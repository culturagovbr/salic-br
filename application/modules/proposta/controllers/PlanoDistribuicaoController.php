<?php

class Proposta_PlanoDistribuicaoController extends Proposta_GenericController
{
    private $intTamPag;
    private $_idPreProjeto = null;

    public function init()
    {
        $idPreProjeto = $this->getRequest()->getParam('idPreProjeto');
        $auth = Zend_Auth::getInstance(); // instancia da autentica��o
        $PermissoesGrupo = array();

        //Da permissao de acesso a todos os grupos do usuario logado afim de atender o UC75
        if (isset($auth->getIdentity()->usu_codigo)) {
            //Recupera todos os grupos do Usuario
            $Usuario = new Autenticacao_Model_Usuario(); // objeto usu�rio
            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
            foreach ($grupos as $grupo) {
                $PermissoesGrupo[] = $grupo->gru_codigo;
            }
        }

        isset($auth->getIdentity()->usu_codigo) ? parent::perfil(1, $PermissoesGrupo) : parent::perfil(4, $PermissoesGrupo);

        // inicializando variaveis com valor padrao
        $this->intTamPag = 10;

        if (!empty ($idPreProjeto)) {
            $this->_idPreProjeto = $idPreProjeto;
            $this->view->idPreProjeto = $idPreProjeto;

            //VERIFICA SE A PROPOSTA ESTA COM O MINC
            $Movimentacao = new Proposta_Model_DbTable_TbMovimentacao();
            $rsStatusAtual = $Movimentacao->buscarStatusAtualProposta($idPreProjeto);
            $this->view->movimentacaoAtual = isset($rsStatusAtual['Movimentacao']) ? $rsStatusAtual['Movimentacao'] : '';
        } else {
            if ($idPreProjeto != '0') {
                parent::message("Necess&aacute;rio informar o n&uacute;mero da proposta.", "/manterpropostaincentivofiscal/index", "ERROR");
            }
        }

        parent::init();

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);
    }

    public function indexAction()
    {
        $this->view->localRealizacao = true;

        $arrBusca = array();
        $arrBusca['idProjeto'] = $this->_idPreProjeto;
        $arrBusca['stAbrangencia'] = true;
        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);

        if (empty($rsAbrangencia)) {
            $this->view->localRealizacao = false;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        if (isset($get->tamPag)) $this->intTamPag = $get->tamPag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;
        $fim = $inicio + $this->intTamPag;
        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $total = $tblPlanoDistribuicao->pegaTotal(
            array(
                "a.idProjeto = ?" => $this->_idPreProjeto,
                "a.stPlanoDistribuicaoProduto = ?" => 'true'
            )
        );
        $tamanho = (($inicio + $this->intTamPag) <= $total) ? $this->intTamPag : $total - ($inicio);
        $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(
            array(
                "a.idProjeto = ?" => $this->_idPreProjeto,
                "a.stPlanoDistribuicaoProduto = ?" => 'true'
            ),
            array("idPlanoDistribuicao DESC"),
            $tamanho,
            $inicio
        );


        if ($fim > $total) $fim = $total;
        $totalPag = (int)(($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $arrDados = array(
            "pag" => $pag,
            "total" => $total,
            "inicio" => ($inicio + 1),
            "fim" => $fim,
            "totalPag" => $totalPag,
            "planosDistribuicao" => ($rsPlanoDistribuicao),
            "formulario" => $this->_urlPadrao . "/proposta/plano-distribuicao/frm-plano-distribuicao?idPreProjeto=" . $this->_idPreProjeto,
            "urlApagar" => $this->_urlPadrao . "/proposta/plano-distribuicao/apagar?idPreProjeto=" . $this->_idPreProjeto,
            "urlPaginacao" => $this->_urlPadrao . "/prosposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto
        );

        $this->view->idPreProjeto = $this->_idPreProjeto;
        $this->view->isEditavel = $this->isEditavel($this->_idPreProjeto);
        $this->montaTela("planodistribuicao/index.phtml", $arrDados);
    }

    public function consultarComponenteAction()
    {
        $get = Zend_Registry::get("get");
        $idPreProjeto = $get->idPreProjeto;
        $this->_helper->layout->disableLayout(); // desabilita o layout

        if (!empty($idPreProjeto) || $idPreProjeto == '0') {
            $tblPlanoDistribuicao = new PlanoDistribuicao();
            $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(array("idProjeto=?" => $idPreProjeto, "stPlanoDistribuicaoProduto=?" => 1), array("idPlanoDistribuicao DESC"), 10);
            $arrDados = array("planosDistribuicao" => $rsPlanoDistribuicao);
            $this->montaTela("planodistribuicao/consultar-componente.phtml", $arrDados);
        } else {
            return false;
        }
    }

    public function frmPlanoDistribuicaoAction()
    {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $bln_exitePP = "false"; //Nao existe Produto Principal cadastrado

        $get = Zend_Registry::get("get");
        if (!empty($get->idPlanoDistribuicao)) {
            $tblPlanoDistribuicao = new PlanoDistribuicao();
            $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscarPlanoDistribuicao(array('idPlanoDistribuicao = ?' => $get->idPlanoDistribuicao));
            $arrDados["planoDistribuicao"] = $rsPlanoDistribuicao;
        }

        $tblProduto = new Produto();
        $rsProdutos = $tblProduto->buscar(array("stEstado = ?" => 'f'), array("Descricao ASC"));


        //BUSCA POR PRODUTO PRINCIPAL CADASTRADO
        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $arrPlanoDistribuicao = $tblPlanoDistribuicao->buscar(array(
            "a.idProjeto = ?" => $this->_idPreProjeto,
            "a.stPrincipal = ?" => 't',
            "a.stPlanoDistribuicaoProduto = ?" => 't'), array("idPlanoDistribuicao DESC"))
            ->toArray();

        if (!empty($arrPlanoDistribuicao)) {
            $bln_exitePP = "true"; //Existe Produto Principal Cadastrado

            $tblSegmento = new Segmento();
            $arrDados["Segmento"] = $tblSegmento->buscar(array("Codigo=?" => $arrPlanoDistribuicao[0]['Segmento']));
        }

        $arrDados["comboprodutos"] = $rsProdutos;
        $manterAgentes = new ManterAgentes();
        $arrDados["comboareasculturais"] = $manterAgentes->listarAreasCulturais();

        $arrDados["acaoSalvar"] = $this->_urlPadrao . "/proposta/plano-distribuicao/salvar?idPreProjeto=" . $this->_idPreProjeto;
        $arrDados["urlApagar"] = $this->_urlPadrao . "/proposta/plano-distribuicao/apagar?idPreProjeto=" . $this->_idPreProjeto;
        $arrDados["acaoCancelar"] = $this->_urlPadrao . "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto;
        $arrDados["bln_exitePP"] = $bln_exitePP;
        $this->montaTela("planodistribuicao/formplanodistribuicao.phtml", $arrDados);
    }

    public function salvarAction()
    {

        $post = Zend_Registry::get("post");
        if (($this->isEditarProjeto($this->_idPreProjeto) && $post->prodprincipal == 1))
            parent::message("Em alterar projeto, n&atilde;o pode alterar o produto principal cadastrado. A opera&ccedil;&atilde;o foi cancelada.", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");

        $precopromocional = str_replace(",", ".", str_replace(".", "", $post->precopromocional));
        $preconormal = str_replace(",", ".", str_replace(".", "", $post->preconormal));
        $QtdeProduzida = $post->qtdenormal + $post->qtdepromocional + $post->patrocinador + $post->beneficiarios + $post->divulgacao;
        $dados = array(
            "Area" => $post->areaCultural,
            "idProjeto" => $this->_idPreProjeto,
            "idProduto" => $post->produto,
//                 "idPosicaoDaLogo"=>$post->logomarca,
            "Segmento" => $post->segmentoCultural,
            "QtdeProduzida" => $QtdeProduzida,
            "QtdeVendaNormal" => $post->qtdenormal,
            "QtdeVendaPromocional" => $post->qtdepromocional,
            "dsJustificativaPosicaoLogo" => $post->dsJustificativaPosicaoLogo,
            "PrecoUnitarioNormal" => $preconormal,
            "PrecoUnitarioPromocional" => $precopromocional,
            "stPrincipal" => $post->prodprincipal,
            "Usuario" => $this->_SGCacesso['IdUsuario']
        );
        if (isset($post->idPlanoDistribuicao)) {
            $dados["idPlanoDistribuicao"] = $post->idPlanoDistribuicao;
        }
        if (isset($post->patrocinador)) {
            $dados["QtdePatrocinador"] = $post->patrocinador;
        }
        if (isset($post->divulgacao)) {
            $dados["QtdeProponente"] = $post->divulgacao;
        }
        if (isset($post->beneficiarios)) {
            $dados["QtdeOutros"] = $post->beneficiarios;
        }
        $dados["stPlanoDistribuicaoProduto"] = true;

        $tblPlanoDistribuicao = new PlanoDistribuicao();

        //VERIFICA SE JA EXISTE PRODUTO PRINCIPAL JA CADASTRADO
        $arrBusca = array();
        $arrBusca['a.idProjeto = ?'] = $this->_idPreProjeto;
        $arrBusca['a.stPrincipal = ?'] = true;
        !empty($post->idPlanoDistribuicao) ? $arrBusca['idPlanoDistribuicao <> ?'] = $post->idPlanoDistribuicao : '';
        //$arrBusca['idPlanoDistribuicao <> ?'] = $post->idPlanoDistribuicao;
        $arrBusca['stPlanoDistribuicaoProduto = ?'] = true;
        $arrPlanoDistribuicao = $tblPlanoDistribuicao->buscar($arrBusca, array("idPlanoDistribuicao DESC"))->toArray();

        if ($post->patrocinador != 0 || $post->divulgacao != 0 || $post->beneficiarios != 0 || $post->qtdenormal != 0 || $post->qtdepromocional != 0) {
            if (!empty($arrPlanoDistribuicao) && $post->prodprincipal == "1") {
                parent::message("J&aacute; existe um Produto Principal cadastrado. A opera&ccedil;&atilde;o foi cancelada.", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
            if ($post->patrocinador > ($QtdeProduzida / 10)) {
                parent::message("A quantidade destinada ao patrocinador n&atilde;o pode ser maior do que 10% do n&uacute;mero Exemplares/Ingressos.", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
            if ($post->divulgacao > ($QtdeProduzida / 10)) {
                parent::message("A quantidade destinada &agrave; divulga&ccedil;&atilde;o n&atilde;o pode ser maior do que 10% do n&uacute;mero Exemplares/Ingressos.", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
            if ($post->beneficiarios < ($QtdeProduzida / 10)) {
                parent::message("A quantidade destinada &agrave; popula&ccedil;&atilde;o de baixa renda n&atilde;o pode ser menor do que 10% do n&uacute;mero Exemplares/Ingressos.", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
            if ((int)str_replace(".", "", $precopromocional) > (int)str_replace(".", "", $preconormal)) {
                parent::message("O valor normal n&atilde;o pode ser menor ou igual ao valor promocional!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
            if ($post->qtdenormal == null) {
                parent::message("Favor preencher o campo Normal(Qntd).", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
            if ($post->qtdepromocional == null) {
                parent::message("Favor preencher o campo Promocional(Qntd).", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
        }

        if (isset($post->produto)) {
            //VERIFICA SE PRODUTO JA ESTA CADASTRADO - NAO PODE GRAVAR O MESMO PRODUTO MAIS DE UMA VEZ.
            $arrBuscaProduto['a.idProjeto = ?'] = $this->_idPreProjeto;
            $arrBuscaProduto['a.idProduto = ?'] = $post->produto;
            $objProduto = $tblPlanoDistribuicao->buscar($arrBuscaProduto);
            if ($objProduto[0]['idPlanoDistribuicao']) {
                parent::message("Produto j&aacute; cadastrado no plano de distribui&ccedil;&atilde;o desta proposta!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
            }
        }
        $retorno = $tblPlanoDistribuicao->salvar($dados);
        if ($retorno > 0) {
            parent::message("Opera&ccedil;&atilde;o realizada com sucesso!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "CONFIRM");
        } else {
            parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
        }
    }

    public function apagarAction()
    {
        if (empty($this->_idPreProjeto))
            parent::message("Informe o numero da proposta", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");

        $get = Zend_Registry::get("get");

        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $rsPlanoDistribuicao = $tblPlanoDistribuicao->findBy(array("idplanodistribuicao = ?" => $get->idPlanoDistribuicao));

        if (($this->isEditarProjeto($this->_idPreProjeto) && $rsPlanoDistribuicao['stPrincipal'] == 1))
            parent::message("Em alterar projeto, n&atilde;o pode excluir o produto principal cadastrado. A opera&ccedil;&atilde;o foi cancelada.", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");

        $retorno = $tblPlanoDistribuicao->apagar($get->idPlanoDistribuicao);

        if ($retorno > 0) {
            parent::message("Opera&ccedil;&atilde;o realizada com sucesso!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "CONFIRM");
        } else {
            parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
        }
    }

    public function detalharPlanoDistribuicaoAction()
    {
        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        if (isset($get->tamPag)) $this->intTamPag = $get->tamPag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;
        $fim = $inicio + $this->intTamPag;
        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $total = $tblPlanoDistribuicao->pegaTotal(array("a.idProjeto = ?" => $this->_idPreProjeto, "a.stPlanoDistribuicaoProduto = ?" => 'true'));
        $tamanho = (($inicio + $this->intTamPag) <= $total) ? $this->intTamPag : $total - ($inicio);

        $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(
            array("a.idProjeto = ?" => $this->_idPreProjeto, "a.stPlanoDistribuicaoProduto = ?" => 'true'),
            array("idPlanoDistribuicao DESC"),
            $tamanho,
            $inicio
        );

        if ($fim > $total) $fim = $total;
        $totalPag = (int)(($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $arrDados = array(
            "pag" => $pag,
            "total" => $total,
            "inicio" => ($inicio + 1),
            "fim" => $fim,
            "totalPag" => $totalPag,
            "planosDistribuicao" => ($rsPlanoDistribuicao),
            "formulario" => $this->_urlPadrao . "/proposta/plano-distribuicao/frm-plano-distribuicao?idPreProjeto=" . $this->_idPreProjeto,
            "urlApagar" => $this->_urlPadrao . "/proposta/plano-distribuicao/apagar?idPreProjeto=" . $this->_idPreProjeto,
            "urlPaginacao" => $this->_urlPadrao . "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto
        );

        $arrBusca['idProjeto'] = $this->_idPreProjeto;
        $arrBusca['stAbrangencia'] = true;
        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);

        $this->view->idPreProjeto = $this->_idPreProjeto;
        $this->view->abrangencias = $rsAbrangencia;
        $this->view->planosDistribuicao = ($rsPlanoDistribuicao);
        $this->view->isEditavel = $this->isEditavel($this->_idPreProjeto);
    }

    public function detalharSalvarAction()
    {
        $dados = $this->getRequest()->getPost();
        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $tblPlanoDistribuicao = new PlanoDistribuicao();

        try {
            $detalhamento->salvar($dados);
            $tblPlanoDistribuicao->updateConsolidacaoPlanoDeDistribuicao($dados['idPlanoDistribuicao']);
        } catch (Exception $e) {
            $this->_helper->json(array('data' => $dados, 'success' => 'false', 'error' => $e));
        }

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

    public function detalharMostrarAction()
    {
        $dados = $this->getRequest()->getParams();
        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $dados = $detalhamento->listarPorMunicicipioUF($dados);
        sleep(1);

        $this->_helper->json(array('data' => $dados->toArray(), 'success' => 'true'));
    }

    public function detalharExcluirAction()
    {
        $id = (int)$this->getRequest()->getParam('idDetalhaPlanoDistribuicao');
        $idPlanoDistribuicao = (int)$this->getRequest()->getParam('idPlanoDistribuicao');

        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $dados = $detalhamento->excluir($id);

        $tblPlanoDistribuicao = new PlanoDistribuicao();
        $tblPlanoDistribuicao->updateConsolidacaoPlanoDeDistribuicao($idPlanoDistribuicao);

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

}
