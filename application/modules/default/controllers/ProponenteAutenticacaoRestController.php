<?php

/**
 * Login e autentica&ccedil;&atilde;o via REST
 *
 * @version 1.1
 * @package application
 * @subpackage application.controller
 * @link http://www.cultura.gov.br
 */
class ProponenteAutenticacaoRestController extends Minc_Controller_AbstractRest{

    public function init() {
        $this->setPublicMethod('post');
        parent::init();
    }

    public function postAction() {
        $result = (object) array('msg' => '');

        # Pegando parametros via POST no formato JSON
        $body = $this->getRequest()->getRawBody();
        $post = Zend_Json::decode($body);

        $username = isset($post['usuario'])? $post['usuario']: NULL;
        $password = isset($post['senha'])? $post['senha']: NULL;
        $registrationId = $this->registrationId;

        if(empty($username) || empty($password)){
            $result->msg = 'CPF ou Senha inv&aacute;lidos!';
        } else if (strlen($username) == 11 && !Validacao::validarCPF($username)){
            $result->msg = 'CPF inv&aacute;lido!';
        } else {
            $Usuario = new Autenticacao_Model_Sgcacesso();
            $verificaStatus = $Usuario->buscar(array ( 'Cpf = ?' => $username));

            $verificaSituacao = 0;
            if(count($verificaStatus)>0) {
                $IdUsuario =  $verificaStatus[0]->IdUsuario;
                $verificaSituacao = $verificaStatus[0]->Situacao;
            }

            if ($verificaSituacao != 1) {
                if(md5($password) != $this->validarSenhaInicial()){
                    $SenhaFinal = EncriptaSenhaDAO::encriptaSenha($username, $password);
                    $buscar = $Usuario->loginSemCript($username, $SenhaFinal);
                } else {
                    $buscar = $Usuario->loginSemCript($username, md5($password));
                }

                if(!$buscar){
                    $result->msg = 'CPF ou Senha inv&aacute;lidos!';
                }
            } else {
                $SenhaFinal = addslashes($password);
                $buscar = $Usuario->loginSemCript($username, $SenhaFinal);
            }

            if($buscar){
                $result->usuario = Zend_Auth::getInstance()->getIdentity();
                $result->authorization = $this->encryptAuthorization();
                # Corrigi caracteres n&atilde;o utf8 para retornar dados da requisi&ccedil;&atilde;o corretamente.
                if($result->usuario && $result->usuario->Nome){
                    $result->usuario->Nome = utf8_encode($result->usuario->Nome);
                }

                $verificaSituacao = $verificaStatus[0]->Situacao;
                if($verificaSituacao == 1) {
                    $result->msg = 'Voc&ecirc; logou com uma senha tempor&aacute;ria. Por favor, troque a senha depois.';
                }

                $modelDispositivoMovel = new Dispositivomovel();
                $result->dispositivo = $modelDispositivoMovel->salvar($registrationId, $username);

            } else {
                $result->msg = 'CPF ou Senha inv&aacute;lidos!';
            }
        }

        # Resposta da autentica&ccedil;&atilde;o.
        $this->getResponse()->setHttpResponseCode(200)->setBody(json_encode($result));
    }

    /**
     * Gera a chave de acesso do usu�rio para utilizar os servi&ccedil;os que precisam de identifica&ccedil;&atilde;o de usu&aacute;rio.
     *
     * @return string
     */
    protected function encryptAuthorization(){
        $usuario = Zend_Auth::getInstance()->getIdentity();
        $authorization = Seguranca::encrypt($this->publicKey. $usuario->Cpf. $this->publicKey, $this->encryptHash);

        return $authorization;
    }

    /**
     * Define senha inicial para cadastros incompletos.
     *
     * @return string
     */
    public static function validarSenhaInicial(){
        return MinC_Controller_Action_Abstract::validarSenhaInicial();
    }

    public function indexAction(){}

    public function getAction(){}

    public function putAction(){}

    public function deleteAction(){}

}
