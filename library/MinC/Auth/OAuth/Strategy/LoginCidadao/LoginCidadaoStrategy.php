<?php
/**
 * Do strategy for Opauth
 *
 * More information on Opauth: http://opauth.org
 *
 * @link         http://opauth.org
 * @package      Opauth.LoginCidadaoStrategy
 * @license      MIT License
 */

/**
 * Do strategy for Opauth
 *
 * @package            Opauth.LoginCidadao
 */
class LoginCidadaoStrategy extends OpauthStrategy
{

    /**
     * Compulsory config keys, listed as unassociative arrays
     */
    public $expects = array('client_id', 'client_secret', 'application_url_base', 'oauth_url_base');

    /**
     * Optional config keys, without predefining any default values.
     */
    public $optionals = array('redirect_uri', 'scope', 'response_type');

    /**
     * Optional config keys with respective default values, listed as associative arrays
     * eg. array('scope' => 'email');
     */
    public $defaults = array(
        'redirect_uri' => '{path_to_strategy}oauth2callback',
        'scope' => 'email public_profile logout cpf full_name birthdate',
        'response_type' => 'code'
    );

    /**
     * Constructor, change defaults array for get online url
     *
     * @param array $strategy Strategy-specific configuration
     * @param array $env Safe env values from Opauth, with critical parameters stripped out
     */
    public function __construct($strategy, $env)
    {
        parent::__construct($strategy, $env);
        $this->strategy['redirect_uri'] = $this->strategy['application_url_base'] . $this->strategy['redirect_uri']; // Login Cidadao validate url, so no child blog url here
    }

    /**
     * Auth request
     */
    public function request()
    {
        // on Dev
        //$url = $this->strategy['oauth_url_base'].'/web/app_dev.php/oauth/v2/auth';
        // On Prod
        $url = $this->strategy['oauth_url_base'] . '/oauth/v2/auth';

        $params = array(
            'client_id' => $this->strategy['client_id'],
            'redirect_uri' => $this->strategy['redirect_uri'],

        );
        foreach ($this->optionals as $key) {
            if (!empty($this->strategy[$key])) $params[$key] = $this->strategy[$key];
        }
        $this->clientGet($url, $params);
    }

    /**
     * Internal callback, after OAuth
     */
    public function oauth2callback()
    {
        if (array_key_exists('code', $_GET) && !empty($_GET['code'])) {
            $code = $_GET['code'];
            // on Dev use:
            //$url = $this->strategy['oauth_url_base'].'/web/app_dev.php/oauth/v2/token';

            $url = $this->strategy['oauth_url_base'] . '/oauth/v2/token';

            $params = array(
                'code' => $code,
                'client_id' => $this->strategy['client_id'],
                'client_secret' => $this->strategy['client_secret'],
                'redirect_uri' => $this->strategy['redirect_uri'],
                'grant_type' => 'authorization_code',
            );

            if (!empty($this->strategy['state'])) $params['state'] = $this->strategy['state'];
            $response = static::serverPost($url, $params, null, $headers);

            $results = json_decode($response);

            if (!empty($results) && isset($results->access_token)) {
                $user = $this->user($results->access_token);

                $this->auth = array(
                    'uid' => $user['id'],
                    'info' => array(
                        'email' => $user['email'],
                        'profile_picture_url' => $user['profile_picture_url']
                    ),
                    'credentials' => array(
                        'token' => $results->access_token
                    ),
                    'raw' => $user
                );

                if (array_key_exists('given_name', $user)) {
                    $this->auth['info']['display_name'] = $user['given_name'];
                    $this->mapProfile($user, 'name', 'info.display_name');
                }
                if (array_key_exists('first_name', $user)) {
                    $this->auth['info']['first_name'] = $user['first_name'];
                    $this->mapProfile($user, 'first_name', 'info.first_name');
                }

                $this->mapProfile($user, 'last_name', 'info.last_name');
                $this->mapProfile($user, 'email', 'info.email');
                $this->mapProfile($user, 'cpf', 'info.email');
                $this->mapProfile($user, 'full_name', 'info.fullname');
                $this->mapProfile($user, 'birthdate', 'info.birthdate');
                $this->mapProfile($user, 'avatar_url', 'info.profile_picture_url');
                
                $this->callback();
            } else {
                $error = array(
                    'code' => 'access_token_error',
                    'message' => 'Failed when attempting to obtain access token',
                    'raw' => array(
                        'response' => $response,
                        'headers' => $headers
                    )
                );
                $this->errorCallback($error);
            }
        } else {
            $error = array(
                'code' => 'oauth2callback_error',
                'raw' => $_GET
            );
            $this->errorCallback($error);
        }
    }

    /**
     * Queries Do API for user info
     *
     * @param string $access_token
     * @return array Parsed JSON results
     */
    private function user($access_token)
    {
        // On dev use:
        //$user = $this->serverGet( $this->strategy['oauth_url_base'].'/web/app_dev.php/api/v1/person.json', array('access_token' => $access_token), null, $headers);

        $user = $this->serverGet($this->strategy['oauth_url_base'] . '/api/v1/person.json', array('access_token' => $access_token), null, $headers);

        if (!empty($user)) {
            return $this->recursiveGetObjectVars(json_decode($user));
        } else {
            $error = array(
                'code' => 'userinfo_error',
                'message' => 'Failed when attempting to query the Do API for user information',
                'raw' => array(
                    'response' => $user,
                    'headers' => $headers
                )
            );
            $this->errorCallback($error);
        }
    }

    /**
     * Curl Hack
     *
     * Simple server-side HTTP request with file_get_contents
     * Provides basic HTTP calls.
     * See serverGet() and serverPost() for wrapper functions of httpRequest()
     *
     * Notes:
     * Reluctant to use any more advanced transport like cURL for the time being to not
     *     having to set cURL as being a requirement.
     * Strategy is to provide own HTTP transport handler if requiring more advanced support.
     *
     * @param string $url Full URL to load
     * @param array $options Stream context options (http://php.net/stream-context-create)
     * @param string $responseHeaders Response headers after HTTP call. Useful for error debugging.
     * @return string Content resulted from request, without headers
     */
    public static function httpRequest($url, $options = null, &$responseHeaders = null)
    {
        $context = null;
        if (!empty($options) && is_array($options)) {
            if (empty($options['http']['header'])) {
                $options['http']['header'] = "User-Agent: opauth";
            } else {
                $options['http']['header'] .= "\r\nUser-Agent: opauth";
            }
        } else {
            $options = array('http' => array('header' => 'User-Agent: opauth'));
        }
//        $context = stream_context_create($options);
//        $content = file_get_contents($url, false, $context);
//        $responseHeaders = implode("\r\n", $http_response_header);

        $reqString = $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $reqString);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (isset($options['http']['method']) && $options['http']['method'] == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if (isset($options['http']['content'])) {
            $dataString = $options['http']['content'];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        if ($content === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }
        curl_close($ch);
        return $content;
    }

    /**
     * Basic server-side HTTP POST request via self::httpRequest(), wrapper of file_get_contents
     *
     * @param string $url Destination URL
     * @param array $data Data to be POSTed
     * @param array $options Additional stream context options, if any
     * @param string $responseHeaders Response headers after HTTP call. Useful for error debugging.
     * @return string Content resulted from request, without headers
     */
    public static function serverPost($url, $data, $options = array(), &$responseHeaders = null)
    {
        if (!is_array($options)) {
            $options = array();
        }

        $query = http_build_query($data, '', '&');

        $stream = array('http' => array(
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded",
            'content' => $query
        ));

        $stream = array_merge_recursive($options, $stream);

        return static::httpRequest($url, $stream, $responseHeaders);
    }

}