<?php

class AnalisarprojetoparecerController extends MinC_Controller_Action_Abstract {


    /**
     * @var integer (vari�vel com o id do usu�rio logado)
     * @access private
     */
    private $getIdUsuario = 0;



    /**
     * Reescreve o m�todo init()
     * @access public
     * @param void
     * @return void
     */
    public function init() {
        $this->view->title = "Salic - Sistema de Apoio �s Leis de Incentivo � Cultura"; // t�tulo da p�gina
        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $Usuario = new UsuarioDAO(); // objeto usu�rio
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo

        if ($auth->hasIdentity()) { // caso o usu�rio esteja autenticado

            // verifica as permiss�es
            $PermissoesGrupo = array();
            $PermissoesGrupo[] = 93;
            $PermissoesGrupo[] = 94; // parecerista
            parent::perfil(1, $PermissoesGrupo);
            if (!in_array($GrupoAtivo->codGrupo, $PermissoesGrupo)) { // verifica se o grupo ativo est� no array de permiss�es
                parent::message("Voc� n�o tem permiss�o para acessar essa �rea do sistema!", "principal/index", "ALERT");
            }

            // pega as unidades autorizadas, org�os e grupos do usu�rio (pega todos os grupos)
            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);

            // manda os dados para a vis�o
            $this->view->usuario = $auth->getIdentity(); // manda os dados do usu�rio para a vis�o
            $this->view->arrayGrupos = $grupos; // manda todos os grupos do usu�rio para a vis�o
            $this->view->grupoAtivo = $GrupoAtivo->codGrupo; // manda o grupo ativo do usu�rio para a vis�o
            $this->view->orgaoAtivo = $GrupoAtivo->codOrgao; // manda o �rg�o ativo do usu�rio para a vis�o

            if (isset($auth->getIdentity()->usu_codigo)) // autenticacao novo salic
            {
                $this->getIdUsuario = UsuarioDAO::getIdUsuario($auth->getIdentity()->usu_codigo);
                $this->getIdUsuario = ($this->getIdUsuario) ? $this->getIdUsuario["idAgente"] : 0;
            }


        } // fecha if
        else { // caso o usu�rio n�o esteja autenticado
            return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
        }

        parent::init(); // chama o init() do pai GenericControllerNew
    }


    /**
     * M�todo index()
     * Busca os produto para an�lise do Parecerista
     * @param
     * @return List
     */
    public function indexAction()
    {
        $auth = Zend_Auth::getInstance();
        $idusuario = $auth->getIdentity()->usu_codigo;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $idOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o

        $UsuarioDAO = new Usuario();
        $agente = $UsuarioDAO->getIdUsuario($idusuario);
        $idAgenteParecerista = $agente['idAgente'];

        $situacao = $this->_request->getParam('situacao');

        $projeto = new Projetos();
        $resp = $projeto->buscaProjetosProdutos(
                array(
                    'distribuirParecer.idAgenteParecerista = ?' => $idAgenteParecerista,
                    'distribuirParecer.idOrgao = ?' => $idOrgao,
                    )
                );

        // ========== IN�CIO PAGINA��O ==========
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('paginacao/paginacao.phtml');
        $paginator = Zend_Paginator::factory($resp); // dados a serem paginados

        $currentPage = $this->_getParam('page', 1);
        $paginator->setCurrentPageNumber($currentPage)->setItemCountPerPage(10); // 10 por p�gina
        // ========== FIM PAGINA��O ==========

        $this->view->qtdRegistro = count($resp);
        $this->view->situacao = $situacao;
        $this->view->buscar = $paginator;
    }

    /**
     * M�todo projetosprodutos()
     * Detalhe ?
     * @param
     * @return List
     */
    public function projetosprodutosAction() {
        $auth = Zend_Auth::getInstance();
        $this->_helper->layout->disableLayout();

        $idusuario 	= $this->getIdUsuario;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $idOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o
        $UsuarioDAO = new Usuario();

        $idAgenteParecerista = $idusuario;

        $nrRelatorio = $this->_request->getParam("nrRelatorio");
        $this->view->nrRelatorio = $nrRelatorio;

        $limitador = 5;
        $where = array('d.idAgenteParecerista = ?' => $idAgenteParecerista, 'd.idOrgao = ?' => $idOrgao);
        //$where = array();
        $projeto = new Projetos();
        switch ($nrRelatorio) {
            case 0:
                $total = $projeto->buscaProjetosProdutosAnaliseInicial($where, true)->current()->toArray();
                $limit = $this->paginacao($total["total"], $limitador);
                $resp = $projeto->buscaProjetosProdutosAnaliseInicial($where, false, $limit['tamanho'], $limit['inicio']);
                break;
            case 1:
                $total = $projeto->buscaProjetosProdutosReadequacao($where, true)->current()->toArray();
                $limit = $this->paginacao($total["total"], $limitador);
                $resp = $projeto->buscaProjetosProdutosReadequacao($where, false, $limit['tamanho'], $limit['inicio']);
                break;
            case 2:
                $total = $projeto->buscaProjetosProdutosExecucaoObjeto($where, true)->current()->toArray();
                $limit = $this->paginacao($total["total"], $limitador);
                $resp = $projeto->buscaProjetosProdutosExecucaoObjeto($where, false, $limit['tamanho'], $limit['inicio']);
                break;
            case 3:
                $total = $projeto->buscaProjetosProdutosPrestacaoContas($where, true)->current()->toArray();
                $limit = $this->paginacao($total["total"], $limitador);
                $resp = $projeto->buscaProjetosProdutosPrestacaoContas($where, false, $limit['tamanho'], $limit['inicio']);
                break;
        }
        /* INICIO */
        $cont = 0;
        $AntIdPronac = null;
        $AntIdProduto = null;
        $AntStPrincipal = null;
        $ProjetosProdutos = array('qtd' => 0);
        foreach ($resp as $key => $val) {
            $cont++;

            $diligencia = NULL;
            $tempoRestante = NULL;
            $tempoDiligencia = NULL;

            /* Diligencia */
            if ($val->DtSolicitacao && $val->DtResposta == NULL) {
                $diligencia = 1;
            } else if ($val->DtSolicitacao && $val->DtResposta != NULL) {
                $diligencia = 2;
            } else if ($val->DtSolicitacao && round(data::CompararDatas($val->DtDistribuicao)) > $val->tempoFimDiligencia) {
                $diligencia = 3;
            } else {
                $diligencia = 0;
            }

            /* Tempo Restante */
            switch ($diligencia) {
                case 0:
                    $tempoRestante = round(data::CompararDatas($val->DtDistribuicao));
                    break;
                case 1:
                    $tempoRestante = round(data::CompararDatas($val->DtDistribuicao, $val->DtSolicitacao));
                    break;
                case 2:
                    $tempoRestante = round(data::CompararDatas($val->DtResposta));
                    break;
                case 3: $tempoRestante = round(data::CompararDatas($val->DtResposta));
                    break;
            }
            /* Tempo Diligencia */
            switch ($diligencia) {
                case 1: $tempoDiligencia = round(data::CompararDatas($val->DtSolicitacao));
                    break;
            }
            $ProjetosProdutos['qtd']++;
            $ProjetosProdutos['projetos'][$cont]['&nbsp;'] = $ProjetosProdutos['qtd'];
            if ($AntIdPronac == $val->IdPRONAC) {
                $ProjetosProdutos['projetos'][$cont]['PRONAC'] = "&nbsp;";
                $ProjetosProdutos['projetos'][$cont]['Nome do Projeto'] = "&nbsp;";
            } else {
                $ProjetosProdutos['projetos'][$cont]['PRONAC'] = "<a target='_blank'  href='" . $this->url(array('controller' => 'consultardadosprojeto', 'action' => 'index')) . "?idPronac=" . $val->IdPRONAC . "' >" . $val->PRONAC . "</a>";
                $ProjetosProdutos['projetos'][$cont]['Nome do Projeto'] = $val->NomeProjeto;
            }

            $ProjetosProdutos['projetos'][$cont]['Produto'] = "<a href='" . $this->url(array('controller' => 'Analisarprojetoparecer', 'action' => 'produto')) . "?nrRelatorio=" . $nrRelatorio . "&idPronac=" . $val->IdPRONAC . "&idProduto=" . $val->idProduto . "&stPrincipal=" . $val->stPrincipal . "' >" . $val->dsProduto . "</a>";



            if ($val->stPrincipal == 1) {
                $ProjetosProdutos['projetos'][$cont]['Tipo de produto'] = "<p style='text-align: center;'><img src='/NovoSalic/public/img/ok_16x16.png' width='20px'/></p>";
            } else {
                $ProjetosProdutos['projetos'][$cont]['Tipo de produto'] = "";
            }
            $ProjetosProdutos['projetos'][$cont]['Data de Recebimento'] = $val->DtEnvio;
            $ProjetosProdutos['projetos'][$cont]['Tempo Restante'] = $tempoRestante . "/" . $val->tempoFimParecer;
            switch ($diligencia) {
                case 0:
                    $ProjetosProdutos['projetos'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><a href='{$this->url(array('controller' => 'diligenciar', 'action' => 'cadastrardiligencia'))}?idPronac={$val->IdPRONAC}&idProduto={$val->idProduto}&situacao=B14&tpDiligencia=124'><img src='/NovoSalic/public/img/notice1.png' title='A Diligenciar' width='30px'/></a></p>";
                    break;
                case 1:
                    $ProjetosProdutos['projetos'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><a href='{$this->url(array('controller' => 'diligenciar', 'action' => 'listardiligenciaanalista'))}?idPronac={$val->IdPRONAC}&idProduto={$val->idProduto}'><img src='/NovoSalic/public/img/notice.png' title='Diligenciado' width='30px'/></a></p>";
                    break;
                case 2:
                    $ProjetosProdutos['projetos'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><a href='{$this->url(array('controller' => 'diligenciar', 'action' => 'listardiligenciaanalista'))}?idPronac={$val->IdPRONAC}&idProduto={$val->idProduto}'><img src='/NovoSalic/public/img/notice2.png' title='Dilig?ncia n?o respondida' width='30px'/></a></p>";
                    break;
                case 3:
                    $ProjetosProdutos['projetos'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><a href='{$this->url(array('controller' => 'diligenciar', 'action' => 'listardiligenciaanalista'))}?idPronac={$val->IdPRONAC}&idProduto={$val->idProduto}&situacao=B14&tpDiligencia=124'><img src='/NovoSalic/public/img/notice3.png' title='Dilig?ncia respondida' width='30px'/></a></p>";
                    break;
            }
            if ($tempoDiligencia) {
                $ProjetosProdutos['projetos'][$cont]['Tempo da Dilig&ecirc;ncia'] = $tempoDiligencia . '/' . $val->tempoFimDiligencia;
            } else {
                $ProjetosProdutos['projetos'][$cont]['Tempo da Dilig&ecirc;ncia'] = '&nbsp;';
            }
            $ProjetosProdutos['projetos'][$cont]['Historico'] = "<p style='text-align: center;'><a href='" . $this->url(array('controller' => 'Analisarprojetoparecer', 'action' => 'historico')) . "?idPronac=" . $val->IdPRONAC . "&idProduto=" . $val->idProduto . "&stPrincipal=" . $val->stPrincipal . "' ><img src='/NovoSalic/public/img/edit_ico.gif'/></a></p>";
            $ProjetosProdutos['projetos'][$cont]['Conclus&atilde;o'] = "<p style='text-align: center;'><a href='" . $this->url(array('controller' => 'Analisarprojetoparecer', 'action' => 'fecharparecer')) . "?idPronac=" . $val->IdPRONAC . "&tipoanalise=" . $val->TipoAnalise . "&idProduto=" . $val->idProduto . "' ><img src='/NovoSalic/public/img/save.gif'/></a></p>";
            $AntIdPronac = $val->IdPRONAC;
            $AntIdProduto = $val->idProduto;
            $AntStPrincipal = $val->stPrincipal;
        }
        $this->view->ProjetosProdutos = $ProjetosProdutos;
    }

    /**
     * M�todo paginacao()
     * Detalhe ?
     * @param
     * @return List
     */
    private function paginacao($total, $qtInformacao = 10) {
        $post = Zend_Registry::get('post');
        $this->intTamPag = $qtInformacao;
        //controlando a paginacao
        $pag = 1;
        if (isset($post->pag))
            $pag = $post->pag;
        if (isset($post->tamPag))
            $this->intTamPag = $post->tamPag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;
        $fim = $inicio + $this->intTamPag;

        $totalPag = (int) (($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;
        if ($fim > $total)
            $fim = $total;

        $this->view->pag = $pag;
        $this->view->total = $total;
        $this->view->inicio = ($inicio + 1);
        $this->view->fim = $fim;
        $this->view->totalPag = $totalPag;
        $this->view->parametrosBusca = $_POST;

        return array('tamanho' => $tamanho, 'inicio' => $inicio);
    }

    /**
     * M�todo historico()
     * Busca o hist�rico do Projeto/Produto
     * @param
     * @return List
     */
    public function historicoAction() {
        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $idUsuario = $auth->getIdentity()->usu_codigo;
//      $idUsuario = $this->getIdUsuario;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $idOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o

        $getBaseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        $idPronac 		= $this->_request->getParam("idPronac");
        $idProduto 		= $this->_request->getParam("idProduto");
        $stPrincipal 	= $this->_request->getParam("stPrincipal");

        $tbDistribuirParecer = new tbDistribuirParecer();
        $resp = $tbDistribuirParecer->buscarHistorico(array("d.idPronac = ?" => $idPronac, "d.idProduto = ?" => $idProduto, "d.stPrincipal = ?" => $stPrincipal));

        $cont = 0;
        $Pareceres = array();
        foreach ($resp as $key => $val) {
            $cont++;

            if ($val->DtSolicitacao && $val->DtResposta == NULL) {
                $diligencia = 1;
            } else if ($val->DtSolicitacao && $val->DtResposta != NULL) {
                $diligencia = 2;
            } else if ($val->DtSolicitacao && round(data::CompararDatas($val->DtDistribuicao)) > $val->tempoFimDiligencia) {
                $diligencia = 3;
            } else {
                $diligencia = 0;
            }

            $Pareceres['pareceres'][$cont]['NOME DO PRODUTO'] = "<b>$val->dsProduto</b>";
            $Pareceres['pareceres'][$cont]['UNIDADE RESPONS&Aacute;VEL'] = $val->Unidade;
            $Pareceres['pareceres'][$cont]['Data'] = $val->DtDistribuicaoPT;
            $Pareceres['pareceres'][$cont]['Observa&ccedil;&otilde;es'] = $val->Observacao;
            $Pareceres['pareceres'][$cont]['Nome do Remetente'] = $val->nmUsuario;
            $Pareceres['pareceres'][$cont]['Nome do Parecerista'] = $val->nmParecerista;

            /*switch ($diligencia) {
                case 0:
                    $Pareceres['pareceres'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><img src='". $getBaseUrl ."/public/img/notice1.png' width='30px'/></p>";
                    break;
                case 1:
                    $Pareceres['pareceres'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><img src='". $getBaseUrl ."/public/img/notice.png' width='30px'/></p>";
                    break;
                case 2:
                    $Pareceres['pareceres'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><img src='". $getBaseUrl ."/public/img/notice2.png' width='30px'/></p>";
                    break;
                case 3:
                    $Pareceres['pareceres'][$cont]['Dilig&ecirc;ncia'] = "<p style='text-align: center;'><img src='". $getBaseUrl ."/public/img/notice3.png' width='30px'/></p>";
                    break;
            }*/
        }
        $this->view->Pareceres = $Pareceres;
    }


    /**
     * M�todo produto()
     * Lista os detalhes para an�lise
     * @param idPronac
     * @param idProduto
     * @param stPrincipal
     * @return List
     */
    public function produtoAction() {
        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $idusuario = $auth->getIdentity()->usu_codigo;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $codOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o
        $codGrupo = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        $idProduto = $this->_request->getParam("idProduto");
        $stPrincipal = $this->_request->getParam("stPrincipal");

        $projetoDAO = new Projetos();

        $whereProjeto['p.IdPRONAC = ?'] = $idPronac;
        $whereProjeto['d.idProduto = ?'] = $idProduto;
        $whereProjeto['d.stPrincipal = ?'] = $stPrincipal;

        $projeto = $projetoDAO->buscaProjetosProdutosAnaliseInicial($whereProjeto);
        $this->view->projeto = $projeto[0];
        $this->view->dsArea = $projeto[0]->dsArea;
        $this->view->dsSegmento = $projeto[0]->dsSegmento;

        /* Analise de conte�do */
        $analisedeConteudoDAO = new Analisedeconteudo();
        $analisedeConteudo = $analisedeConteudoDAO->dadosAnaliseconteudo(false, array('idPronac = ?' => $idPronac, 'idProduto = ?' => $idProduto));

        $PlanilhaDAO = new PlanilhaProjeto();
        if ($stPrincipal == 1) {
            //$where = array('PPJ.IdPRONAC = ?' => $idPronac);
            $where = array('PPJ.IdPRONAC = ?' => $idPronac, 'PPJ.IdProduto in (0, ?)' => $idProduto);
        } else {
            $where = array('PPJ.IdPRONAC = ?' => $idPronac, 'PPJ.IdProduto = ?' => $idProduto, 'PD.Descricao is not null' => null);
        }

        $resp = $PlanilhaDAO->buscarAnaliseCustos($where);
        $itensCusto = array('fonte' => array(), 'totalSolicitado' => 0, 'totalSugerido' => 0);
        $cont = true;

        foreach ($resp as $key => $val) {
            $produto = $val->Produto == null ? 'Adminitra&ccedil;&atilde;o do Projeto' : $val->Produto;
            if (!isset($itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['qtd'])) {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade] = array('qtd' => 0, 'totalUfSolicitado' => 0, 'totalUfSugerido' => 0, 'itens' => array(), 'totalSolicitado' => 0, 'totalSugerido' => 0);
            }
            $itensCusto['totalSolicitado'] += $val->VlSolicitado;
            $itensCusto['totalSugerido'] += $val->VlSugeridoParecerista;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['qtd']++;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['totalUfSolicitado'] += $val->VlSolicitado;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['totalUfSugerido'] += $val->VlSugeridoParecerista;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['&nbsp;'] = $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['qtd'];

            // So pode alterar se for incentivo fiscal - FonteRecurso = 109
            if(($analisedeConteudo[0]->ParecerFavoravel == 1) && ($val->idEtapa != 4)) {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Item'] = "<a href='javascript:AlterarItem({$val->idPlanilhaProjeto},{$idPronac},{$idProduto},{$stPrincipal})'>{$val->Item}</a>";
            } else if(($analisedeConteudo[0]->ParecerFavoravel == 1) && ($stPrincipal == 1)) {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Item'] = "<a href='javascript:AlterarItem({$val->idPlanilhaProjeto},{$idPronac},{$idProduto},{$stPrincipal})'>{$val->Item}</a>";
            } else {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Item'] = "{$val->Item}";
            }

            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Dias'] = $val->diasprop;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Unidade'] = $val->UnidadeProposta;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Quantidade'] = number_format($val->quantidadeprop, 0, '.', ',');
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Ocorr�ncias'] = number_format($val->ocorrenciaprop, 0, '.', ',');
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Valor Unit�rio'] = $val->valorUnitarioprop;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Valor Solicitado'] = $val->VlSolicitado;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Justificativa do Proponente'] = $val->justificitivaproponente;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Valor Sugerido pelo Parecerista'] = $val->VlSugeridoParecerista;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Justificativas do Parecerista'] = $val->dsJustificativaParecerista;
        }
        foreach ($itensCusto['fonte'] as $key => $value) {
            foreach ($value as $key2 => $value2) {
                foreach ($value2 as $key3 => $value3) {
                    foreach ($value3 as $key4 => $value4) {

                        if ($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado'] != 0) {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado'] = $this->formatarReal($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado']);
                        }else {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado'] = "R$ 0,00";
                        }

                        if ($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido'] != 0) {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido'] = $this->formatarReal($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido']);
                        }else {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido'] = "R$ 0,00";
                        }
                    }
                }
            }
        }
        $valorPossivel = $itensCusto['totalSolicitado']-$itensCusto['totalSugerido'];
        $valorSolicitado = $itensCusto['totalSolicitado'];
        if ($itensCusto['totalSolicitado'] != 0) {
            $itensCusto['totalSolicitado'] = $this->formatarReal($itensCusto['totalSolicitado']);
        } else {
            $itensCusto['totalSugerido'] = "R$ 0,00";
        }

        if ($itensCusto['totalSugerido'] != 0) {
            $itensCusto['totalSugerido'] = $this->formatarReal($itensCusto['totalSugerido']);
        } else {
            $itensCusto['totalSugerido'] = "R$ 0,00";
        }

        $this->view->itens = $itensCusto;
        $this->view->stPrincipal = $stPrincipal;
        $this->view->comboareasculturais = Agente_Model_ManterAgentesDAO::buscarAreasCulturais();
        $this->view->combosegmentosculturais = Segmentocultural::buscarSegmento($projeto[0]->Area);
        $this->view->valorpossivel = $valorPossivel;
        $this->view->vlSolicitado = $valorSolicitado;

        /* Se for o produto principal, envia os dados dos secund�rios junto *******************************/
        if($stPrincipal == 1) {
            $tbDistribuirParecerDAO = new tbDistribuirParecer();

            $dadosWhere["t.stEstado = ?"]                   = 0;
            $dadosWhere["t.TipoAnalise in (?)"]             = array(1,3);
            $dadosWhere["p.Situacao IN ('B11', 'B14')"]     = '';
            $dadosWhere["p.IdPRONAC = ?"]                   = $idPronac;
            $dadosWhere["t.stPrincipal = ?"]                = 0;
            $Secundarios = $tbDistribuirParecerDAO->dadosParaDistribuirSecundarios($dadosWhere);

            $dadosWhere["t.DtDistribuicao is not null"]     = '';
            $dadosWhere["t.DtDevolucao is null"]        	= '';
            $SecundariosAtivos = $tbDistribuirParecerDAO->dadosParaDistribuir($dadosWhere);
            $pscount = count($SecundariosAtivos);

            $i = 1;
            foreach($Secundarios as $ps) {
                $wherePS['PAP.idPRONAC = ?'] = $ps->IdPRONAC;
                $wherePS['PAP.idProduto = ?'] = $ps->idProduto;
                $valorSugerido = $PlanilhaDAO->somaDadosPlanilha($wherePS);

                $produtosSecundarios[$i]['IdPRONAC'] 			= $ps->IdPRONAC;
                $produtosSecundarios[$i]['idProduto'] 			= $ps->idProduto;
                $produtosSecundarios[$i]['stPrincipal'] 		= $ps->stPrincipal;
                $produtosSecundarios[$i]['Produto'] 			= $ps->Produto;
                $i++;
            }

            $this->view->produtosSecundarios = $Secundarios;
            // $this->view->produtosSecundarios = $produtosSecundarios;
            $this->view->produtosSecundariosEmAnalise = $pscount;

            /** Verificar se o Produto principal j� foi dado a consolida��o ********************/
            $consolidado = 'N';
            $enquadramentoDAO 		= new Enquadramento();
            $buscaEnquadramento 	= $enquadramentoDAO->buscarDados($idPronac, null, false);
            $countEnquadramentoP 	= count($buscaEnquadramento);

            $parecerDAO 			= new Parecer();
            $whereParecer['idPRONAC = ?'] = $idPronac;
            $buscaParecer 			= $parecerDAO->buscar($whereParecer);
            $countParecerP 			= count($buscaParecer);
            /***********************************************************************************/
            if(($countEnquadramentoP != 0) && ($countParecerP != 0)) {
                $consolidado = 'S';
            }

            $this->view->consolidado   = $consolidado;
            $this->view->consolidacao  = $buscaParecer;
            $this->view->enquadramento = $buscaEnquadramento;
        }

        /****************************************************************************************************/
        // Dados para concluir a an�lise
        $tbDistribuirParecerDAO = new tbDistribuirParecer();
        $tbDiligencia = new tbDiligencia();

        /* Verifica se tem diligencia para o projeto  */
        $rsDilig = $tbDiligencia->buscarDados($idPronac);

        // Conta quantas diligencias existe
        $dilig = count($rsDilig);

        /** Verifica se tem produtos secund�rios n�o analizados ****************************/
        $dadosWhereSA["t.stEstado = ?"] = 0;
        $dadosWhereSA["t.FecharAnalise = ?"] = 0;
        $dadosWhereSA["t.TipoAnalise = ?"] = 3;
        $dadosWhereSA["p.Situacao IN ('B11', 'B14')"] = '';
        $dadosWhereSA["p.IdPRONAC = ?"] = $idPronac;
        $dadosWhereSA["t.stPrincipal = ?"] = 0;
        $dadosWhereSA["t.DtDevolucao is null"] = '';

        $SecundariosAtivos = $tbDistribuirParecerDAO->dadosParaDistribuir($dadosWhereSA)->count();
        $pscount = $SecundariosAtivos;
        /***********************************************************************************/

        /** Verificar se o Produto j� foi dado o Parecer ***********************************/
        $tbAnaliseDeConteudoDAO = new Analisedeconteudo();
        $whereAC['IdPRONAC = ?'] = $idPronac;
        $whereAC['idProduto = ?'] = $idProduto;
        $whereAC['ParecerDeConteudo = ?'] = '';
        $countAnalizado = $tbAnaliseDeConteudoDAO->dadosAnaliseconteudo(null,$whereAC)->count();
        /***********************************************************************************/

        /** Verificar se o Produto principal j� foi dado a consolida��o ********************/
        $enquadramentoDAO = new Enquadramento();
        $buscaEnquadramento = $enquadramentoDAO->buscarDados($idPronac, null, false);
        $countEnquadramentoP = count($buscaEnquadramento);

        $parecerDAO = new Parecer();
        $buscaParecer = $parecerDAO->buscarParecer(null, $idPronac);
        $countParecerP = count($buscaParecer);

        /***********************************************************************************/
        $this->view->pscount = $pscount;
        $this->view->countAnalizado = $countAnalizado;
        $this->view->countEnquadramentoP = $countEnquadramentoP;
        $this->view->countParecerP = $countParecerP;
        $this->view->dilig = $dilig;

        $this->view->idPronac = $idPronac;
        $this->view->idProduto = $idProduto;
        $this->view->idD = $this->_request->getParam('idD');
        $this->view->Perfil = $codGrupo;
        /***********************************************************************************/
    }

    public function validaRegra20Porcento($idPronac) {

        /**********************************************************************************************************/
        // Valida��o do 20%
        $planilhaProjeto = new PlanilhaProjeto();
        $valorProjeto = $planilhaProjeto->somarPlanilhaProjeto($idPronac, 109);

        $valorProjetoDivulgacao = $planilhaProjeto->somarPlanilhaProjetoDivulgacao($idPronac, 109, null, null);

        $somaProjetoDivulgacao = $valorProjetoDivulgacao->soma ? $valorProjetoDivulgacao->soma : 0 ;

        $totalDivulgacao = false;

        if ($somaProjetoDivulgacao != 0) {
            $this->view->totalsugerido = $valorProjeto['soma'] ? $valorProjeto['soma'] : 0; //valor total do projeto (Planilha Aprovacao)
            $porcentValorProjeto = ($valorProjeto['soma'] * 0.20);
            $totalValorProjetoDivulgacao = $valorProjetoDivulgacao->soma;

            $valorRetirar = $totalValorProjetoDivulgacao-$porcentValorProjeto;
            $this->view->valorRetirar = $valorRetirar;

            if ($totalValorProjetoDivulgacao > $porcentValorProjeto ) {
                return "false";
            }
            else {
                return "true";
            }

        }
        else {
            return "true";
        }
    }

    /**
     * M�todo produtosecundario()
     * Lista os detalhes da an�lise dos produtos secund�rios
     * @param idPronac
     * @param idProduto
     * @param stPrincipal
     * @return List
     */
    public function produtosecundarioAction() {

        $auth 		 = Zend_Auth::getInstance(); // pega a autentica��o
        $idusuario 	 = $auth->getIdentity()->usu_codigo;

        $GrupoAtivo  = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $codOrgao 	 = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o
        $codGrupo 	 = $GrupoAtivo->codGrupo;

        $idPronac 	 = $this->_request->getParam("idPronac");
        $idProduto 	 = $this->_request->getParam("idProduto");
        $stPrincipal     = $this->_request->getParam("stPrincipal");

        $projetoDAO = new Projetos();

        $whereProjeto['p.IdPRONAC = ?'] = $idPronac;
        $whereProjeto['d.idProduto = ?'] = $idProduto;
        $whereProjeto['d.stPrincipal = ?'] = $stPrincipal;

        $projeto = $projetoDAO->buscaProjetosProdutosAnaliseInicial($whereProjeto);
        $this->view->projeto = $projeto[0];

        /* Analise de conte�do */
        $analisedeConteudoDAO = new Analisedeconteudo();
        $analisedeConteudo = $analisedeConteudoDAO->dadosAnaliseconteudo(false, array('idPronac = ?' => $idPronac, 'idProduto = ?' => $idProduto));

        $PlanilhaDAO = new PlanilhaProjeto();

        if ($stPrincipal) {
            $where = array('PPJ.IdPRONAC = ?' => $idPronac, 'PPJ.IdProduto = ?' => $idProduto);
        }
        else {
            $where = array('PPJ.IdPRONAC = ?' => $idPronac, 'PPJ.IdProduto = ?' => $idProduto, 'PD.Descricao is not null' => null);
        }

        $resp = $PlanilhaDAO->buscarAnaliseCustos($where);

        $itensCusto = array('fonte' => array(), 'totalSolicitado' => 0, 'totalSugerido' => 0);
        $cont = true;

        foreach ($resp as $key => $val) {
            $produto = $val->Produto == null ? 'Adminitra&ccedil;&atilde;o do Projeto' : $val->Produto;
            if (!isset($itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['qtd'])) {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade] = array('qtd' => 0, 'totalUfSolicitado' => 0, 'totalUfSugerido' => 0, 'itens' => array(), 'totalSolicitado' => 0, 'totalSugerido' => 0);
            }
            $itensCusto['totalSolicitado'] += $val->VlSolicitado;
            $itensCusto['totalSugerido'] += $val->VlSugeridoParecerista;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['qtd']++;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['totalUfSolicitado'] += $val->VlSolicitado;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['totalUfSugerido'] += $val->VlSugeridoParecerista;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['&nbsp;'] = $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['qtd'];

            //$itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Item'] = "{$val->Item}";

            // Se etapa for igual a Divulgacao/Comercializacao e tiver ultrapassado os 20% do valor total do projeto, libera para alteracao
            $bln_regra20Porecento = $this->validaRegra20Porcento($idPronac);
            if(($analisedeConteudo[0]->ParecerFavoravel == 1) && ($val->NrFonteRecurso == '109') && ($val->idEtapa == 3) && $bln_regra20Porecento == "false") {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Item'] = "<a href='#' onclick='javascript:AlterarItem({$val->idPlanilhaProjeto},{$idPronac},{$idProduto},{$stPrincipal})'>{$val->Item}</a>";
            }else {
                $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Item'] = "{$val->Item}";
            }
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Dias'] = $val->diasprop;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Unidade'] = $val->UnidadeProposta;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Quantidade'] = number_format($val->quantidadeprop, 0, '.', ',');
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Ocorr�ncias'] = number_format($val->ocorrenciaprop, 0, '.', ',');
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Valor Unit�rio'] = $val->valorUnitarioprop;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Valor Solicitado'] = $val->VlSolicitado;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Justificativa do Proponente'] = $val->justificitivaproponente;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Valor Sugerido pelo Parecerista'] = $val->VlSugeridoParecerista;
            $itensCusto['fonte'][$val->FonteRecurso][$produto][$val->idEtapa . ' - ' . $val->Etapa][$val->UF . ' - ' . $val->Cidade]['itens'][$val->idPlanilhaProjeto]['Justificativas do Parecerista'] = $val->dsJustificativaParecerista;
        }
        foreach ($itensCusto['fonte'] as $key => $value) {
            foreach ($value as $key2 => $value2) {
                foreach ($value2 as $key3 => $value3) {
                    foreach ($value3 as $key4 => $value4) {

                        if ($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado'] != 0) {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado'] = $this->formatarReal($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado']);
                        }else {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSolicitado'] = "R$ 0,00";
                        }

                        if ($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido'] != 0) {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido'] = $this->formatarReal($itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido']);
                        }else {
                            $itensCusto['fonte'][$key][$key2][$key3][$key4]['totalUfSugerido'] = "R$ 0,00";
                        }
                    }
                }
            }
        }
        $valorPossivel = $itensCusto['totalSolicitado']-$itensCusto['totalSugerido'];
        $valorSolicitado = $itensCusto['totalSolicitado'];
        if ($itensCusto['totalSolicitado'] != 0) {
            $itensCusto['totalSolicitado'] = $this->formatarReal($itensCusto['totalSolicitado']);
        }
        else {
            $itensCusto['totalSugerido'] = "R$ 0,00";
        }
        if ($itensCusto['totalSugerido'] != 0) {
            $itensCusto['totalSugerido'] = $this->formatarReal($itensCusto['totalSugerido']);
        }
        else {
            $itensCusto['totalSugerido'] = "R$ 0,00";
        }

        $this->view->itens = $itensCusto;
        $this->view->stPrincipal = $stPrincipal;
        $this->view->comboareasculturais   = Agente_Model_ManterAgentesDAO::buscarAreasCulturais();
        $this->view->valorpossivel   = $valorPossivel;
        $this->view->vlSolicitado    = $valorSolicitado;

        /* Se for o produto principal, envia os dados dos secund�rios junto *******************************/
        if($stPrincipal == 1) {
            $tbDistribuirParecerDAO = new tbDistribuirParecer();

            $dadosWhere["t.stEstado = ?"]                   = 0;
            $dadosWhere["t.FecharAnalise = ?"]              = 0;
            $dadosWhere["t.TipoAnalise = ?"]               = 3;
            $dadosWhere["p.Situacao IN ('B11', 'B14')"]     = '';
            $dadosWhere["p.IdPRONAC = ?"]                   = $idPronac;
            $dadosWhere["t.stPrincipal = ?"]                = 0;

            $Secundarios = $tbDistribuirParecerDAO->dadosParaDistribuir($dadosWhere);

            $dadosWhere["t.DtDevolucao is null"]        = '';
            $SecundariosAtivos = $tbDistribuirParecerDAO->dadosParaDistribuirSecundarios($dadosWhere);

            $pscount = count($SecundariosAtivos);

            $i = 0;
            foreach($Secundarios as $ps) {

                $wherePS['PAP.idPRONAC = ?'] = $ps->IdPRONAC;
                $wherePS['PAP.idProduto = ?'] = $ps->idProduto;
                $valorSugerido = $PlanilhaDAO->somaDadosPlanilha($wherePS);


                $produtosSecundarios[$i]['IdPRONAC'] 			= $ps->IdPRONAC;
                $produtosSecundarios[$i]['idProduto'] 			= $ps->idProduto;
                $produtosSecundarios[$i]['stPrincipal'] 		= $ps->stPrincipal;

                $produtosSecundarios[$i]['Produto'] 			= $ps->Produto;
                $produtosSecundarios[$i]['ParecerFavoravel'] 	= $ps->ParecerFavoravel;
                $produtosSecundarios[$i]['ValorSugerido'] 		= $valorSugerido['soma'];

                $i++;

            }

            $this->view->produtosSecundarios = $produtosSecundarios;
            $this->view->produtosSecundariosEmAnalise = $pscount;


            /** Verificar se o Produto principal j� foi dado a consolida��o ********************/
            $consolidado = 'N';

            $enquadramentoDAO 		= new Enquadramento();
            $buscaEnquadramento 	= $enquadramentoDAO->buscarDados($idPronac, null, false);
            $countEnquadramentoP 	= count($buscaEnquadramento);

            $parecerDAO 			= new Parecer();
            $whereParecer['idPRONAC = ?'] = $idPronac;
            //$buscaParecer 			= $parecerDAO->buscarParecer($idPronac);
            $buscaParecer 			= $parecerDAO->buscar($whereParecer); //UTILIZANDO METODO GENERICO PARA NAO CONFLITAR COM A CNIC
            $countParecerP 			= count($buscaParecer);
            /***********************************************************************************/

            if(($countEnquadramentoP != 0) && ($countParecerP != 0)) {
                $consolidado = 'S';
            }

            $this->view->consolidado = $consolidado;

        }

        /****************************************************************************************************/

    }

    /**
     * M�todo analisedeconteudo()
     * @return List
     */
    public function analisedeconteudoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $dsJustificativa = isset($_POST["ParecerDeConteudo"]) ? $_POST["ParecerDeConteudo"] : '';

        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $idusuario = $auth->getIdentity()->usu_codigo;

        $post 		= $_POST;
        $stAcao 	= $post['stAcao'];
        $idPronac 	= $post['idPRONAC'];
        $idProduto      = $post['idProduto'];
        $stPrincipal    = $post['stPrincipal'];
        $idD            = $this->_request->getParam("idD");

        switch ($stAcao) {
            case 1: /* CONSULTA */

                $analisedeConteudoDAO = new Analisedeconteudo();
                $resp = $analisedeConteudoDAO->dadosAnaliseconteudo(false, array('idPRONAC = ?' => $idPronac, 'idProduto = ?' => $idProduto))->current()->toArray();
                foreach ($resp as $key => $val) {
                    $arrayRetorno[$key] = utf8_encode($val);
                }
                echo json_encode($arrayRetorno);
                break;

            case 2:
                try {
                    if (!$post['ParecerFavoravel']) {
                        $planilhaProjeto = new PlanilhaProjeto();
                        $atualizar = array('idUnidade' => 1, 'Quantidade' => 0, 'Ocorrencia' => 0, 'ValorUnitario' => 0, 'QtdeDias' => 0, 'idUsuario' => $idusuario, 'Justificativa' => '');

                        if ($stPrincipal) {
                            $planilhaProjeto->alterar($atualizar, array('idPRONAC = ?' => $idPronac));
                        } else {
                            $planilhaProjeto->alterar($atualizar, array('idPRONAC = ?' => $idPronac, 'idProduto = ?' => $idProduto));
                        }
                    } else {
                        $analisedeConteudoDAO = new Analisedeconteudo();
                        $whereB['idPronac  = ?'] = $idPronac;
                        $whereB['idProduto = ?'] = $idProduto;
                        $busca = $analisedeConteudoDAO->buscar($whereB);

                        if($busca[0]->ParecerFavoravel == 0) {
                            $copiaPlanilha = PlanilhaPropostaDAO::parecerFavoravel($idPronac, $idProduto);
                        }
                    }

                    $dados = array(
                        'Lei8313' 		=> $post['Lei8313'],
                        'Artigo3' 		=> $post['Artigo3'],
                        'IncisoArtigo3' 	=> $post['IncisoArtigo3'],
                        'AlineaArtigo3' 	=> $post['AlineaArtigo3'],
                        'Artigo18' 		=> $post['Artigo18'],
                        'AlineaArtigo18' 	=> $post['AlineaArtigo18'],
                        'Artigo26' 		=> $post['Artigo26'],
                        'Lei5761' 		=> $post['Lei5761'],
                        'Artigo27' 		=> $post['Artigo27'],
                        'IncisoArtigo27_I' 	=> isset($post['IncisoArtigo27_I']) ? $post['IncisoArtigo27_I'] : 0,
                        'IncisoArtigo27_II'     => isset($post['IncisoArtigo27_II']) ? $post['IncisoArtigo27_II'] : 0,
                        'IncisoArtigo27_III' 	=> isset($post['IncisoArtigo27_III']) ? $post['IncisoArtigo27_III'] : 0,
                        'IncisoArtigo27_IV' 	=> isset($post['IncisoArtigo27_IV']) ? $post['IncisoArtigo27_IV'] : 0,
                        'TipoParecer' 		=> 1,
                        'ParecerFavoravel' 	=> $post['ParecerFavoravel'],
                        'ParecerDeConteudo' 	=> $dsJustificativa,
                        'idUsuario' 		=> $idusuario,
                    );
                    $analisedeConteudoDAO = new Analisedeconteudo();
                    $where['idPRONAC = ?']  = $idPronac;
                    $where['idProduto = ?'] = $idProduto;
                    $analisedeConteudoDAO->update($dados,$where);

                    parent::message("Altera&ccedil;&atilde;o realizada com sucesso!", "Analisarprojetoparecer/produto/?idPronac={$idPronac}&idProduto={$idProduto}&stPrincipal={$stPrincipal}&idD={$idD}", "CONFIRM");
                }
                catch (Exception $e) {
                    parent::message($e->getMessage(), "Analisarprojetoparecer/produto/?idPronac={$idPronac}&idProduto={$idProduto}&stPrincipal={$stPrincipal}&idD={$idD}", "ERROR");
                }
                break;

            case 3: /* INSERT */
                try {
                    if (!$post['ParecerFavoravel']) {
                        $planilhaProjeto = new PlanilhaProjeto();
                        $atualizar = array('idUnidade' => 1, 'Quantidade' => 0, 'Ocorrencia' => 0, 'ValorUnitario' => 0, 'QtdeDias' => 0, 'idUsuario' => $idusuario, 'Justificativa' => '');

                        if ($stPrincipal) {
                            $planilhaProjeto->alterar($atualizar, array('idPRONAC = ?' => $idPronac));
                        } else {
                            $planilhaProjeto->alterar($atualizar, array('idPRONAC = ?' => $idPronac, 'idProduto = ?' => $idProduto));
                        }
                    } else {
                        if($busca[0]->ParecerFavoravel == 0) {
                            $copiaPlanilha = PlanilhaPropostaDAO::parecerFavoravel($idPronac, $idProduto);
                        }
                    }

                    $dados = array(
                        'idPronac' 				=> $idPronac,
                        'idProduto' 			=> $idProduto,
                        'Lei8313' 				=> $post['Lei8313'],
                        'Artigo3' 				=> $post['Artigo3'],
                        'IncisoArtigo3' 		=> $post['IncisoArtigo3'],
                        'AlineaArtigo3' 		=> $post['AlineaArtigo3'],
                        'Artigo18' 				=> $post['Artigo18'],
                        'AlineaArtigo18' 		=> $post['AlineaArtigo18'],
                        'Artigo26' 				=> $post['Artigo26'],
                        'Lei5761' 				=> $post['Lei5761'],
                        'Artigo27' 				=> $post['Artigo27'],
                        'IncisoArtigo27_I' 		=> isset($post['IncisoArtigo27_I']) ? $post['IncisoArtigo27_I'] : 0,
                        'IncisoArtigo27_II' 	=> isset($post['IncisoArtigo27_II']) ? $post['IncisoArtigo27_II'] : 0,
                        'IncisoArtigo27_III' 	=> isset($post['IncisoArtigo27_III']) ? $post['IncisoArtigo27_III'] : 0,
                        'IncisoArtigo27_IV' 	=> isset($post['IncisoArtigo27_IV']) ? $post['IncisoArtigo27_IV'] : 0,
                        'TipoParecer' 			=> 1,
                        'ParecerFavoravel' 		=> $post['ParecerFavoravel'],
                        'ParecerDeConteudo' 	=> $dsJustificativa,
                        'idParecer' 			=> null,
                        'idUsuario' 			=> $idusuario,
                    );

                    $analisedeConteudoDAO = new Analisedeconteudo();
                    $resp = $analisedeConteudoDAO->insert($dados);
                    parent::message("Altera&ccedil;&atilde;o realizada com sucesso!", "Analisarprojetoparecer/produto/?idPronac={$idPronac}&idProduto={$idProduto}&stPrincipal={$stPrincipal}&idD={$idD}", "CONFIRM");

                } catch (Exception $e) {
                    parent::message($e->getMessage(), "Analisarprojetoparecer/produto/?idPronac={$idPronac}&idProduto={$idProduto}&stPrincipal={$stPrincipal}&idD={$idD}", "ERROR");
                }
                break;
        }
    }

    /**
     * M�todo alteraitemsolicitado()
     * Altera os itens da planilha
     * @param idPronac
     * @param idProduto
     * @param stPrincipal
     * @param idPlanilhaProjeto
     * @return void
     */
    public function alterarintemsolicitadoAction() {
        $this->_helper->layout->disableLayout();
        $idPronac = $this->_request->getParam("idPronac");
        $idProduto = $this->_request->getParam("idProduto");
        $stPrincipal = $this->_request->getParam("stPrincipal");
        $idPlanilhaProjeto = $this->_request->getParam("idPlanilhaProjeto");

        $this->view->planilhaUnidade = PlanilhaUnidadeDAO::buscar();


        $projetoDAO = new Projetos();
        $projeto = $projetoDAO->buscaProjetosProdutosAnaliseInicial(array('p.IdPRONAC = ?' => $idPronac, 'd.idProduto = ?' => $idProduto, 'd.stPrincipal = ?' => $stPrincipal));
        $this->view->projeto = $projeto[0];

        /* ITEM */
        $PlanilhaDAO = new PlanilhaProjeto();
        $planilha = $PlanilhaDAO->buscarAnaliseCustos(array('PPJ.idPlanilhaProjeto= ?' => $idPlanilhaProjeto));
        $this->view->dadosItem = $planilha[0];
    }

    /**
     * M�todo salvaitem()
     * Salva os itens da planilha
     * @param idPronac
     * @param idProduto
     * @param stPrincipal
     * @param idPlanilhaProjeto
     * @return void
     */
    public function salvaritemAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $idusuario = $auth->getIdentity()->usu_codigo;

        $dados = $_POST;
        $idPlanilhaProjeto = $_POST['idPlanilhaProjeto'];
        $idPronac = $_POST['idPronac'];
        $idProduto = $_POST['idProduto'];
        $stPrincipal = $_POST['stPrincipal'];

        unset($dados['idPlanilhaProjeto']);
        unset($dados['exibirContadorTextarea']);
        unset($dados['idPronac']);
        unset($dados['idProduto']);
        unset($dados['stPrincipal']);
        $dados['ValorUnitario'] = str_replace('.', '', $_POST['ValorUnitario']);
        $dados['ValorUnitario'] = str_replace(',', '.', $dados['ValorUnitario']);
        $dados['Justificativa'] = utf8_decode($_POST['Justificativa']);
        $dados['idUsuario'] = $idusuario;
        $where = array('idPlanilhaProjeto = ?' => $idPlanilhaProjeto);

        $planilhaProjeto = new PlanilhaProjeto();

        if ($planilhaProjeto->alterar($dados, $where)) {
            echo "Salvo com sucesso!";
        }
        else {
            echo "N?o foi poss&iacute;vel salvar!";
        }

    }


    /**
     * M�todo fecharparecer()
     * informa os dados para conclus�o da an�lise
     * @return void
     */
    public function fecharparecerAction() {

        $auth = Zend_Auth::getInstance(); // pega a autentica��o
        $idusuario 	= $auth->getIdentity()->usu_codigo;
        $dtAtual = Date("Y/m/d h:i:s");

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $codOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o

        $idPronac 	 	= $this->_request->getParam("idPronac");
        $idProduto 	 	= $this->_request->getParam("idProduto");
        $idDistribuirParecer 	= $this->_request->getParam("idD");
        $stPrincipal            = $this->_request->getParam("stPrincipal");
        $this->view->totaldivulgacao = "true";

        /**********************************************************************************************************/
        // Valida��o do 20%

        //valor total do projeto V1

        $planilhaProjeto = new PlanilhaProjeto();
        $valorProjeto = $planilhaProjeto->somarPlanilhaProjeto($idPronac, 109);

        //Validacao dos 20%
        if ( $valorProjeto['soma'] > 0 && $stPrincipal == "1") {
            $this->view->totaldivulgacao = $this->validaRegra20Porcento($idPronac);
        }

        /**********************************************************************************************************/

        /**********************************************************************************************************/
        // Valida��o do 15%
        if ($stPrincipal == "1") //avaliacao da regra dos 15% so deve ser feita quando a analise for do produto principal
        {
            $Situacao = false;

            $V1 = '';
            $V2 = '';
            $V3 = '';
            $V4 = '';
            $V5 = '';
            $V6 = '';

            $tpPlanilha = 'CO'; // O que � isso?
            $planilhaProjeto = new PlanilhaProjeto();

            /*****************************************************************************************/
            /* V1 */

            $whereTotalV1['PAP.IdPRONAC = ?'] = $idPronac;
            $whereTotalV1['PAP.FonteRecurso = ?'] = 109;
            $whereTotalV1['PAP.idPlanilhaItem <> ? '] = 206;

            $valorProjeto15 = $planilhaProjeto->somaDadosPlanilha($whereTotalV1);
            //$this->view->totalsugerido = $valorProjeto['soma'] ? $valorProjeto['soma'] :0;
            //echo 'Valor Total do Projeto :';
            $V1 = $valorProjeto15['soma'];

            //x($this->formatarReal($V1));

            /*****************************************************************************************/
            /* V2 */
            $whereTotalV2['PAP.IdPRONAC = ?'] = $idPronac;
            $whereTotalV2['PAP.FonteRecurso = ?'] = 109;
            $whereTotalV2['PAP.idEtapa = ? '] = 4;
            $whereTotalV2['PAP.idProduto = ?'] = 0;
            $whereTotalV2['PAP.idPlanilhaItem not in (?)'] = array(5249,206,1238);


            $valoracustosadministrativos = $planilhaProjeto->somaDadosPlanilha($whereTotalV2);
            //$this->view->totalsugerido = $valorProjeto['soma'] ? $valorProjeto['soma'] :0;
            //echo 'Custo Administrativo :';
            $V2 = $valoracustosadministrativos['soma'];


            //x($this->formatarReal($V2));

            /*****************************************************************************************/
            /* 15% */

            if($V1 > 0 and $valoracustosadministrativos['soma'] < $valorProjeto['soma']) {
                //Calcula os 15% do valor total do projeto V3
                $quinzecentoprojeto = $V1 * 0.15;

                //x('15% = '.$this->formatarReal($quinzecentoprojeto));
                //$this->view->V3 = $quinzecentoprojeto;
                //Subtrai os custos administrativos pelos 15% do projeto (V2 - V3)
                $verificacaonegativo = $valoracustosadministrativos['soma'] - $quinzecentoprojeto;
                //$this->view->V4 = $verificacaonegativo;
                //V4
                if($verificacaonegativo < 0) {
                    //x(0);
                    $this->view->verifica15porcento = 0;
                }
                else {
                    //V1 - V4 = V5
                    /*V5*/$valorretirar = /*V1*/$V1 - /*V4*/$verificacaonegativo;
                    //$this->view->V5 = $valorretirar;
                    /*V6*/$quinzecentovalorretirar = /*V5*/$valorretirar * 0.15;
                    //$this->view->V6 = $quinzecentovalorretirar;
                    //V2 - V6
                    //$valorretirarplanilha = $quinzecentoprojeto - $quinzecentovalorretirar; //(codigo antigo V3 - V6)
                    $valorretirarplanilha = $valoracustosadministrativos['soma'] - $quinzecentovalorretirar; //(correcao V2 - V6)
                    $this->view->verifica15porcento = $valorretirarplanilha;

                    //x($this->formatarReal($valorretirarplanilha));
                }

            }
            else {
                $this->view->verifica15porcento = $valoracustosadministrativos['soma'];
                //x($valoracustosadministrativos['soma']);
            }


            //$this->view->verifica15porcento = 0;
            //die();
        } //fecha if produto principal
        /**********************************************************************************************************/


        if($_POST) {
            $justificativa  	 = trim(strip_tags($this->_request->getParam("justificativa")));
            $tbDistribuirParecer    = new tbDistribuirParecer();
            $dadosWhere["t.idDistribuirParecer = ?"]    = $idDistribuirParecer;
            $buscaDadosProjeto 	= $tbDistribuirParecer->dadosParaDistribuir($dadosWhere);

            //xd($buscaDadosProjeto);

            try {
                $tbDistribuirParecer->getAdapter()->beginTransaction();
                foreach ($buscaDadosProjeto as $dp):

                    // DEVOLVER PARA O COORDENADOR ( PARECERISTA )

                    $dados = array(
                            'idOrgao' => $dp->idOrgao,
                            'DtEnvio' => $dp->DtEnvio,
                            'idAgenteParecerista' => $dp->idAgenteParecerista,
                            'DtDistribuicao' => $dp->DtDistribuicao,
                            'DtDevolucao' => new Zend_Db_Expr("GETDATE()"),
                            'DtRetorno' => null,
                            'FecharAnalise' => 0,
                            'Observacao' => $justificativa,
                            'idUsuario' => $idusuario,
                            'idPRONAC' => $dp->IdPRONAC,
                            'idProduto' => $dp->idProduto,
                            'TipoAnalise' => $dp->TipoAnalise,
                            'stEstado' => 0,
                            'stPrincipal' => $dp->stPrincipal,
                            'stDiligenciado' => null
                    );

                    $where['idDistribuirParecer = ?'] = $idDistribuirParecer;

                    $salvar = $tbDistribuirParecer->alterar(array('stEstado' => 1), $where);

                    $insere = $tbDistribuirParecer->inserir($dados);

                endforeach;

                $tbDistribuirParecer->getAdapter()->commit();

                parent::message("An�lise conclu�da com sucesso !", "Analisarprojetoparecer/index" ,"CONFIRM");

            } catch (Zend_Db_Exception $e) {

                $tbDistribuirParecer->getAdapter()->rollBack();
                parent::message("Error".$e->getMessage(), "Analisarprojetoparecer/fecharparecer/idD/".$idDistribuirParecer,"ERROR");
            }



        }
        else {
            $idPronac    = $this->_request->getParam("idPronac");
            $idProduto   = $this->_request->getParam("idProduto");
        }

        $projetos = new Projetos();
        $dadosProjetoProduto = $projetos->dadosFechar($this->getIdUsuario, $idPronac, $idDistribuirParecer);

        $this->view->dados = $dadosProjetoProduto;
        $this->view->idpronac = $idPronac;

    }

    /**
     * M�todo formatarReal()
     * Converte para o formato Brasileiro
     * @param moeda
     * @return String
     */
    function formatarReal($moeda) {
        if (!empty($moeda)) {
            $moeda = number_format($moeda, 2, ',', '.');
            return "R$ " . $moeda;
        }
        else {
            return "";
        }
    }

    /**
     * M�todo url()
     * Monta a URL
     * @param array
     * @param name
     * @param reset
     * @param encode
     * @return void
     */
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true) {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        return $router->assemble($urlOptions, $name, $reset, $encode);
    }

    /**
     * M�todo prestacaodecontasfinanceiro()
     * @return void
     */
    public function prestacaodecontasfinanceiroAction() {
        $this->_helper->layout->disableLayout(); // desabilita o layout
        //$this->_helper->viewRenderer->setNoRender(true);

        $auth = Zend_Auth::getInstance();
        $this->_helper->layout->disableLayout();

        $idusuario = $auth->getIdentity()->usu_codigo;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $idOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o
        $UsuarioDAO = new Usuario();
        $agente = $UsuarioDAO->getIdUsuario($idusuario);
        $idAgenteParecerista = $agente['idAgente'];

        $idProduto = $this->_request->getParam('idProduto');

        $where = array( 'd.idAgenteParecerista = ?' => $idAgenteParecerista, 'p.Orgao = ?' => $idOrgao, 'idProduto = ?' => $idProduto);

        $projeto = new Projetos();
        $resp = $projeto->buscaProjetosProdutosExecucaoObjeto($where, false);

        $this->view->qtdRegistro = count($resp);
        $this->view->dados = $resp;

    }

    /**
     * M�todo consolidacaodoprojeto()
     * Consolida os dados do Projeto
     * @param idPronac
     * @param idProduto
     * @param stPrincipal
     * @param idPlanilhaProjeto
     * @return void
     */
    public function consolidacaoprojetoAction() {
        $auth = Zend_Auth::getInstance();
        $idusuario = $auth->getIdentity()->usu_codigo;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $idOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o

        $idProduto 		= $this->_request->getParam('idProduto');
        $stPrincipal 		= $this->_request->getParam('stPrincipal');
        $areaCultural 		= $this->_request->getParam('areaCultural');
        $segmentoCultural 	= $this->_request->getParam('segmentoCultural');
        $enquadramentoProjeto   = $this->_request->getParam('enquadramentoProjeto');
        $dsConsolidacao  	= $this->_request->getParam("dsConsolidacao");
        $idPronac 		= $this->_request->getParam('idPronacP');
        $anoProjeto 		= $this->_request->getParam('AnoProjeto');
        $sequencial 		= $this->_request->getParam('Sequencial');
        $parecerProjeto 	= $this->_request->getParam('parecerProjeto');
        $sugeridoReal 		= Mascara::delMaskMoeda($this->_request->getParam('sugeridoReal'));
        $sugeridoReal 		= str_replace("R$","",$sugeridoReal);

        $pa = new paChecarLimitesOrcamentario();
        $resultadoCheckList = $pa->exec($idPronac, 2);
        $i = 0;
        foreach ($resultadoCheckList as $resultado) {
            if($resultado->Observacao == 'PENDENTE'){
                $i++;
            }
        }

        if($i > 0){
            $this->view->resultadoCheckList = $resultadoCheckList;
        } else {
            $this->_helper->layout->disableLayout();

            try {
                /** Fazendo um Update no Projeto enquadrando na �rea e Segmento ******************************/
                if($areaCultural <> 0) {
                    $projetoDAO = new Projetos();
                    $dadosProjeto = array('Area'=> $areaCultural, 'Segmento' => $segmentoCultural, 'Logon' => $idusuario);
                    $where['IdPRONAC = ?'] = $idPronac;
                    $alteraProjeto = $projetoDAO->update($dadosProjeto, $where);
                }
                /**********************************************************************************************/

                /** Gravando as informa��es do enquadramento do Projeto ***************************************/
                $enquadramentoDAO = new Enquadramento();
                $dadosEnquadramento = array(
                    'IdPRONAC'=> $idPronac,
                    'AnoProjeto' => $anoProjeto,
                    'Sequencial'=> $sequencial,
                    'Enquadramento' => $enquadramentoProjeto,
                    'DtEnquadramento' => new Zend_Db_Expr("GETDATE()"),
                    'Observacao' => '',
                    'Logon' => $idusuario
                );
                $whereBuscarDados  = array('IdPRONAC = ?' => $idPronac, 'AnoProjeto = ?' => $anoProjeto, 'Sequencial = ?' => $sequencial);
                $buscarEnquadramento = $enquadramentoDAO->buscar($whereBuscarDados);
                if(count($buscarEnquadramento) > 0){
                    $buscarEnquadramento = $buscarEnquadramento->current();
                    $whereUpdate = 'IdEnquadramento = '.$buscarEnquadramento->IdEnquadramento;
                    $alteraEnquadramento = $enquadramentoDAO->alterar($dadosEnquadramento, $whereUpdate);
                } else {
                    $insereEnquadramento = $enquadramentoDAO->inserir($dadosEnquadramento);
                }
                $buscaEnquadramento = $enquadramentoDAO->buscarDados($idPronac, null, false);
                /**********************************************************************************************/

                $parecerDAO = new Parecer();
                $dadosParecer = array(
                        'idPRONAC' => $idPronac,
                        'AnoProjeto' => $anoProjeto,
                        'Sequencial' => $sequencial,
                        'TipoParecer' => 1,
                        'ParecerFavoravel' => $parecerProjeto,
                        'DtParecer' => new Zend_Db_Expr("GETDATE()"),
                        'NumeroReuniao' => null,
                        'ResumoParecer' => $dsConsolidacao,
                        'SugeridoReal' => $sugeridoReal,
                        'Atendimento' => 'S',
                        'idEnquadramento' => $buscaEnquadramento['IdEnquadramento'],
                        'stAtivo' => 1,
                        'idTipoAgente' => 1,
                        'Logon' => $idusuario
                );

                $buscarParecer = $parecerDAO->buscar($whereBuscarDados);
                if(count($buscarParecer) > 0){
                    $buscarParecer = $buscarParecer->current();
                    $whereUpdateParecer = 'IdParecer = '.$buscarParecer->IdParecer;
                    $alteraParecer = $parecerDAO->alterar($dadosParecer, $whereUpdateParecer);
                } else {
                    $insereParecer = $parecerDAO->inserir($dadosParecer);
                }
                /**********************************************************************************************/
                parent::message("Projeto consolidado com sucesso.", "Analisarprojetoparecer/produto?idPronac=".$idPronac."&idProduto=".$idProduto."&stPrincipal=".$stPrincipal,"CONFIRM");
            } catch (Exception $e) {
                parent::message("Error: ".$e->getMessage(), "Analisarprojetoparecer/produto?idPronac=".$idPronac."&idProduto=".$idProduto."&stPrincipal=".$stPrincipal,"ERROR");
            }
        }
    }

}