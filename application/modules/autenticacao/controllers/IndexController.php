<?php

/**
 * Controller Login
 * @author tisomar - Politec
 * @author Vinicius Feitosa da Silva <viniciusfesil@mail.com>
 * @since 2011
 * @version 1.0
 * @package application
 * @subpackage application.controller
 * @link http://www.cultura.gov.br
 */
class Autenticacao_IndexController extends MinC_Controller_Action_Abstract
{
    public $orgaoAtivo;

    /**
     * Reescreve o metodo init()
     * @access public
     * @param void
     * @return void
     */
    public function init()
    {
        Zend_Layout::startMvc(array('layout' => 'layout_login'));
        parent::init();
    }

    public function indexAction()
    {
        $oauthConfigArray = Zend_Registry::get("config")->toArray();
        $this->view->habilitarServicoLoginCidadao = false;
        if($oauthConfigArray && $oauthConfigArray['OAuth']) {
            $this->view->habilitarServicoLoginCidadao = (bool)$oauthConfigArray['OAuth']['servicoHabilitado'];
        }
    }

    /**
     * Efetua o login no sistema
     * @access public
     * @param void
     * @return void
     *
     * @todo Melhorar a view do PostgreSQL onde usa 3 funcoes (tabelas.vwUsuariosOrgaosGrupos)
     */
    public function loginAction()
    {
        $this->_helper->layout->disableLayout();

        try {
            $username = Mascara::delMaskCNPJ(Mascara::delMaskCPF($this->getParam('Login', null)));
            $password = $this->getParam('Senha', null);

            if (empty($username) || empty($password))
            {
                throw new Exception("Login ou Senha inv&aacute;lidos!");
            } else if (strlen($username) == 11 && !Validacao::validarCPF($username)) {
                throw new Exception("O CPF informado &eacute; inv&aacute;lido!");
            } else if (strlen($username) == 14 && !Validacao::validarCNPJ($username)) {
                throw new Exception("O CPF informado &eacute; inv&aacute;lido!");
            } else {
                $Usuario = new Autenticacao_Model_Usuario();
                $buscar = $Usuario->login($username, $password);
                if ($buscar) {
                    $auth = array_change_key_case((array) Zend_Auth::getInstance()->getIdentity());
                    $objUnidades = $Usuario->buscarUnidades($auth['usu_codigo'], 21)->current();
                    if($objUnidades) {
                       $objUnidades = $objUnidades->toArray();
                    }
                    // registra o primeiro grupo do usuario (pega unidade autorizada, orgao e grupo do usuario)
                    $Grupo = array_change_key_case($objUnidades);
                    $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
                    $GrupoAtivo->codGrupo = $Grupo['gru_codigo'];
                    $GrupoAtivo->codOrgao = $Grupo['uog_orgao'];
                    $this->orgaoAtivo = $GrupoAtivo->codOrgao;

                    return $this->_helper->redirector->goToRoute(array('controller' => 'principal'), null, true);
                } else {
                    //se nenhum registro foi encontrado na tabela Usuario, ele passa a tentar se logar como proponente.
                    //neste ponto o _forward encaminha o processamento para o metodo login do controller login, que recebe
                    //o post igualmente e tenta encontrar usuario cadastrado em SGCAcesso
                    $this->forward("login-proponente", "index", "autenticacao");
//                    throw new Exception("Usuario inexistente!");
                }
            }

        } catch (Exception $objException) {
            echo '<pre>';
            var_dump($objException->getMessage());
            $this->_helper->viewRenderer->setNoRender(TRUE);
            parent::message($objException->getMessage(), "index", "ERROR");
        }
    }

    /**
     * Metodo que efetua o login
     * @access public
     * @param void
     * @return void
     */
    public function loginProponenteAction()
    {

        // recebe os dados do formulario via post
        $username = Mascara::delMaskCNPJ(Mascara::delMaskCPF($this->getParam('Login', null)));
        $password = $this->getParam('Senha', null);
        $password = str_replace("##menor##", "<", $password);
        $password = str_replace("##maior##", ">", $password);
        $password = str_replace("##aspa##", "'", $password);

        try {
            if (empty($username) || empty($password)) {
                # verifica se os campos foram preenchidos
                parent::message("Senha ou login inv&aacute;lidos", "/login/index", "ALERT");
            } else if (strlen($username) == 11 && !Validacao::validarCPF($username)) {
                # verifica se o CPF e valido
                parent::message("CPF inv&aacute;lido", "/login/index");
            } else if (strlen($username) == 14 && !Validacao::validarCNPJ($username)) // verifica se o CNPJ e valido
            {
                parent::message("CNPJ inv&aacute;lido", "/login/index");
            } else {
                // realiza a busca do usuario no banco, fazendo a autenticacao do mesmo
                $Usuario = new Autenticacao_Model_Sgcacesso();
                $verificaStatus = $Usuario->buscar(array('cpf = ?' => $username))->toArray();
                if ($verificaStatus) {
                    $verificaStatus = array_change_key_case(reset($verificaStatus));

                    $IdUsuario = $verificaStatus['IdUsuario'];
                    $verificaSituacao = $verificaStatus['Situacao'];

                    if (md5($password) != $this->validarSenhaInicial()) {
                        $SenhaFinal = EncriptaSenhaDAO::encriptaSenha($username, $password);
                        $buscar = $Usuario->loginSemCript($username, $SenhaFinal);
                    } else {
                        $buscar = $Usuario->loginSemCript($username, md5($password));
                    }
                    if (!$buscar) {
                        parent::message("Login ou Senha inv&aacute;lidos!", "/autenticacao", "ALERT");
                    }
                } else {
                    $SenhaFinal = addslashes($password);
                    $buscar = $Usuario->loginSemCript($username, $SenhaFinal);
                }

                if ($buscar) {
                    $verificaSituacao = $verificaStatus['Situacao'];
                    if ($verificaSituacao == 1) {
                        parent::message("Voc&ecirc; logou com uma senha tempor&aacute;ria. Por favor, troque a senha.", "/autenticacao/index/alterarsenha?idUsuario=" . $IdUsuario, "ALERT");
                    }
                    $agentes = new Agente_Model_DbTable_Agentes();
                    $verificaAgentes = $agentes->buscar(array('cnpjcpf = ?' => $username))->current();
                    if (!empty ($verificaAgentes)) {
                        //                                        $this->_redirect("/agente/agentes/incluiragenteexterno");
                        //                                        parent::message("Voc&ecirc; ainda n&atilde;o est&aacute; cadastrado como proponente, por favor fa&ccedil;a isso agora.", "/manteragentes/agentes?acao=cc&idusuario={$verificaStatus[0]->IdUsuario}", "ALERT");
                        return $this->_helper->redirector->goToRoute(array('controller' => 'principalproponente'), null, true);
                    } else {
                        return $this->_helper->redirector->goToRoute(array('controller' => 'principalproponente'), null, true);
                        //parent::message("Voc&ecirc; ainda n&atilde;o est&aacute; cadastrado como proponente, por favor fa&ccedil;a isso agora.", "/agente/manteragentes/agentes?acao=cc&idusuario={$verificaStatus['idusuario']}", "ALERT");
                    }

                }
                else {
                    parent::message("Usu&aacute;rio n&atilde;o cadastrado", "/autenticacao", "ALERT");
                }
            }
        }
        catch (Exception $e) {
            echo '<pre>';
            var_dump($e->getMessage());
            $this->_helper->viewRenderer->setNoRender(TRUE);
            parent::message($e->getMessage(), "index", "ERROR");
        }
    }

    /**
     * Efetua o logout do sistema
     * @access public
     * @param void
     * @return void
     */
    public function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        Zend_Session::destroy();
        unset($_SESSION);

        $arrayOauthConfiguration = $this->getOPAuthConfiguration();
        if(count($arrayOauthConfiguration) > 0) {
            $url = $arrayOauthConfiguration['logout_uri'];
            $this->redirect($url);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * cadastrarusuarioAction
     *
     * @access public
     * @return void
     */
    public function cadastrarusuarioAction()
    {

        if ($_POST) {

            $post = Zend_Registry::get('post');
            $cpf = Mascara::delMaskCNPJ(Mascara::delMaskCPF($post->cpf));

            if (trim($post->email) != trim($post->emailConf)) {
                parent::message("Digite o email certo!", "/autenticacao/index/cadastrarusuario", "ALERT");
            }

            $senha = Gerarsenha::gerasenha(15, true, true, true, false);
            $db = Zend_Db_Table::getDefaultAdapter();
            $senhaCriptografada = EncriptaSenhaDAO::encriptaSenha($cpf, $senha);
            $dataFinal = data::dataAmericana($post->dataNasc);

            $dados = array(
                "Cpf" => $cpf,
                "Nome" => $post->nome,
                "DtNascimento" => $dataFinal,
                "Email" => $post->email,
                "Senha" => $senhaCriptografada,
                "DtCadastro" => date("Y-m-d"),
                "Situacao" => 1,
                "DtSituacao" => date("Y-m-d")
            );

            $sgcAcesso = new Autenticacao_Model_Sgcacesso();
            $sgcAcessoBuscaCpf = $sgcAcesso->buscar(array("Cpf = ?" => $cpf));

            $sgcAcessoBuscaCpfArray = $sgcAcessoBuscaCpf->toArray();

            if (!empty ($sgcAcessoBuscaCpfArray)) {
                parent::message("CPF j&aacute; cadastrado", "/autenticacao/index/cadastrarusuario", "ALERT");
            }

            $sgcAcessoBuscaEmail = $sgcAcesso->buscar(array("Email = ?" => $post->email));
            $sgcAcessoBuscaEmailArray = $sgcAcessoBuscaEmail->toArray();

            if (!empty ($sgcAcessoBuscaEmailArray)) {
                parent::message("E-mail j&aacute; cadastrado", "/autenticacao/index/cadastrarusuario", "ALERT");
            }

            if (empty ($sgcAcessoBuscaCpfArray) && empty ($sgcAcessoBuscaEmailArray)) {
                /**
                 * ==============================================================
                 * INICIO DO VINCULO DO RESPONSAVEL COM ELE MESMO (PROPONENTE)
                 * ==============================================================
                 */
                /* ========== VERIFICA SE O RESPONSAVEL JA TEM CADASTRO COMO PROPONENTE ========== */
                $Agentes = new Agente_Model_DbTable_Agentes();
                $Visao = new Visao();

                $sgcAcessoSave = $sgcAcesso->salvar($dados);

                $buscarAgente = $Agentes->buscar(array('CNPJCPF = ?' => $cpf));

                $idAgenteProp = count($buscarAgente) > 0 ? $buscarAgente[0]->idAgente : 0;
                $buscarVisao = $Visao->buscar(array('Visao = ?' => 144, 'stAtivo = ?' => 'A', 'idAgente = ?' => $idAgenteProp));

                /* ========== VINCULA O RESPONSAVEL A SEU PROPRIO PERFIL DE PROPONENTE ========== */
                if (count($buscarVisao) > 0) :
                    $tbVinculo = new Agente_Model_DbTable_TbVinculo();
                    $idResp = $sgcAcesso->buscar(array('Cpf = ?' => $sgcAcessoSave)); // pega o id do responsavel cadastrado
                    $dadosVinculo = array(
                        'idAgenteProponente' => $idAgenteProp
                        ,'dtVinculo' => new Zend_Db_Expr('GETDATE()')
                        ,'siVinculo' => 2
                        ,'idUsuarioResponsavel' => $idResp[0]->IdUsuario
                    );
                    $tbVinculo->inserir($dadosVinculo);
                endif;

                /**
                 * ==============================================================
                 * FIM DO VINCULO DO RESPONSAVEL COM ELE MESMO (PROPONENTE)
                 * ==============================================================
                 */
                /* ========== ENVIA O E-MAIL PARA O USUARIO ========== */
                $assunto = "Cadastro SALICWEB";
                $perfil = 'SALICWEB';
                $mens = "Ol&aacute; $post->nome ,<br><br>";
                $mens .= "Senha: $senha <br><br>";
                $mens .= "Esta &eacute; a sua senha de acesso ao Sistema de Apresenta&ccedil;&atilde;o de Projetos via Web do ";
                $mens .= "Minist&eacute;rio da Cultura.<br><br>Lembramos que a mesma dever&aacute; ser ";
                $mens .= "trocada no seu primeiro acesso ao sistema.<br><br>";
                $mens .= "Esta &eacute; uma mensagem autom&aacute;tica. Por favor n&atilde;o responda.<br><br>";
                $mens .= "Atenciosamente,<br>Minist&eacute;rio da Cultura";

                $enviaEmail = EmailDAO::enviarEmail($post->email, $assunto, $mens, $perfil);
                parent::message("Cadastro efetuado com sucesso. Verifique a senha no seu email", "/autenticacao", "CONFIRM");
            }
        }
    }

    /**
     * solicitarsenhaAction
     *
     * @access public
     * @return void
     * @author wouerner <wouerner@gmail.com>
     */
    public function solicitarsenhaAction()
    {
        if ($_POST) {
            $post = Zend_Registry::get('post');
            $cpf = Mascara::delMaskCNPJ(Mascara::delMaskCPF($post->cpf)); // recebe cpf
            $dataNasc = data::dataAmericana($post->dataNasc); // recebe dataNasc
            $email = $post->email;
            $sgcAcesso = new Autenticacao_Model_Sgcacesso();

            $sgcAcessoBuscaCpf = $sgcAcesso->buscar(array("Cpf = ?" => $cpf, "Email = ?" => $email, "DtNascimento = ?" => $dataNasc));
            $verificaUsuario = $sgcAcessoBuscaCpf->toArray();
            if (empty ($verificaUsuario)) {
                parent::message("Dados incorretos!", "/autenticacao", "ALERT");
            }

            $sgcAcessoBuscaCpfArray = $verificaUsuario;
            $nome = $sgcAcessoBuscaCpfArray[0]['Nome'];
            $senha = Gerarsenha::gerasenha(15, true, true, true, true);
            $senhaFormatada = str_replace(">", "", str_replace("<", "", str_replace("'", "", $senha)));

            $senhaFormatada = EncriptaSenhaDAO::encriptaSenha($cpf, $senhaFormatada);

            $dados = array(
                "IdUsuario" => $sgcAcessoBuscaCpfArray[0]['IdUsuario'],
                "Senha" => $senhaFormatada,
                "Situacao" => 1,
                "DtSituacao" => date("Y-m-d")
            );

            $sgcAcessoSave = $sgcAcesso->salvar($dados);
            $assunto = "Cadastro SALICWEB";
            $perfil = "SALICWEB";
            $mens = "Ol&aacute; " . $nome . ",<br><br>";
            $mens .= "Senha....: " . $senha. "<br><br>";
            $mens .= "Esta &eacute; a sua senha tempor&aacute;ria de acesso ao Sistema de Apresenta&ccedil;&atilde;o de Projetos via Web do ";
            $mens .= "Minist&eacute;rio da Cultura.<br><br>Lembramos que a mesma dever&aacute; ser ";
            $mens .= "trocada no seu primeiro acesso ao sistema.<br><br>";
            $mens .= "Esta &eacute; uma mensagem autom&aacute;tica. Por favor n&atilde;o responda.<br><br>";
            $mens .= "Atenciosamente,<br>Minist&eacute;rio da Cultura";

            $email = $sgcAcessoBuscaCpfArray[0]['Email'];

            $enviaEmail = EmailDAO::enviarEmail($email, $assunto, $mens, $perfil);
            parent::message("Senha gerada com sucesso. Verifique seu email!", "/autenticacao", "CONFIRM");
        }
    }


    /**
     * alterarsenhaAction
     *
     * @access public
     * @return void
     * @author wouerner <wouerner@gmail.com>
     */
    public function alterarsenhaAction()
    {

        // autenticacao proponente (Novo Salic)
        //parent::perfil(4);

        /* ========== INICIO ID DO USUARIO LOGADO ========== */
        $auth = (array) Zend_Auth::getInstance()->getIdentity();
        $Usuario = new Autenticacao_Model_Usuario();

        // verifica se o usuario logado e agente
        $idUsuario = $Usuario->getIdUsuario(null, $auth['Cpf']);

        // caso nao tenha idAgente, atribui o idUsuario
        $this->getIdUsuario = ($idUsuario) ? $idUsuario['idagente'] : $auth['IdUsuario'];
        $this->getIdUsuario = empty($this->getIdUsuario) ? 0 : $this->getIdUsuario;

//        Zend_Layout::startMvc(array('layout' => 'layout_proponente'));

        $this->view->cpf = "";
        $this->view->nome = "";
        $dataFormatada = "";
        $this->view->dtNascimento = "";
        $this->view->email = "";
        if (count(Zend_Auth::getInstance()->getIdentity()) > 0) {
            $auth = Zend_Auth::getInstance();

            $idUsuario = $auth->getIdentity()->IdUsuario ?$auth->getIdentity()->IdUsuario : $auth->getIdentity()->IdUsuario ;

            $this->view->idUsuario = $idUsuario;
            $cpf = $auth->getIdentity()->Cpf;
            $this->view->cpf = $auth->getIdentity()->Cpf;
            $this->view->nome = $auth->getIdentity()->Nome;
            $dataFormatada = data::formatarDataMssql($auth->getIdentity()->DtNascimento);
            $this->view->dtNascimento = $dataFormatada;
            $this->view->email = $auth->getIdentity()->Email;

        }

        if ($_POST) {

            $post = Zend_Registry::get('post');
            $senhaAtual = $post->senhaAtual;
            $senhaNova = $post->senhaNova;
            $repeteSenha = $post->repeteSenha;

            $senhaAtual = str_replace("##menor##", "<", $senhaAtual);
            $senhaAtual = str_replace("##maior##", ">", $senhaAtual);
            $senhaAtual = str_replace("##aspa##", "'", $senhaAtual);

            $sgcAcesso = new Autenticacao_Model_Sgcacesso();

            $idUsuario = $_POST['idUsuario'];
            if (empty ($_POST['idUsuario'])) {
                $idUsuario = $_POST['idUsuarioGet'];
            }

            $buscarSenha = $sgcAcesso->findBy(array('idUsuario' => $idUsuario));
            $senhaAtualBanco = $buscarSenha['Senha'];

            if (empty ($cpf)) {
                $cpf = $buscarSenha['Cpf'];
            }

            // busca a senha do banco TABELAS
            $mdlUsuario = new Autenticacao_Model_Usuario();

            $buscarCPF = $mdlUsuario->findBy(array('usu_identificacao' => $cpf));

            $cpfTabelas = count($buscarCPF) > 0 ? true : false;
            $senhaTabelas = $mdlUsuario->verificarSenha(trim($cpf), $senhaAtual);

            $senhaCript = EncriptaSenhaDAO::encriptaSenha($cpf, $senhaAtual);

            if ($buscarSenha['Situacao'] != 1) {
                $comparaSenha = EncriptaSenhaDAO::encriptaSenha($cpf, $senhaAtual);
                $SenhaFinal = $comparaSenha;

                //@todo verificar as regras de negocios para $cpfTabelas
                if (trim($senhaAtualBanco) != trim($SenhaFinal) && !$senhaTabelas) {
                    parent::message("Por favor, digite a senha atual correta!", "/autenticacao/index/alterarsenha?idUsuario=$idUsuario", "ALERT");
                }

                if (trim($senhaAtualBanco) != trim($SenhaFinal) && ($cpfTabelas && !$senhaTabelas)) {
                    parent::message("Por favor, digite a senha atual correta!", "/autenticacao/index/alterarsenha?idUsuario=$idUsuario", "ALERT");
                }
            } else {

                //@todo verificar as regras de negocios para $cpfTabelas
                if (trim($senhaAtualBanco) != trim($senhaCript) && !$senhaTabelas) {
                    parent::message("Por favor, digite a senha atual correta!", "/autenticacao/index/alterarsenha?idUsuario=$idUsuario", "ALERT");
                }

                if (trim($senhaAtualBanco) != trim($senhaCript) && ($cpfTabelas && !$senhaTabelas)) {
                    parent::message("Por favor, digite a senha atual correta!", "/autenticacao/index/alterarsenha?idUsuario=$idUsuario", "ALERT");
                }
            }
            if (trim($senhaNova) == trim($repeteSenha) && !empty($senhaNova) && !empty($repeteSenha)) {

                if (empty ($idUsuario)) {
                    $post = Zend_Registry::get('post');
                    $idUsuario = $post->idUsuario;
                }
                $sgcAcessoBuscaCpf = $sgcAcesso->buscar(array("IdUsuario = ?" => $idUsuario));

                $cpf = $sgcAcessoBuscaCpf[0]['Cpf'];
                $nome = $sgcAcessoBuscaCpf[0]['Nome'];
                $email = $sgcAcessoBuscaCpf[0]['Email'];
                $senhaCriptografada = EncriptaSenhaDAO::encriptaSenha($cpf, $senhaNova);

                $dados = array(
                    "IdUsuario" => $idUsuario,
                    "Senha" => $senhaCriptografada,
                    "Situacao" => 3,
                    "DtSituacao" => date("Y-m-d")
                );

                $sgcAcessoSave = $sgcAcesso->salvar($dados);

                $assunto = "Cadastro SALICWEB";
                $perfil = "SALICWEB";
                $mens = "Ol&aacute; " . $nome . ",<br><br>";
                $mens .= "Senha....: " . $senhaNova . "<br><br>";
                $mens .= "Esta &eacute; a sua nova senha de acesso ao Sistema de Apresenta&ccedil;&atilde;o de Projetos via Web do ";
                $mens .= "Minist&eacute;rio da Cultura.<br><br>";
                $mens .= "Esta &eacute; uma mensagem autom&aacute;tica. Por favor n&atilde;o responda.<br><br>";
                $mens .= "Atenciosamente,<br>Minist&eacute;rio da Cultura";

                $enviaEmail = EmailDAO::enviarEmail($email, $assunto, $mens, $perfil);
                parent::message("Senha alterada com sucesso!", "/autenticacao", "CONFIRM");
            }
        }
    }

    public function alterarsenhausuarioAction()
    {
        parent::perfil(0);
        // autenticacao proponente (Novo Salic)

        /* ========== INICIO ID DO USUARIO LOGADO ========== */
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $Usuario = new Autenticacao_Model_Usuario();

        // verifica se o usuario logado e agente
        $idUsuario = $Usuario->getIdUsuario(null, $auth->getIdentity()->usu_identificacao);
        if (isset($auth->getIdentity()->usu_identificacao)) {
            // caso nao tenha idAgente, atribui o idUsuario
            $this->getIdUsuario = ($idUsuario) ? $idUsuario['idagente'] : $auth->getIdentity()->usu_codigo;
            //$this->getIdUsuario = empty($this->getIdUsuario) ? 0 : $this->getIdUsuario;
            /* ========== FIM ID DO USUARIO LOGADO ========== */

        }

        Zend_Layout::startMvc(array('layout' => 'layout'));

        $this->view->cpf = "";
        $this->view->nome = "";

        if (count(Zend_Auth::getInstance()->getIdentity()) > 0) {
            $auth = Zend_Auth::getInstance();// instancia da autenticacao

            $idUsuario = $auth->getIdentity()->usu_codigo;
            $cpf = $auth->getIdentity()->usu_identificacao;

            $this->view->idUsuario = $auth->getIdentity()->usu_codigo;
            $this->view->cpf = $auth->getIdentity()->usu_identificacao;
            $this->view->nome = $auth->getIdentity()->usu_nome;

        }

        if ($_POST) {

            $post = Zend_Registry::get('post');

            $senhaAtual = $post->senhaAtual; // recebe senha atua
            $senhaNova = $post->senhaNova; // recebe senha nova
            $repeteSenha = $post->repeteSenha; // recebe repete senha

            $senhaAtual = str_replace("##menor##", "<", $senhaAtual);
            $senhaAtual = str_replace("##maior##", ">", $senhaAtual);
            $senhaAtual = str_replace("##aspa##", "'", $senhaAtual);

            $idUsuario = $_POST['idUsuario'];
            if (empty ($_POST['idUsuario'])) {
                $idUsuario = $_POST['idUsuarioGet'];
            }
            $buscarSenha = $Usuario->buscar(array('usu_codigo = ?' => $idUsuario))->toArray();
            $senhaAtualBanco = $buscarSenha[0]['usu_senha'];

            $comparaSenha = EncriptaSenhaDAO::encriptaSenha($cpf, $senhaAtual);
            $SenhaFinal = $comparaSenha[0]->senha;

            $comparaSenhaNova = EncriptaSenhaDAO::encriptaSenha($cpf, $senhaNova);
            $senhaNovaCript = $comparaSenhaNova[0]->senha;

            if (trim($senhaAtualBanco) != trim($SenhaFinal)) {
                parent::message("Por favor, digite a senha atual correta!", "/autenticacao/index/alterarsenhausuario?idUsuario=$idUsuario", "ALERT");
            }

            if ($repeteSenha != $senhaNova) {
                parent::message("Senhas diferentes!", "/autenticacao/index/alterarsenhausuario?idUsuario=$idUsuario", "ALERT");
            }

            if ($senhaAtualBanco == $senhaNovaCript) {
                parent::message("Por favor, digite a senha diferente da atual!", "/autenticacao/index/alterarsenhausuario?idUsuario=$idUsuario", "ALERT");
            }

            if (strlen(trim($senhaNova)) < 5) {
                parent::message("Por favor, sua nova senha dever&aacute; conter no m&iacute;nimo 5 d&iacute;gitos!", "/autenticacao/index/alterarsenhausuario?idUsuario=$idUsuario", "ALERT");
            }

            $alterar = $Usuario->alterarSenhaSalic($cpf, $senhaNova);
            if ($alterar) {
                parent::message("Senha alterada com sucesso!", "/principal/index/", "CONFIRM");
            } else {
                parent::message("Erro ao alterar senha!", "/autenticacao/index/alterarsenhausuario?idUsuario=$idUsuario", "ALERT");
            }
        }
    }

    public function logarcomoAction()
    {

//        $this->_helper->layout->disableLayout();
//        Zend_Layout::startMvc(array('layout' => 'layout_proponente'));

        $buscaUsuario = new Usuariosorgaosgrupos();
        $buscaUsuarioRs = $buscaUsuario->buscarUsuariosOrgaosGrupos(
            array('gru_status > ?' => 0, 'sis_codigo = ?' => 21), array('usu_nome asc'));

        $this->view->buscaUsuario = $buscaUsuarioRs->toArray();


        if ($_POST) {


            // recebe os dados do formul&aacute;rio via post
            $post = Zend_Registry::get('post');
            $username = Mascara::delMaskCNPJ(Mascara::delMaskCPF($post->cpf)); // recebe o login sem mascaras
            $password = $post->senha; // recebe a senha
            $idLogarComo = $post->logarComo;

            $senhaFinal = EncriptaSenhaDAO::encriptaSenha($username, $password);

            $usuario = new Autenticacao_Model_Usuario();
            $usuarioRs = $usuario->buscar(
                array('usu_identificacao = ?' => $username, 'usu_senha = ?' => $senhaFinal)
            );

            if (!empty ($usuarioRs)) {
                $usuarioRs = $usuario->buscar(
                    array('usu_identificacao = ?' => $idLogarComo))->current();
                $senha = $usuarioRs->usu_senha;

                $Usuario = new Autenticacao_Model_Usuario();
                $buscar = $Usuario->loginSemCript($idLogarComo, $senha);

                if ($buscar) {
                    $auth = Zend_Auth::getInstance(); // instancia da autenticacao

                    // registra o primeiro grupo do usu&aacute;rio (pega unidade autorizada, organiza e grupo do usuaaio)
                    $Grupo = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21); // busca todos os grupos do usuario
                    $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess?o com o grupo ativo
                    $GrupoAtivo->codGrupo = $Grupo[0]->gru_codigo; // armazena o grupo na sess?o
                    $GrupoAtivo->codOrgao = $Grupo[0]->uog_orgao; // armazena o org?o na sess?o

                    // redireciona para o Controller protegido
                    return $this->_helper->redirector->goToRoute(array('controller' => 'principal'), null, true);
                }
            }
        }
    }

    /**
     *
     * @name alterardadosAction
     *
     * @author Ruy Junior Ferreira Silva <ruyjfs@gmail.com>
     * @since  01/09/2016
     */
    public function alterardadosAction()
    {
        // autenticacao proponente (Novo Salic)
        parent::perfil(4);

        /* ========== INICIO ID DO USUARIO LOGADO ========== */
        $auth = array_change_key_case((array) Zend_Auth::getInstance()->getIdentity());
        $Usuario = new Autenticacao_Model_Usuario();

        // verifica se o usuario logado e agente
        $idUsuario = $Usuario->getIdUsuario(null, $auth['cpf']);

        // caso nao tenha idAgente, atribui o idUsuario
        $this->getIdUsuario = ($idUsuario) ? $idUsuario['idagente'] : $auth['idusuario'];
        $this->getIdUsuario = empty($this->getIdUsuario) ? 0 : $this->getIdUsuario;

        /* ========== FIM ID DO USUARIO LOGADO ========== */

        $sgcAcesso = new Autenticacao_Model_Sgcacesso();
        $cpf = Mascara::delMaskCPF($auth['cpf']);
        $buscarDados = array_change_key_case($sgcAcesso->buscar(array('cpf = ?' => $cpf))->current()->toArray());

        if (count(Zend_Auth::getInstance()->getIdentity()) > 0) {
            if (strlen($buscarDados['cpf']) > 11) {
                $this->view->cpf = Mascara::addMaskCNPJ($buscarDados['cpf']);
            } else {
                $this->view->cpf = Mascara::addMaskCPF($buscarDados['cpf']);
            }

            $this->view->nome = $buscarDados['nome'];
            $dataFormatada = Data::tratarDataZend($buscarDados['dtnascimento'], 'Brasileira');
            $this->view->dtNascimento = $dataFormatada;
            $this->view->email = $buscarDados['email'];
        }

//        $this->_helper->layout->disableLayout(); // desabilita Zend_Layout
//        Zend_Layout::startMvc(array('layout' => 'layout_proponente'));

        if ($_POST) {
            $post = Zend_Registry::get('post');
            $cpf = Mascara::delMaskCNPJ(Mascara::delMaskCPF($post->cpf)); // recebe cpf
            $nome = $post->nome; // recebe o nome
            $dataNasc = $post->dataNasc; // recebe dataNasc
            $email = $post->email; // recebe email
            $emailConf = $post->emailConf; // recebe confirmacao senha
            $module = $this->getRequest()->getModuleName();
            $controller = $this->getRequest()->getControllerName();

            if (trim($email) != trim($emailConf)) {
                parent::message("Digite o email certo!", "/{$module}/{$controller}/alterardados", "ALERT");
            }

            $dataFinal = data::dataAmericana($dataNasc);
            $dados = array(
                "idusuario" => $auth['idusuario'],
                "cpf" => $cpf,
                "nome" => $nome,
                "dtnascimento" => $dataFinal . ' 00:00:00',
                "email" => $email,
                "dtcadastro" => date("Y-m-d"),
                "dtsituacao" => date("Y-m-d")
            );

            $sgcAcessoSave = $sgcAcesso->salvar($dados);
            parent::message("Dados alterados com sucesso", "/{$module}/{$controller}/alterardados", "CONFIRM");
        }
    }
}

