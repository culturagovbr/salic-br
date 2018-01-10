<?php

class Proposta_ManterpropostaincentivofiscalController extends Proposta_GenericController
{
    private $idResponsavel = 0;
    private $idAgente = 0;
    private $idUsuario = 0;
    private $idPreProjeto = null;
    private $blnPossuiDiligencias = 0;
    private $idAgenteProponente = 0;
    private $cpfLogado = null;
    private $usuarioProponente = "N";

    public function init()
    {
        $auth = Zend_Auth::getInstance();
        $arrAuth = array_change_key_case((array)$auth->getIdentity());
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');

        // verifica as permissoes
        $PermissoesGrupo = array();
        $PermissoesGrupo[] = 97;  // Gestor do SALIC
        $PermissoesGrupo[] = 93;  // Coordenador de Parecerista
        $PermissoesGrupo[] = 94;  // Parecerista
        $PermissoesGrupo[] = 121; // Tecnico
        $PermissoesGrupo[] = 122; // Coordenador de Acompanhamento

        if (isset($auth->getIdentity()->usu_codigo)) {
            parent::perfil(1, $PermissoesGrupo);
        } else {
            parent::perfil(4, $PermissoesGrupo);
        }
        $cpf = isset($arrAuth['usu_codigo']) ? $arrAuth['usu_identificacao'] : $arrAuth['cpf'];

        // Busca na SGCAcesso
        $sgcAcesso = new Autenticacao_Model_Sgcacesso();
        $acessos = $sgcAcesso->findBy(array('Cpf' => $cpf));

        // Busca na Usuarios
        $mdlusuario = new Autenticacao_Model_Usuario();
        $usuario = $mdlusuario->findBy(array('usu_identificacao' => $cpf));

        // Busca na Agentes
        $tblAgentes = new Agente_Model_DbTable_Agentes();
        $agente = $tblAgentes->findBy(array('CNPJCPF' => $cpf));

        if ($agente) {
            $this->idResponsavel = $acessos['IdUsuario'];
            $this->idAgente = $agente['idAgente'];
        }
        if ($usuario) {
            $this->idUsuario = $usuario['usu_codigo'];
            if ($this->proponente != 0) {
                $this->usuarioProponente = "S";
            }
        }

        // Busca na tabela apoio ExecucaoImediata
        $tableVerificacao = new Proposta_Model_DbTable_Verificacao();
        $listaExecucaoImediata = $tableVerificacao->fetchPairs('idVerificacao', 'Descricao', array('idTipo' => 23), array('idVerificacao'));
        $this->view->listaExecucaoImediata = $listaExecucaoImediata;

        $this->cpfLogado = $cpf;
        $this->idAgenteProponente = $this->idAgente;
        $this->usuario = isset($arrAuth['usu_codigo']) ? 'func' : 'prop';
        $this->view->usuarioLogado = isset($arrAuth['usu_codigo']) ? 'func' : 'prop';
        $this->view->usuarioProponente = $this->usuarioProponente;
        parent::init();
        //recupera ID do pre projeto (proposta)
        $idPreProjeto = $this->getRequest()->getParam('idPreProjeto');

        if (!empty($idPreProjeto)) {
            $this->idPreProjeto = $idPreProjeto;
            $this->view->idPreProjeto = $idPreProjeto;

            $this->verificarPermissaoAcesso(true, false, false);

            //VERIFICA SE A PROPOSTA ESTA COM O MINC
            // @todo criei um metodo separado para verificar a situacao, fazer os testes e retirar esse trecho
            $Movimentacao = new Proposta_Model_DbTable_TbMovimentacao();
            $rsStatusAtual = $Movimentacao->buscarStatusAtualProposta($this->idPreProjeto);
            $this->view->movimentacaoAtual = isset($rsStatusAtual['Movimentacao']) ? $rsStatusAtual['Movimentacao'] : '';

            //VERIFICA SE A PROPOSTA FOI ENVIADA AO MINC ALGUMA VEZ
            $arrbusca = array();
            $arrbusca['idProjeto = ?'] = $this->idPreProjeto;
            $arrbusca['Movimentacao = ?'] = '96';
            $rsHistMov = $Movimentacao->buscar($arrbusca);
            $this->view->blnJaEnviadaAoMinc = $rsHistMov->count();

            //VERIFICA SE A PROPOSTA TEM DILIGENCIAS
            $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
            $rsDiligencias = $PreProjeto->listarDiligenciasPreProjeto(array('pre.idPreProjeto = ?' => $this->idPreProjeto));
            $this->view->blnPossuiDiligencias = $rsDiligencias->count();

            $this->view->acao = $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar";

        }
    }

    public function verificaPermissaoAcessoProposta($idPreProjeto)
    {
        $tblProposta = new Proposta_Model_DbTable_PreProjeto();
        $rs = $tblProposta->buscar(array("idPreProjeto = ? " => $idPreProjeto, "1=1 OR idEdital IS NULL OR idEdital > 0" => "?", "idUsuario =?" => $this->idResponsavel));
        return $rs->count();
    }

    public function indexAction()
    {
        if ($this->idPreProjeto) {
            $this->redirect("/proposta/manterpropostaincentivofiscal/identificacaodaproposta/idPreProjeto/" . $this->idPreProjeto);
        } else {
            $this->redirect("/proposta/manterpropostaincentivofiscal/listarproposta");
        }

        $arrBusca = array();
        $arrBusca['stestado = ?'] = 1;
        $arrBusca['idusuario = ?'] = $this->idResponsavel;
        // Chama o SQL
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->buscar($arrBusca, array("idagente ASC"));

        //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
        $this->montaTela(
            "manterpropostaincentivofiscal/index.phtml",
            array(
                "acaoAlterar" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/identificacaodaproposta",
                "acaoExcluir" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/excluir",
                "dados" => $rsPreProjeto
            )
        );
    }

    public function declaracaonovapropostaAction()
    {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $post = Zend_Registry::get('post');

        if ($post->mecanismo == 1) { //mecanismo == 1 (proposta por incentivo fiscal)
            $url = $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/identificacaodaproposta";
        } else {
            $url = $this->_urlPadrao . "/manterpropostaedital/editalconfirmar";
        }

        //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
        $this->montaTela("manterpropostaincentivofiscal/declaracaonovaproposta.phtml", array("acao" => $url,
            "agente" => $post->propronente));
    }

    /**
     * buscaproponenteAction
     *
     * @access public
     * @return void
     * @deprecated Fiz as alteracoes e este metodo era desnecessario para iniciar uma nova proposta
     */
    public function buscaproponenteAction()
    {
        $post = Zend_Registry::get('post');

        if (empty($post->idAgente)) {
            $this->montaTela("manterpropostaincentivofiscal/declaracaonovaproposta.phtml");
            return;
        }

        //VERIFICA SE PROPONETE JA ESTA CADASTRADO
        $arrBusca = array();
        $arrBusca['a.idAgente = ?'] = $post->idAgente;
        $tblAgente = new Agente_Model_DbTable_Agentes();
        $rsProponente = $tblAgente->buscarAgenteENome($arrBusca)->current();

        if ($rsProponente) {
            $rsProponente = array_change_key_case($rsProponente->toArray());
            //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
            $this->montaTela("manterpropostaincentivofiscal/identificacaodaproposta.phtml", array("proponente" => $rsProponente,
                "acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar"));
        } else {
            $this->_redirect("/agente/manteragentes/agentes");
        }
    }

    /**
     * validaagenciaAction
     *
     * @access public
     * @return void
     */
    public function validaagenciaAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $get = Zend_Registry::get('get');
        $agencia = $get->agencia;

        if ($agencia > 0) {
            $tblProposta = new Proposta_Model_DbTable_PreProjeto();
            $agencia = $tblProposta->buscaragencia($agencia);
            if (count($agencia) > 0) {
                echo "";
            } else {
                echo "Ag&ecirc;ncia inv&aacute;lida";
            }
        } else {
            echo "Ag&ecirc;ncia inv&aacute;lida";
        }
    }

    /**
     * Metodo responsavel por gravar a Proposta (INSERT e UPDATE)
     * @param void
     * @return objeto
     */
    public function salvarAction()
    {

        $post = array_change_key_case($this->getRequest()->getPost());
        if (empty($post['idagente'])) {
            throw new Zend_Exception("Informe o idagente");
        }

        $idPreProjeto = $post['idpreprojeto'];
        $acao = $post['acao'];
        $url = $post['url'];

        if ($acao == 'atualizacao_automatica') {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
            $this->_helper->viewRenderer->setNoRender(true);
        }

        if ($post['dtiniciodeexecucao']) {
            $dtInicioTemp = explode("/", $post['dtiniciodeexecucao']);
            $post['dtiniciodeexecucao'] = $dtInicioTemp[2] . "/" . $dtInicioTemp[1] . "/" . $dtInicioTemp[0] . date(" H:i:s");
        }

        if ($post['dtfinaldeexecucao']) {
            $dtFimTemp = explode("/", $post['dtfinaldeexecucao']);
            $post['dtfinaldeexecucao'] = $dtFimTemp[2] . "/" . $dtFimTemp[1] . "/" . $dtFimTemp[0] . date(" H:i:s");
        }

        if ($post['dtatotombamento']) {
            $dtAtoTombamentoTemp = explode("/", $post['dtatotombamento']);
            $post['dtatotombamento'] = $dtAtoTombamentoTemp[2] . "/" . $dtAtoTombamentoTemp[1] . "/" . $dtAtoTombamentoTemp[0] . date(" H:i:s");
        }

        if ($post['nomeprojeto']) {
            $nomeProjeto = str_replace("'", "", $post['nomeprojeto']);
            $post['nomeprojeto'] = str_replace("\"", "", $nomeProjeto);
        }

        if ($post['resumodoprojeto']) {
            //***NAO TIRAR ESSA QUEBRA DE LINHA - FAZ PARTE DA PROGRAMACAO****
            $post['resumodoprojeto'] = str_replace('
', ' ', str_replace('	', '', str_replace('&nbsp;', '', strip_tags(trim($post['resumodoprojeto'])))));
            //***NAO TIRAR ESSA QUEBRA DE LINHA - FAZ PARTE DA PROGRAMACAO****
        }

        if (!empty($idPreProjeto)) {

            $arrBusca['idPreProjeto = ?'] = $idPreProjeto;

            $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();
            $rsPreProjeto = $tblPreProjeto->buscar($arrBusca)->current();
            $proposta = array_change_key_case($rsPreProjeto->toArray());
            $post = array_intersect_key($post, $proposta) + $proposta;
        }

        $dados = array(
            "idAgente" => isset($post['idagente']) ? $post['idagente'] : null,
            "NomeProjeto" => isset($post['nomeprojeto']) ? $post['nomeprojeto'] : null,
            "Mecanismo" => 1, //seguindo sistema legado
            "AgenciaBancaria" => isset($post['agenciabancaria']) ? $post['agenciabancaria'] : null,
            "AreaAbrangencia" => isset($post['areaabrangencia']) ? $post['areaabrangencia'] : false,
            "DtInicioDeExecucao" => isset($post['dtiniciodeexecucao']) ? $post['dtiniciodeexecucao'] : null,
            "DtFinalDeExecucao" => isset($post['dtfinaldeexecucao']) ? $post['dtfinaldeexecucao'] : null,
            "DtAtoTombamento" => !empty($post['dtatotombamento']) ? $post['dtatotombamento'] : null,
            "NrAtoTombamento" => isset($post['nratotombamento']) ? $post['nratotombamento'] : null,
            "EsferaTombamento" => isset($post['esferatombamento']) ? $post['esferatombamento'] : '0',
            "ResumoDoProjeto" => isset($post['resumodoprojeto']) ? $post['resumodoprojeto'] : null,
            "Objetivos" => isset($post['objetivos']) ? $post['objetivos'] : null,
            "Justificativa" => isset($post['justificativa']) ? $post['justificativa'] : null,
            "Acessibilidade" => isset($post['acessibilidade']) ? $post['acessibilidade'] : null,
            "DemocratizacaoDeAcesso" => isset($post['democratizacaodeacesso']) ? $post['democratizacaodeacesso'] : null,
            "EtapaDeTrabalho" => isset($post['etapadetrabalho']) ? $post['etapadetrabalho'] : null,
            "FichaTecnica" => isset($post['fichatecnica']) ? $post['fichatecnica'] : null,
            "Sinopse" => isset($post['sinopse']) ? $post['sinopse'] : null,
            "ImpactoAmbiental" => isset($post['impactoambiental']) ? $post['impactoambiental'] : null,
            "EspecificacaoTecnica" => isset($post['especificacaotecnica']) ? $post['especificacaotecnica'] : null, //No legado o que esta sendo gravado aqui e OUTRAS INFORMACOES
            "EstrategiadeExecucao" => isset($post['estrategiadeexecucao']) ? $post['estrategiadeexecucao'] : null, //No legado o que esta sendo gravado aqui e ESPECIFICAO TECNICA
            "dtAceite" => isset($post['dtaceite']) ? $post['dtaceite'] : date("Y/m/d H:i:s"), // verificar se realmente eh sempre que salva
            "stEstado" => isset($post['stestado']) ? $post['stestado'] : true,
            "stDataFixa" => isset($post['stdatafixa']) ? $post['stdatafixa'] : false,
            "stProposta" => isset($post['stproposta']) ? $post['stproposta'] : 0,
            "idUsuario" => isset($post['idusuario']) ? $post['idusuario'] : $this->idResponsavel,
            "stTipoDemanda" => "NA", //seguindo sistema legado
            "stPlanoAnual" => true, //seguindo sistema legado
            "tpProrrogacao" => isset($post['tpprorrogacao']) ? $post['tpprorrogacao'] : ''
        );

        $dados['idPreProjeto'] = $idPreProjeto;

        $mesagem = "Cadastro realizado com sucesso!";
        if (!empty($idPreProjeto)) {
            $mesagem = "Altera&ccedil;&atilde;o realizada com sucesso!";
        }

        //instancia classe modelo
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();
        try {

            //persiste os dados do Pre Projeto
            $idPreProjeto = $tblPreProjeto->salvar($dados);
            $this->view->idPreProjeto = $idPreProjeto;

            if ($acao == "incluir") {
                // Salvando os dados na TbVinculoProposta
                $tbVinculoDAO = new Agente_Model_DbTable_TbVinculo();
                $tbVinculoPropostaDAO = new Agente_Model_DbTable_TbVinculoProposta();

                $whereVinculo['idUsuarioResponsavel = ?'] = $this->idResponsavel;
                $whereVinculo['idAgenteProponente   = ?'] = $post['idagente'];
                $vinculo = $tbVinculoDAO->buscar($whereVinculo);

                if (count($vinculo) == 0) {
                    $dadosV = array(
                        'idAgenteProponente' => $post['idagente'],
                        'dtVinculo' => MinC_Db_Expr::date(),
                        'siVinculo' => 2,
                        'idUsuarioResponsavel' => $this->idResponsavel
                    );

                    $insere = $tbVinculoDAO->insert($dadosV);
                }

                $vinculo2 = $tbVinculoDAO->buscar($whereVinculo);
                if (count($vinculo2) > 0) {
                    $vinculo2 = $vinculo2[0]->toArray();
                    $novosDadosV = array(
                        'idVinculo' => $idVinculo = $vinculo2['idVinculo'],
                        'idPreProjeto' => $idPreProjeto,
                        'siVinculoProposta' => 2
                    );
                    $insere = $tbVinculoPropostaDAO->insert($novosDadosV);
                }
                /* **************************************************************************************** */
            }
            // Plano de execu&ccedil;&atilde;o imediata #novain
            if ($post['stproposta'] == '618') { // proposta execucao imediata edital
                $idDocumento = 248;
            } elseif ($post['stproposta'] == '619') { // proposta execucao imediata contrato de patroc&iacute;nio
                $idDocumento = 162;
            }

            if (!empty($idDocumento)) {

                $arrayFile = array(
                    'idPreProjeto' => $idPreProjeto,
                    'documento' => $idDocumento,
                    'tipoDocumento' => 2,
                    'observacao' => ''
                );

                $mapperTbDocumentoAgentes = new Proposta_Model_TbDocumentosAgentesMapper();
                $file = new Zend_File_Transfer();
                $mapperTbDocumentoAgentes->saveCustom($arrayFile, $file);
            }
            if ($acao != 'atualizacao_automatica') {

                if (empty($url))
                    $url = "/proposta/manterpropostaincentivofiscal/identificacaodaproposta/idPreProjeto/" . $idPreProjeto;

                parent::message($mesagem, $url, "CONFIRM");
            }
            return;
        } catch (Zend_Exception $ex) {
            if($idPreProjeto) {
                parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!" . $ex->getMessage(), "/proposta/manterpropostaincentivofiscal/index?idPreProjeto=" . $idPreProjeto, "ERROR");
            } else {
                parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!" . $ex->getMessage(), "/proposta/manterpropostaincentivofiscal/listarproposta", "ERROR");
            }
        }
    }

    /**
     * Metodo responsavel por carregar a proposta na tela apos uma nova proposta ser inclusa
     * @param $idPreProjeto
     * @return objeto
     */
    public function carregaProposta($idPreProjeto)
    {
        $arrBusca = array();
        $arrBusca['idPreProjeto = ?'] = $idPreProjeto;
        $this->view->idPreProjeto = $idPreProjeto;
        // Chama o SQL
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->buscar($arrBusca)->current();

        $arrBuscaProponete = array();
        $arrBuscaProponete['a.idAgente = ?'] = $rsPreProjeto->idAgente;

        $tblAgente = new Agente_Model_DbTable_Agentes();
        $rsProponente = $tblAgente->buscarAgenteENome($arrBuscaProponete)->current();

        $arrDados = array("proposta" => $rsPreProjeto,
            "proponente" => $rsProponente);
        return $arrDados;
        //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMETROS NECESSARIOS DENTRO DO ARRAY
        $this->montaTela("manterpropostaincentivofiscal/identificacaodaproposta.phtml", array("acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar",
            "proposta" => $rsPreProjeto,
            "proponente" => $rsProponente));
    }

    /**
     * Metodo responsavel por carregar os dados da proposta para alteracao
     * @param void
     * @return objeto
     * @deprecated testar proposta e remover em novaIn
     */
//    public function editarAction()
//    {
//        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
//        $this->verificarPermissaoAcesso(true, false, false);
//
//        $idPreProjeto = $this->idPreProjeto;
//
//        $this->redirect("/proposta/manterpropostaincentivofiscal/identificacaodaproposta/idPreProjeto/" . $this->idPreProjeto);
//
//        $this->view->idPreProjeto = $idPreProjeto;
//
//        if (!empty($idPreProjeto)) {
//
//            $arrBusca['idPreProjeto = ?'] = $idPreProjeto;
//
//            $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();
//            $rsPreProjeto = $tblPreProjeto->buscar($arrBusca)->current();
//
//            if ($rsPreProjeto) {
//                $rsPreProjeto = $rsPreProjeto->toArray();
//                $stProposta = $rsPreProjeto["stProposta"];
//            }
//
//            $arrBuscaProponete['a.idAgente = ?'] = $rsPreProjeto['idAgente'];
//            $tblAgente = new Agente_Model_DbTable_Agentes();
//            $rsProponente = $tblAgente->buscarAgenteENome($arrBuscaProponete)->current();
//            if ($rsProponente) {
//                $rsProponente = ($rsProponente->toArray());
//            }
//
//            $ag = new Agente_Model_DbTable_Agentes();
//            $verificarvinculo = $ag->buscarAgenteVinculoProponente(array('vprp.idPreProjeto = ?' => $idPreProjeto, 'vprp.sivinculoproposta = ?' => 2));
//
//            $verificarvinculoCount = $ag->buscarAgenteVinculoProponente(array('vprp.idPreProjeto = ?' => $idPreProjeto))->count();
//
//            if ($verificarvinculoCount > 0) {
//                $this->view->verificarsolicitacaovinculo = true;
//            } else {
//                $this->view->verificarsolicitacaovinculo = false;
//            }
//
//            // I Love you @
//            if (@$verificarvinculo[0]->sivinculo != 2) {
//                $this->view->siVinculoProponente = true;
//            } else {
//                $this->view->siVinculoProponente = false;
//            }
//
//            $idAgente = $this->idResponsavel;
//
//            $tblVinculo = new Agente_Model_DbTable_TbVinculo();
//
//            $arrBuscaP['vp.idPreProjeto = ?'] = $idPreProjeto;
//            $arrBuscaP['vi.idUsuarioResponsavel = ?'] = $this->idResponsavel;
//            $rsVinculoP = $tblVinculo->buscarVinculoProponenteResponsavel($arrBuscaP);
//
//            $arrBuscaN['vi.sivinculo in (0,2)'] = '';
//            $arrBuscaN['vi.idUsuarioResponsavel = ?'] = $this->idResponsavel;
//            $rsVinculoN = $tblVinculo->buscarVinculoProponenteResponsavel($arrBuscaN);
//            //METODO QUE MONTA TELA DO USUARIO ENVIANDO TODOS OS PARAMENTROS NECESSARIO DENTRO DO ARRAY
//
//
//            $idDocumento = "";
//
//            if (!empty($stProposta)) {
//
//                $tbl = new Proposta_Model_DbTable_TbDocumentosPreProjeto();
//
//                // Plano de execu&ccedil;&atilde;o imediata #novain
//                if ($stProposta == '618') { // proposta execucao imediata edital
//                    $idDocumento = 248;
//                } elseif ($stProposta == '619') { // proposta execucao imediata contrato de patroc&iacute;nio
//                    $idDocumento = 162;
//                }
//                if (!empty($idDocumento))
//                    $arquivoExecucaoImediata = $tbl->buscarDocumentos(array("idprojeto = ?" => $idPreProjeto, "CodigoDocumento = ?" => $idDocumento));
//            }
//
//            $this->montaTela(
//                "manterpropostaincentivofiscal/identificacaodaproposta.phtml",
//                array("acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar",
//                    "proposta" => $rsPreProjeto,
//                    "solicitacaovinculo" => $verificarvinculo,
//                    "idResponsavel" => $idAgente,
//                    "dadosVinculo" => $rsVinculoP,
//                    "listaProponentes" => $rsVinculoN,
//                    "idPreProjeto" => $idPreProjeto,
//                    "arquivoExecucaoImediata" => $arquivoExecucaoImediata,
//                    "proponente" => $rsProponente)
//            );
//        } else {
//            //chama o metodo index
//            $this->_forward("index", "manterpropostaincentivofiscal", 'proposta');
//        }
//    }

    public function identificacaodapropostaAction()
    {

        if (empty($this->_proposta["idpreprojeto"])) {

            $post = Zend_Registry::get('post');

            $arrBusca = array();
            if (empty($post->idAgente)) {
                parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!", "/proposta/manterpropostaincentivofiscal/listarproposta", "ERROR");
            }

            $arrBusca['a.idAgente = ?'] = $post->idAgente;
            $tblAgente = new Agente_Model_DbTable_Agentes();
            $rsProponente = $tblAgente->buscarAgenteENome($arrBusca)->current();

            if ($rsProponente) {
                $rsProponente = array_change_key_case($rsProponente->toArray());

                $this->view->proponente = $rsProponente;

                $this->montaTela("manterpropostaincentivofiscal/identificacaodaproposta.phtml", array("proponente" => $rsProponente,
                    "acao" => $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/salvar"));
            }

        } else {

            $tbl = new Proposta_Model_DbTable_TbDocumentosPreProjeto();

            if ($this->_proposta["stproposta"] == Proposta_Model_Verificacao::PROPOSTA_APROVADA_EM_EDITAIS) {
                $codigoDocumento = Proposta_Model_DocumentosExigidos::RESULTADO_DA_SELECAO_PUBLICA;
            } elseif ($this->_proposta["stproposta"] == Proposta_Model_Verificacao::PROPOSTA_COM_CONTRATOS_DE_PATROCINIOS) {
                $codigoDocumento = Proposta_Model_DocumentosExigidos::CONTRATO_FIRMADO_COM_INCENTIVADOR;
            }

            if (!empty($codigoDocumento)) {
                $arquivoExecucaoImediata = $tbl->buscarDocumentos(array("idProjeto = ?" => $this->idPreProjeto, "CodigoDocumento = ?" => $codigoDocumento));
            }

            $this->view->arquivoExecucaoImediata = $arquivoExecucaoImediata;
        }


        if ($this->isEditarProjeto($this->idPreProjeto)) {

            $tblProjetos = new Projetos();
            $projeto = $tblProjetos->findBy(array('idProjeto = ?' => $this->idPreProjeto));

            if (!empty($projeto['IdPRONAC'])) {
                $projeto2 = ConsultarDadosProjetoDAO::obterDadosProjeto(array('idPronac' => (int)$projeto['IdPRONAC']));

                $this->view->projeto = array_change_key_case((array)$projeto2[0]);
            }

            $planilhaproposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
            $fonteincentivo = $planilhaproposta->somarPlanilhaProposta($this->idPreProjeto, 109);
            $outrasfontes = $planilhaproposta->somarPlanilhaProposta($this->idPreProjeto, false, 109);

            $this->view->valorsolicitadoincentivo = !empty($fonteincentivo['soma']) ? $fonteincentivo['soma'] : 0;
            $this->view->valoroutrasfontes = !empty($outrasfontes['soma']) ? $outrasfontes['soma'] : 0;
        }

    }

    public function responsabilidadesocialAction()
    {

    }

    public function detalhestecnicosAction()
    {

    }

    public function outrasinformacoesAction()
    {

    }

    /**
     * Encaminhar projeto ao MinC
     *
     * Metódo para o proponente finalizar a situa&ccedil;&atilde;o do projeto, nem sempre este metódo ser&aacute; acionado,
     * tendo em vista que existe uma rotina no banco para alterar a situacao do projeto após o prazo de alteracao.
     *
     * Regras antes de encaminhar
     * 1. Validar o checklist da proposta
     *
     * Regras ao encaminhar
     * Quando o proponente clicar na op&ccedil;&atilde;o Encaminhar projeto ao MinC, o sistema dever&aacute; a alterar situa&ccedil;&atilde;o do projeto para B20,
     * com a seguinte providencia tomada: Projeto ajustado pelo proponente e encaminhado ao MinC para avalia&ccedil;&atilde;o.
     *
     *
     */
    public function encaminharprojetoaomincAction()
    {
        $this->verificarPermissaoAcesso(true, false, false);

        $params = $this->getRequest()->getParams();

        $idPreProjeto = $params['idPreProjeto'];

        if (empty($idPreProjeto)) {
            parent::message("Necess&aacute;rio informar o n&uacute;mero da proposta.", "/proposta/manterpropostaincentivofiscal/listarproposta", "ERROR");
        }

        $validacao = new stdClass();
        $listaValidacao = array();

        $sp = new Proposta_Model_DbTable_PreProjeto();

        # verifica se existe alguma pendencia no checklist de proposta
        $validaProposta = $this->validarEnvioPropostaComSp($idPreProjeto);

        $pendencias = in_array('PENDENTE', array_column(converterObjetosParaArray($validaProposta), 'Observacao'));

        if ($pendencias) {
            $this->view->resultado = $validaProposta;
        } else {

            $tblProjetos = new Projetos();
            $projeto = array_change_key_case($tblProjetos->findBy(array('idprojeto = ?' => $idPreProjeto)));
            $idPronac = $projeto['idpronac'];

            $planilhaproposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
            $ValorTotalPlanilha = $planilhaproposta->somarPlanilhaProposta($idPreProjeto)->toArray();

            # validar valor original e valor total atual da proposta
            if ($ValorTotalPlanilha['soma'] > $projeto['solicitadoreal']) {
                $validacao->dsInconsistencia = 'O valor total do projeto n&atilde;o pode ultrapassar o valor anteriormente solicitado!';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'manterorcamento', 'action' => 'produtoscadastrados', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            $validado = true;
            foreach ($listaValidacao as $valido) {
                if ($valido->Observacao == 'PENDENTE') {
                    $validado = false;
                    break;
                }
            }

            if ($params['confirmarenvioaominc'] == true) {
                if ($validado) {

                    if ($projeto['Area'] == 2) {
                        $orgaoUsuario = 171; # 171 - SAV/DAP
                    } else {
                        $orgaoUsuario = 262; # 262 - SEFIC/DIAAPI
                    }

                    # verificar se o projeto j&aacute; possui avaliador
                    $tbAvaliacao = new Analise_Model_DbTable_TbAvaliarAdequacaoProjeto();
                    $avaliacao = $tbAvaliacao->buscarUltimaAvaliacao($idPronac);

                    if (!empty($avaliacao)) {
                        $tbAvaliacao->inserirAvaliacao($idPronac, $orgaoUsuario, $avaliacao['idTecnico']);
                    } else {
                        $tbAvaliacao->inserirAvaliacao($idPronac, $orgaoUsuario);
                    }

                    # alterar a situacao do projeto
                    $codigoSituacao = 'B20'; #B20 - Projeto adequado a realidade de execucao
                    $providenciaTomada = "Projeto ajustado pelo proponente e encaminhado ao MinC para avalia&ccedil;&atilde;o";

                    $tblProjetos->alterarSituacao($idPronac, '', $codigoSituacao, $providenciaTomada);

                    parent::message("Projeto encaminhado com sucesso para an&aacute;lise no Minist&eacute;rio da Cultura.", "/listarprojetos/listarprojetos", "CONFIRM");
                } else {
                    parent::message("Alguns erros encontrados no envio do projeto", "/proposta/manterpropostaincentivofiscal/encaminharprojetoaominc/idPreProjeto/" . $idPreProjeto, "ERROR");
                }
            }
            $this->view->resultado = $listaValidacao;
            $this->view->acao = $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/encaminharprojetoaominc";
        }
    }

    public function inativarPropostaAction()
    {
        $this->verificarPermissaoDeAcessoProponenteAPropostaOuProjeto();

        $get = Zend_Registry::get("get");
        $idPreProjeto = $get->idPreProjeto;
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();
        $rsPreProjeto = $tblPreProjeto->buscar(['idPreProjeto = ?' => $idPreProjeto])->current()->toArray();
        $preProjetoModel = new Proposta_Model_PreProjeto($rsPreProjeto);
        $preProjetoModel->stEstado = 0;
        $preProjetoMapper = new Proposta_Model_PreProjetoMapper();

        if ($preProjetoMapper->save($preProjetoModel)) {
            parent::message("Exclus&atilde;o realizada com sucesso!", "/proposta/manterpropostaincentivofiscal/listarproposta", "CONFIRM");
        }
        parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!", "/proposta/manterpropostaincentivofiscal/listarproposta", "ERROR");
    }

    private function verificarPermissaoDeAcessoProponenteAPropostaOuProjeto () {
        $this->verificarPermissaoAcesso(true, false, false);
    }

    public function enviarPropostaAction()
    {
        $arrResultado = array();

        $this->verificarPermissaoAcesso(true, false, false);
        $params = $this->getRequest()->getParams();

        $idPreProjeto = $this->getRequest()->getParam('idPreProjeto');

        try {

            if (empty($idPreProjeto)) {
                throw new Exception("&Eacute; necess&aacute;rio informar o n&uacute;mero da proposta.");
            }

            $tbPreProjeto = new Proposta_Model_DbTable_PreProjeto();

            if (!$tbPreProjeto->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {

                $arrResultado = $this->validarEnvioPropostaSemSp($idPreProjeto);
            } else {
                $arrResultado = $this->validarEnvioPropostaComSp($idPreProjeto);
            }

            if ($params['confirmarenvioaominc'] == true && $arrResultado->Observacao === true) {

                $proposta = $tbPreProjeto->findBy(['idPreProjeto' => $idPreProjeto]);

                $dadosMovimentacaoProposta = array(
                    'idProjeto' => $idPreProjeto,
                    'Movimentacao' => 96,
                    'DtMovimentacao' => MinC_Db_Expr::date(),
                    'stEstado' => 1,
                    'Usuario' => $proposta['idUsuario']
                );

                $tbAvaliacaoProposta = new Admissibilidade_Model_DbTable_TbAvaliacaoProposta();
                $propostaJaAvaliada = $tbAvaliacaoProposta->findBy(['idProjeto' => $idPreProjeto]);

                if (empty($propostaJaAvaliada)) {

                    $orgaoSuperior = self::obterOrgaoSuperiorPelaAreaAbrangencia($proposta['AreaAbrangencia']);
                    $tbUsuariosOrgaosGrupos = new Usuariosorgaosgrupos();
                    $whereTecnicoAdmissibilidade = [
                        's.sis_codigo = ?' => 21,
                        'g.gru_codigo = ?' => 92,
                        'o.org_superior = ?' => $orgaoSuperior,
                        'ug.uog_status = ?' => 1
                    ];

                    $tecnicoAdmissiblidade = $tbUsuariosOrgaosGrupos->obterTecnicoComMenosProjetosParaAnaliseMesAtual($whereTecnicoAdmissibilidade);

                    if (empty($tecnicoAdmissiblidade)) {
                        throw new Exception("Nenhum t&eacute;cnico encontrado, n&atilde;o foi poss&iacute;vel distribuir an&aacute;lise. Proposta não encaminhada!");
                    }

                    $dadosPropostaParaAvaliacao = [
                        'idProjeto' => $idPreProjeto,
                        'idTecnico' => $tecnicoAdmissiblidade->usu_codigo,
                        'DtEnvio' => MinC_Db_Expr::date(),
                        'ConformidadeOK' => 9,
                        'stEstado' => 'f'
                    ];

                    $tbAvaliacaoProposta->insert($dadosPropostaParaAvaliacao);

                    $dadosMovimentacaoProposta['stEstado'] = 0;
                }

                $tbMovimentacao = new Proposta_Model_DbTable_TbMovimentacao();
                $insert = $tbMovimentacao->insert($dadosMovimentacaoProposta);

                parent::message("Proposta encaminhada com sucesso para an&aacute;lise no Minist&eacute;rio da Cultura.", "/proposta/manterpropostaincentivofiscal/identificacaodaproposta/idPreProjeto/" . $idPreProjeto, "CONFIRM");

            } else {
                $this->view->resultado = $arrResultado;
            }

            $this->view->acao = $this->_urlPadrao . "/proposta/manterpropostaincentivofiscal/enviar-proposta/idPreProjeto/" . $this->idPreProjeto;


        } catch (Exception $e) {
            parent::message($e->getMessage(), "/proposta/manterpropostaincentivofiscal/identificacaodaproposta/idPreProjeto/" . $this->idPreProjeto, "ERROR");
        }

    }

    /**
     * validarEnvioPropostaComSp
     *
     * @param mixed $idPreProjeto
     * @access public
     * @return void
     */
    public function validarEnvioPropostaComSp($idPreProjeto)
    {
        try {
            $validacao = new stdClass();

            $this->atualizarDadosPessoaJuridicaVerificandoCNAECultural($idPreProjeto);

            $tbPreProjeto = new Proposta_Model_DbTable_PreProjeto();
            $arrResultado = $tbPreProjeto->spChecklistParaApresentacaoDeProposta($idPreProjeto);

            $validado = true;
            foreach ($arrResultado as $item) {
                if ($item->Observacao == 'PENDENTE') {
                    $validado = false;
                    break;
                }
            }

            if ($validado) {
                $validacao->dsInconsistencia = 'A proposta cultural n&atilde;o possui pend&ecirc;ncias';
                $validacao->Observacao = true;
                $validacao->Url = '';
                return $validacao;
            }

            return $arrResultado;

        } catch (Zend_Exception $ex) {
            parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o! (comsp)" . $ex->getMessage(), "/proposta/manterpropostaincentivofiscal/index?idPreProjeto=" . $idPreProjeto, "ERROR");
        }

    }

    public function validarEnvioPropostaSemSp($idPreProjeto)
    {
        try {

            $tbPreProjeto = new Proposta_Model_DbTable_PreProjeto();
            return $tbPreProjeto->checklistEnvioPropostaSemSp($idPreProjeto);

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function obterOrgaoSuperiorPelaAreaAbrangencia($areaAbrangencia)
    {
        $orgaoSuperior = Orgaos::ORGAO_SUPERIOR_SAV;

        if ($areaAbrangencia == 0) {
            $orgaoSuperior =  Orgaos::ORGAO_SUPERIOR_SEFIC;
        }

        return $orgaoSuperior;

    }

    private function obterTecnicoParaAnaliseDaProposta() {

    }

    public function confirmarEnvioPropostaAoMincAction()
    {

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);

    }

    /**
     * Metodo responsavel por validar as datas do formulario
     * @param void
     * @return objeto
     */
    public function validaDatasAction()
    {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        //recupera parametros
        $get = Zend_Registry::get('get');
        $dtInicio = $get->dtInicio;
        $dtFim = $get->dtFim;

        $bln = "true";
        $script = "";
        $mensagem = "";

        $objData = new Data();

        //VERIFICA SE DATA INICIO E MAIOR QUE DATA FINAL
        if (!empty($get->dtInicio) && !empty($get->dtFim) && strlen($get->dtInicio) == 10 && strlen($get->dtFim) == 10) {

            $dtTemp = explode("/", $get->dtInicio);
            $dtInicio = $dtTemp[2] . $dtTemp[1] . $dtTemp[0];

            $dtTemp = null;
            $dtTemp = explode("/", $get->dtFim);
            $dtFim = $dtTemp[2] . $dtTemp[1] . $dtTemp[0];

            if ($dtInicio > $dtFim) {
                $mensagem = "<br><font color='red'>Data de in&iacute;cio n&atilde;o pode ser maior que a data final</font>";
                $bln = "false";
            }
            if (!$objData->validarData($get->dtInicio)) {
                $mensagem = "<br><font color='red'>Data de in&iacute;cio inv&aacute;lida</font>";
                $bln = "false";
            }
            if (!$objData->validarData($get->dtFim)) {
                $mensagem = "<br><font color='red'>Data final inv&aacute;lida</font>";
                $bln = "false";
            }
            if (!$objData->validarData($get->dtInicio)) {
                $mensagem = "<br><font color='red'>O per&iacute;odo de execu&ccedil;&atilde;o de projetos de plano anual dever ser posterior ao ano vigente</font>";
                $bln = "false";
            }

        }

        //VERIFICA SE DATA INICIO E MAIOR QUE 90 DIAS DA DATA ATUAL
        if (!empty($get->dtInicio) && strlen($get->dtInicio) == 10) {
            $dtTemp = explode("/", $get->dtInicio);
            $dtInicio = $dtTemp[2] . $dtTemp[1] . $dtTemp[0];

            $diffEmDias = $objData->CompararDatas(date("Ymd"), $dtInicio);
            if ($diffEmDias < 0 || $diffEmDias < 90) {
                $mensagem = "<br><font color='red'>A data inicial de realiza&ccedil;&atilde;o dever&aacute; ser no m&iacute;nimo 90 dias ap&oacute;s a data atual.</font>";
                $bln = "false";
            }

            if (!$objData->validarData($get->dtInicio)) {
                $mensagem = "<br><font color='red'>Data de in&iacute;cio inv&aacute;lida</font>";
                $bln = "false";
            }
            //verifica se a data inicio esta entre 01 de Fevereiro e 30 de Novembro
            //if($dtInicio >= date("Y")."0201" && $dtInicio <= date("Y")."1130"){
        }

        //VERIFICA SE DATA DO ATO E VALIDA, CASO ELA TENHA SIDO INFORMADA
        if (!empty($get->dtAto) && strlen($get->dtAto) == 10) {
            if (!$objData->validarData(trim($get->dtAto))) {
                $mensagem = "<br><font color='red'>Data tombamento inv&aacute;lida</font>";
                $bln = "false";
            }
        }


        $script = "\$('#blnDatasValidas').val(" . $bln . ");\n";
        $this->montaTela("manterpropostaincentivofiscal/mensagem.phtml", array("mensagem" => $mensagem,
            "script" => $script));
    }

    public function listarpropostaAction()
    {

        $proposta = new Proposta_Model_DbTable_PreProjeto();
        $dadosCombo = array();
        $cpfCnpj = '';
        $rsVinculo = ($this->idResponsavel) ? $proposta->listarPropostasCombo($this->idResponsavel) : array();

        $agente = array();

        $i = 0;
        foreach ($rsVinculo as $rs) {
            $cpfCnpj = Mascara::addMaskCPF($rs->CNPJCPF);
            if (strlen(trim($rs->CNPJCPF)) > 11) {
                $cpfCnpj = Mascara::addMaskCNPJ($rs->CNPJCPF);
            }

            $dadosCombo[$i]['idAgenteProponente'] = $rs->idAgente;
            $dadosCombo[$i]['CPF'] = $cpfCnpj;
            $dadosCombo[$i]['Nome'] = $rs->nomeproponente;

            $i++;
        }

        $this->view->dadosCombo = $dadosCombo;
        $this->view->idResponsavel = $this->idResponsavel;
        $this->view->idUsuario = $this->idUsuario;
    }

    public function listarPropostasAjaxAction()
    {
        $idAgente = $this->getRequest()->getParam('idAgente');
        $start = $this->getRequest()->getParam('start');
        $length = $this->getRequest()->getParam('length');
        $draw = (int)$this->getRequest()->getParam('draw');
        $search = $this->getRequest()->getParam('search');
        $order = $this->getRequest()->getParam('order');
        $columns = $this->getRequest()->getParam('columns');
        if($order) {
            $order = ($order[0]['dir'] != 1) ? array($columns[$order[0]['column']]['name'] . ' ' . $order[0]['dir']) : array("idPreProjeto DESC");
        }

        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();

        $rsPreProjeto = $tblPreProjeto->propostas($this->idAgente, $this->idResponsavel, $idAgente, array(), $order, $start, $length, $search);
        $Movimentacao = new Proposta_Model_DbTable_TbMovimentacao();

        $recordsTotal = 0;
        $recordsFiltered = 0;
        $aux = array();
        if (!empty($rsPreProjeto)) {
            foreach ($rsPreProjeto as $key => $proposta) {
                $proposta->nomeproponente = utf8_encode($proposta->nomeproponente);
                $proposta->nomeprojeto = utf8_encode($proposta->nomeprojeto);
                $rsStatusAtual = $Movimentacao->buscarStatusPropostaNome($proposta->idPreProjeto);
                $proposta->situacao = isset($rsStatusAtual['MovimentacaoNome']) ? utf8_encode($rsStatusAtual['MovimentacaoNome']) : '';

                $aux[$key] = $proposta;
            }
            $recordsFiltered = $tblPreProjeto->propostasTotal($this->idAgente, $this->idResponsavel, $idAgente, array(), null, null, null, $search);
            $recordsTotal = $tblPreProjeto->propostasTotal($this->idAgente, $this->idResponsavel, $idAgente);
        }

        $this->_helper->json(array(
            "data" => !empty($aux) ? $aux : 0,
            'recordsTotal' => $recordsTotal ? $recordsTotal : 0,
            'draw' => $draw,
            'recordsFiltered' => $recordsFiltered ? $recordsFiltered : 0));
    }

    /**
     * Metodo consultarresponsaveis()
     * UC 89 - Fluxo FA2 - Aceitar Vinculo
     * @access public
     * @param void
     * @return void
     */
    public function consultarresponsaveisAction()
    {
        $auth = Zend_Auth::getInstance();
        $arrAuth = array_change_key_case((array)$auth->getIdentity()); // pega a autentica&ccedil;&atilde;o

        $idUsuario = $arrAuth['idusuario'];
        $cpf = $arrAuth['cpf'];

        $tblAgentes = new Agente_Model_DbTable_Agentes();
        $buscarpendentes = $tblAgentes->gerenciarResponsaveisListas('0', $idUsuario);
        $buscarvinculados = $tblAgentes->gerenciarResponsaveisListas('2', $idUsuario);

        $this->view->pendentes = $buscarpendentes;
        $this->view->vinculados = $buscarvinculados;
        $this->view->cpfLogado = $cpf;
    }

    /**
     * Metodo vincularpropostas()
     * UC 89 - Fluxo FA6 - Vincular Propostas
     * @access public
     * @param void
     * @return void
     */
    public function vincularpropostasAction()
    {
        $tbVinculo = new Agente_Model_DbTable_TbVinculo();
        $propostas = new Proposta_Model_DbTable_PreProjeto();

        $tblAgentes = new Agente_Model_DbTable_Agentes();
        $dadosCombo = array();
        $rsVinculo = $tblAgentes->listarVincularPropostaCombo($this->idResponsavel);

        $i = 0;

        foreach ($rsVinculo as $rs) {
            $dadosCombo[$i]['idResponsavel'] = $rs->idUsuarioResponsavel;
            $dadosCombo[$i]['idVinculo'] = $rs->idVinculo;
            $dadosCombo[$i]['NomeResponsavel'] = $rs->NomeResponsavel;
            $i++;
        }

        //ADICIONA AO ARRAY O IDAGENTE DO USUARIO LOGADO
        $dadosIdAgentes = array($this->idAgenteProponente);

        //VERIFICA SE O USUARIO LOGADO EH DIRIGENTE DE ALGUMA EMPRESA
        $Vinculacao = new Agente_Model_DbTable_Vinculacao();
        $rsVinculucao = $Vinculacao->verificarDirigenteIdAgentes($this->cpfLogado);

        //CASO RETORNE ALGUM RESULTADO, ADICIONA OS IDAGENTE'S DE CADA UM AO ARRAY
        if (count($rsVinculucao) > 0) {
            foreach ($rsVinculucao as $value) {
                $dadosIdAgentes[] = $value->idAgente;
            }
        }

        //PROCURA AS PROPOSTAS DE TODOS OS IDAGENTE'S
        $listaPropostas = $propostas->buscarVinculadosProponenteDirigentes($dadosIdAgentes);

        $wherePropostaD['pp.idAgente = ?'] = $this->idAgenteProponente;
        $wherePropostaD['"pr"."idProjeto" IS NULL'] = '';
//        $wherePropostaD['pp.idUsuario <> ?'] = $this->idResponsavel;
        $listaPropostasD = $propostas->buscarPropostaProjetos($wherePropostaD);

        $this->view->responsaveis = $dadosCombo;
        $this->view->propostas = $listaPropostas;
        $this->view->propostasD = $listaPropostasD;
    }

    /**
     * Metodo vincularpropostas()
     * UC 89 - Fluxo FA8 - Vincular Propostas
     */
    public function vincularprojetosAction()
    {
        $tbVinculo = new Agente_Model_DbTable_TbVinculo();
        $propostas = new Proposta_Model_DbTable_PreProjeto();

        $whereProjetos['pp.idAgente = ?'] = $this->idAgenteProponente;
        $whereProjetos['pp.idUsuario <> ?'] = $this->idResponsavel;
        $whereProjetos['pr.idProjeto IS NOT NULL'] = '';
        $listaProjetos = $propostas->buscarPropostaProjetos($whereProjetos);

        $this->view->projetos = $listaProjetos;
    }

    /**
     * UC 89 - Fluxo FA4 - Vincular Responsï¿½vel
     */
    public function novoresponsavelAction()
    {

    }

    /**
     * UC 89 - Fluxo FA4 - Vincular Responsaveis
     * @todo retirar html
     */
    public function respnovoresponsavelAction()
    {

        $this->_helper->layout->disableLayout();

        $cnpjcpf = Mascara::delMaskCPF($this->_request->getParam("cnpjcpf"));
        $nome = $this->_request->getParam("nome");

        $tbVinculo = new Agente_Model_DbTable_TbVinculo();

        if ((empty($cnpjcpf)) && (empty($nome))) {
            echo "<table class='tabela'>
                    <tr>
                        <td class='red' align='center'>Voc&eacute; deve preencher pelo menos um campo!</td>
                    </tr>
                </table>";
            $this->_helper->viewRenderer->setNoRender(TRUE);
        } elseif (!empty($cnpjcpf)) {
            $where['SGA.Cpf = ?'] = $cnpjcpf;
        } elseif (!empty($nome)) {
            $where['SGA.Nome like ?'] = "%{$nome}%";
        }

        $busca = $tbVinculo->buscarResponsaveis($where, $this->idAgenteProponente);

        $this->view->dados = $busca;
        $this->view->dadoscount = count($busca);
        $this->view->idAgenteProponente = $this->idAgenteProponente;
    }

    /**
     * Este metodo deve estar igual &agrave; regra de negocio 1.3 da spCheckListParaApresentacaoDeProposta
     */
    public function atualizarDadosPessoaJuridicaVerificandoCNAECultural($idPreProjeto)
    {

        $TbPreProjeto = new Proposta_Model_DbTable_PreProjeto();
        $proponente = $TbPreProjeto->buscarProponenteProposta($idPreProjeto);

        $tbDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
        $produtoPrincipal = $tbDistribuicao->findBy(array('idProjeto' => $idPreProjeto, 'stPrincipal' => 1));

        $segmentosIsentos = array('4D', '5A', '5D', '5E', '5S', '6I');

        if (isCnpjValid($proponente->CNPJCPF) && isset($produtoPrincipal['Segmento'])) {

            if (!in_array($produtoPrincipal['Segmento'], $segmentosIsentos)) {

                $cnae = $TbPreProjeto->verificarCNAEProponenteComProdutoPrincipal($idPreProjeto);

                # Se o CNAE estiver vazio, for&ccedil;ar atualiza&ccedil;&atilde;o do proponente com os dados do webservice da receita
                if (empty($cnae)) {
                    $servicoReceita = new ServicosReceitaFederal();
                    $dadosPessoaJuridica = $servicoReceita->consultarPessoaJuridicaReceitaFederal($proponente->CNPJCPF, true);
                    return $dadosPessoaJuridica;
                }
                return false;
            }
            return true;
        }
        return true; #pf
    }

}