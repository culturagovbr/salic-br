<?php

class Proposta_Model_DbTable_PreProjeto extends MinC_Db_Table_Abstract
{
    protected $_schema = "sac";
    protected $_name = "PreProjeto";
    protected $_primary = "idPreProjeto";

    public $_totalRegistros = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function retirarProjetos($idUsuario, $idUsuarioR, $idAgente)
    {
        $where['idUsuario = ?'] = $idUsuarioR;
        $where['idAgente = ?'] = $idAgente;

        return $this->update(array('idUsuario' => $idUsuario), $where);
    }

    public function listaProjetos($idUsuario)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $subSql = $this->select()
            ->from(array('pr' => 'projetos'), array('*'), $this->_schema)
            ->where('pr.idProjeto = p.idPreProjeto');

        $p = array(
            'p.idPreProjeto',
            'idAgente',
            'NomeProjeto',
            'Mecanismo',
            'stTipoDemanda'
        );

        $sql = $this->select()
            ->from(array('p' => 'PreProjeto'), $p, $this->_schema)
            ->where('stEstado = 1')
            ->where("stTipoDemanda like 'NA'")
            ->where('idUsuario = ?', $idUsuario)
            ->where(new Zend_Db_Expr("not exists ($subSql)"));

        return $db->fetchAll($sql);
    }

    public function buscar($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);

        $slct->from($this, array("*",
            "dtiniciodeexecucaoform" => $this->getExpressionToChar("DtInicioDeExecucao"),
            "dtfinaldeexecucaoform" => $this->getExpressionToChar("DtFinalDeExecucao"),
            "dtatotombamentoform" => $this->getExpressionToChar("DtAtoTombamento"),
            "dtaceiteform" => $this->getExpressionToChar("dtAceite"),
            "dtarquivamentoform" => $this->getExpressionToChar("DtArquivamento"),
        ));

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        //adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        return $this->fetchAll($slct);
    }

    public function buscaCompleta(
        $where = array(),
        $order = array(),
        $tamanho = -1,
        $inicio = -1
    )
    {

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name),
            array("a.*",
                "a.ResumoDoProjeto" => 'CAST("a"."ResumoDoProjeto" AS TEXT) as ResumoDoProjeto',
                "a.Objetivos" => 'CAST("a"."Objetivos" AS TEXT) as Objetivos',
                "a.Justificativa" => 'CAST("a"."Justificativa" AS TEXT) as Justificativa',
                "a.Acessibilidade" => 'CAST("a"."Acessibilidade" AS TEXT) as Acessibilidade',
                "a.DemocratizacaoDeAcesso" => 'CAST("a"."DemocratizacaoDeAcesso" AS TEXT) as DemocratizacaoDeAcesso',
                "a.EtapaDeTrabalho" => 'CAST("a"."EtapaDeTrabalho" AS TEXT) as EtapaDeTrabalho',
                "a.FichaTecnica" => 'CAST("a"."FichaTecnica" AS TEXT) as FichaTecnica',
                "a.Sinopse" => 'CAST("a"."Sinopse" AS TEXT) as Sinopse',
                "a.ImpactoAmbiental" => 'CAST("a"."ImpactoAmbiental" AS TEXT) as ImpactoAmbiental',
                "a.EspecificacaoTecnica" => 'CAST("a"."EspecificacaoTecnica" AS TEXT) as EspecificacaoTecnica',
                "a.EstrategiadeExecucao" => 'CAST("a"."EstrategiadeExecucao" AS TEXT) as EstrategiadeExecucao',
                "a.DtInicioDeExecucaoForm" => $this->getExpressionToChar(DtInicioDeExecucao),
            ),
            $this->_schema);

        $slct->joinInner(array('ag' => 'Agentes'),
            'a.idAgente = ag.idAgente',
            array("ag.CNPJCPF as CNPJCPF"),
            $this->getSchema('agentes'));

        $slct->joinInner(array('m' => 'Nomes'),
            'a.idAgente = m.idAgente',
            array("m.Descricao as NomeAgente"),
            $this->getSchema('agentes'));

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        $slct->where(new Zend_Db_Expr(
            'NOT EXISTS(
                    SELECT 1 
                      FROM "' . $this->getSchema('sac') . '"."Projetos" pr 
                     WHERE "a"."idPreProjeto" = "pr"."idProjeto" 
                       AND "pr"."Situacao" <> ' . "'E90')"
        ));

        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
//xd($slct->assemble());
        return $this->fetchAll($slct);
    }

    public function salvarMovimentacao($arrData)
    {
        $date = new DateTime();
        if (!$this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            $mprMovimentacao = new Proposta_Model_TbMovimentacaoMapper();
            $arrMovimentacaoDb = $mprMovimentacao->findBy(array('idProjeto' => $arrData['idPreProjeto']));
            if (empty($arrMovimentacaoDb)) {
                $arrMovimentacao = array();
                $arrMovimentacao['idProjeto'] = $arrData['idPreProjeto'];
                $arrMovimentacao['Movimentacao'] = 95;
                $arrMovimentacao['DtMovimentacao'] = $date->format('Y-m-d H:i:sP');
                $arrMovimentacao['stEstado'] = 0;
                $arrMovimentacao['Usuario'] = $arrData['idUsuario'];
                $mprMovimentacao->save(new Proposta_Model_TbMovimentacao($arrMovimentacao));
            }
        }
    }

    public function salvar($dados)
    {
        if (!empty ($dados['idPreProjeto'])) {
            $rsPreProjeto = $this->findBy(
                [
                    'idPreProjeto' => $dados['idPreProjeto']
                ]);
        } else {
            unset($dados['idPreProjeto']);
            $id = $this->insert($dados);
//            $id = $this->insert(array_filter($dados));
            $dados['idPreProjeto'] = $id;
            $this->salvarMovimentacao($dados);
            return $id;
        }

        $objPreprojeto = new Proposta_Model_PreProjeto($dados);
        $objPreprojetoMapper = new Proposta_Model_PreProjetoMapper();
        $id = $objPreprojetoMapper->save($objPreprojeto);

        if ($id) {
            return $id;
        }
        return false;
    }

    public function consultaTodosProjetos($idAgente, $idResponsavel, $arrBusca)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $a = array(
            new Zend_Db_Expr('0 as Ordem'),
            'a.idPreProjeto',
            'a.NomeProjeto',
            'a.idUsuario',
            'a.idAgente'
        );

        $subSql = $this->select()
            ->from(array('pr' => 'projetos'), new Zend_Db_Expr('1'), $this->_schema)
            ->where('a.idPreProjeto = pr.idprojeto');

        $sql = $this->select()
            ->from(array('a' => 'PreProjeto'), $a, $this->_schema)
            ->join(array('ag' => 'agentes'), 'a.idAgente = ag.idAgente', 'ag.CNPJCPF AS CNPJCPF', $this->getSchema('agentes'))
            ->join(array('m' => 'Nomes'), 'a.idAgente = m.idAgente', 'm.Descricao AS NomeAgente', $this->getSchema('agentes'))
            ->where('a.idAgente = ?', $idAgente)
            ->where(new Zend_Db_Expr("NOT EXISTS($subSql)"));

        foreach ($arrBusca as $value) {
            $sql->where($value);
        }

        $aSql = array(
            new Zend_Db_Expr('1 as Ordem'),
            'a.idPreProjeto',
            'a.NomeProjeto',
            'a.idUsuario',
            'a.idAgente'
        );

        $sql2 = $this->select()
            ->from(array('a' => 'PreProjeto'), $aSql, $this->_schema)
            ->join(array('ag' => 'agentes'), '(a.idAgente = ag.idAgente)', 'ag.CNPJCPF AS CNPJCPF', $this->getSchema('agentes'))
            ->join(array('m' => 'Nomes'), '(a.idAgente = m.idAgente)', 'm.Descricao AS NomeAgente', $this->getSchema('agentes'))
            ->join(array('s' => 'SGCacesso'), 'a.idUsuario = s.IdUsuario', null, $this->getSchema('controledeacesso'))
            ->where('a.idusuario = ?', $idResponsavel)
            ->where('ag.CNPJCPF <> s.Cpf')
            ->where(new Zend_Db_Expr('NOT EXISTS(SELECT 1 FROM sac.Projetos pr WHERE  a.idPreProjeto = pr.idprojeto)'));

        foreach ($arrBusca as $value) {
            $sql2->where($value);
        }

        $sql = $this->select()->union(array($sql, $sql2), Zend_Db_Select::SQL_UNION_ALL)
            ->order(new Zend_Db_Expr('1'))
            ->order('m.Descricao ASC');

        return $db->fetchAll($sql);
    }

    public function consultaprojetos($idagente)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()->from('PreProjeto', array('idPreProjeto', 'idAgente', 'NomeProjeto', 'Mecanismo'), $this->_schema)
            ->where('idagente = ?', $idagente)
            ->order('nomeprojeto');

        return $db->fetchAll($sql);
    }

    public function inserirProposta($dados)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $cadastrar = $db->insert("sac.PreProjeto", $dados);

        if ($cadastrar) {
            return true;
        } else {
            return false;
        }
    }

    public function alterarDados($dados, $where)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $cadastrar = $db->update("sac.PreProjeto", $dados, $where);

        if ($cadastrar) {
            return true;
        } else {
            return false;
        }
    }

    public function listaUF()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()->from('UF', array('*'), $this->getSchema('agentes'))->order('Sigla');

        return $db->fetchAll($sql);
    }

    public function buscaIdAgente($CNPJCPF)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()->from('Agentes', array('*'), $this->getSchema('agentes'))->where('CNPJCPF = ?', $CNPJCPF);

        return $db->fetchAll($sql);
    }

    public function inserirAgentes($dadosAgentes)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $Agentes = $db->insert("agentes.Agentes", $dadosAgentes);
    }

    public function inserirNomes($dadosNomes)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $Nomes = $db->insert("agentes.Nomes", $dadosNomes);
    }

    public function inserirEnderecoNacional($dadosEnderecoNacional)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $Nomes = $db->insert("agentes.EnderecoNacional", $dadosEnderecoNacional);
    }

    public function inserirVisao($dadosVisao)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $Nomes = $db->insert("agentes.Visao", $dadosVisao);
    }

    public function editarproposta($idPreProjeto)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()->from('PreProjeto', array('*'), $this->_schema)->where('idPreProjeto = ?', $idPreProjeto);

        return $db->fetchAll($sql);
    }

    public function recuperarTecnicosOrgao($idOrgaoSuperior)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()->from('vwUsuariosOrgaosGrupos', array('usu_codigo', 'uog_orgao'), 'tabelas')
            ->where('sis_codigo=21')
            ->where('gru_codigo=92')
            ->where('uog_status = 1')
            ->where('uog_orgao = ?', $idOrgaoSuperior);

        return $db->fetchAll($sql);
    }

    public function listarDiligenciasPreProjeto($consulta = array(), $retornaSelect = false)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('pre' => $this->_name),
            array('nomeProjeto' => 'pre.NomeProjeto', 'pronac' => 'pre.idPreProjeto'),
            $this->_schema
        );

        $select->joinInner(
            array('aval' => 'tbAvaliacaoProposta'),
            'aval.idProjeto = pre.idPreProjeto',
            array(
                'aval.stProrrogacao',
                'idDiligencia' => 'aval.idAvaliacaoProposta',
                'dataSolicitacao' => "to_char(\"aval\".\"DtAvaliacao\", 'YYYY-MM-DD HH24:MI:SS')",
                'dataResposta' => "to_char(\"aval\".\"dtResposta\", 'YYYY-MM-DD HH24:MI:SS')",
                'Solicitacao' => 'aval.Avaliacao',
                'Resposta' => 'aval.dsResposta',
                'aval.idCodigoDocumentosExigidos',
                'aval.stEnviado'
            ),
            $this->_schema
        );

        $select->joinLeft(
            array('arq' => 'scCorp.tbArquivo'),
            'arq.idArquivo = aval.idArquivo',
            array(
                'arq.nmArquivo',
                'arq.idArquivo'
            ),
            $this->getSchema('bdcorporativo')
        );

        $select->joinLeft(
            array('a' => 'Agentes'),
            'pre.idAgente = a.idAgente',
            array(
                'a.idAgente'
            ),
            $this->getSchema('agentes')
        );

        $select->joinLeft(
            array('n' => 'Nomes'),
            'a.idAgente = n.idAgente',
            array(
                'n.Descricao'
            ),
            $this->getSchema('agentes')
        );

        foreach ($consulta as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        if ($retornaSelect) {
            return $select;
        } else {
            return $this->fetchAll($select);
        }
    }

    function dadosPreProjeto($consulta = array())
    {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('pre' => $this->_name),
            array(
                'nomeProjeto' => 'pre.NomeProjeto',
                'pronac' => 'pre.idPreProjeto'
            )
        );

        foreach ($consulta as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        return $this->fetchAll($select);

    }

    public function buscarAgentePreProjeto($consulta = array())
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('pre' => $this->_name),
            array(
                'pre.idAgente'
            ),
            $this->_schema
        );

        foreach ($consulta as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        return $this->fetchAll($select);
    }

    public function listaAvaliadores($where = array())
    {

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('pp' => $this->_name),
            array('pp.idPreProjeto')
        );
        $slct->joinInner(
            array('ave' => 'tbAvaliadorEdital'),
            'ave.idEdital = pp.idEdital',
            array('ave.idAvaliador'),
            'bdcorporativo.scSAC'
        );
        $slct->joinInner(array('nom' => 'Nomes'),
            'nom.idAgente = ave.idAvaliador',
            array('nom.Descricao'),
            $this->getSchema('agentes')
        );
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        return $this->fetchAll($slct);
    }

    public function listaApenasAvaliadores($where = array())
    {

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('pp' => $this->_name),
            array('')
        );
        $slct->joinInner(
            array('ave' => 'tbAvaliadorEdital'),
            'ave.idEdital = pp.idEdital',
            array(new Zend_Db_Expr('distinct(ave.idAvaliador)')),
            'bdcorporativo.scSAC'
        );
        $slct->joinInner(array('nom' => 'Nomes'),
            'nom.idAgente = ave.idAvaliador',
            array('nom.Descricao'),
            $this->getSchema('agentes')
        );
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        return $this->fetchAll($slct);
    }

    public function buscarPropostaEditalCompleto($where = array())
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('p' => $this->_name),
            array('p.idPreProjeto,
                       p.idAgente,
                       p.NomeProjeto,
                       p.Mecanismo,
                       p.AgenciaBancaria,
                       p.DtInicioDeExecucao,
                       p.DtFinalDeExecucao,
                       p.stTipoDemanda,
                       p.idEdital,
                       CAST(p.ResumoDoProjeto as TEXT) as ResumoDoProjeto')

        );
        $slct->joinLeft(
            array('fd' => 'tbFormDocumento'),
            'fd.idEdital = p.idEdital OR p.idEdital IS NOT NULL',
            array('fd.nrFormDocumento', 'fd.nrVersaoDocumento'),
            'bdcorporativo.scQuiz'
        );

        $slct->joinInner(array('nm' => 'Nomes'),
            'nm.idAgente = p.idAgente',
            array('nm.Descricao as nomeAgente'),
            $this->getSchema('agentes')
        );
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        return $this->fetchAll($slct);
    }

    public function dadosProjetoDiligencia($idProjeto)
    {

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('p' => $this->_name),
            array(
                'idProjeto' => 'p.idPreProjeto',
                'p.NomeProjeto'
            )
        );
        $slct->joinInner(
            array("a" => "Agentes"),
            "a.idAgente = p.idAgente",
            array(),
            $this->getSchema('agentes')
        );
        $slct->joinInner(
            array("n" => "Nomes"),
            "a.idAgente = n.idAgente",
            array('Destinatario' => 'Descricao'),
            $this->getSchema('agentes')
        );
        $slct->joinInner(
            array("int" => "Internet"),
            "a.idAgente = int.idAgente",
            array('Email' => 'Descricao'),
            $this->getSchema('agentes')
        );

        $slct->where('p.idPreProjeto = ?', $idProjeto);
        $slct->where('a.Status = 0'); // Status do registro da pessoa, 0 - para ativo e 1 - para inativo

        return $this->fetchAll($slct);
    }

    public function analiseDeCustos($idPreProjeto)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $a = array(
            'a.idPreProjeto',
            'a.NomeProjeto',
        );

        $d = array(
            new Zend_Db_Expr("CONVERT(varchar(8),d.idPlanilhaEtapa) + ' - ' + d.Descricao AS Etapa"),
            'd.idPlanilhaEtapa'
        );

        $z = array(
            'z.idProduto',
            new Zend_Db_Expr("CASE WHEN z.idProduto = 0 THEN 'Administra&ccedil;&atilde;o do Projeto' ELSE c.Descricao END AS Produto"),
            new Zend_Db_Expr('z.Quantidade * z.Ocorrencia * z.ValorUnitario AS VlSolicitado'),
            'z.Quantidade',
            'z.Ocorrencia',
            'z.ValorUnitario',
            'z.QtdeDias',
            'z.TipoDespesa',
            'z.TipoPessoa',
            'z.Contrapartida',
            'z.dsJustificativa as JustificativaProponente',
            'z.FonteRecurso AS idFonte',
            new Zend_Db_Expr('ROUND(z.Quantidade * z.Ocorrencia * z.ValorUnitario, 2) AS Sugerido'),
            'z.idUsuario'
        );

        $f = array(
            'f.UF',
            'f.idUF',
            'f.Municipio',
            'f.idMunicipio',
        );

        $schema = parent::getStaticTableName('sac');
        $sql = $this->select()->from(array('a' => 'PreProjeto'), $a, $schema)
            ->join(array('z' => 'tbPlanilhaProposta'), 'z.idProjeto = a.idPreProjeto', $z, $schema)
            ->joinLeft(array('c' => 'Produto'), 'c.Codigo = z.idProduto', array(), $schema)
            ->join(array('d' => 'tbPlanilhaEtapa'), 'd.idPlanilhaEtapa = z.idEtapa', $d, $schema)
            ->join(array('e' => 'tbPlanilhaUnidade'), 'e.idUnidade = z.Unidade', array('e.Descricao AS Unidade'), $schema)
            ->join(array('i' => 'tbPlanilhaItens'), 'i.idPlanilhaItens = z.idPlanilhaItem', array('i.Descricao AS Item'), $schema)
            ->join(array('x' => 'Verificacao'), 'x.idVerificacao = z.FonteRecurso', array('x.Descricao AS FonteRecurso'), $schema)
            ->join(array('f' => 'vUFMunicipio'), 'f.idUF = z.UfDespesa AND f.idMunicipio = z.MunicipioDespesa', $f, parent::getStaticTableName('agentes'))
            ->where('a.idPreProjeto = ?', $idPreProjeto)
            ->order(array('x.Descricao', 'Produto', 'Etapa', 'UF', 'Item'));

        return $db->fetchAll($sql);
    }

    public function tecnicoTemProposta($idTecnico)
    {

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array("ap" => "tbAvaliacaoProposta"),
            array("*"),
            "SAC"
        );
        $slct->joinInner(
            array("m" => "tbMovimentacao"),
            "ap.idProjeto = m.idProjeto",
            array(),
            "SAC"
        );

        $condicao = new Zend_Db_Expr("select top 1 * from sac..Projetos p where p.idProjeto = ap.idProjeto");
        $slct->where("ap.idTecnico = ?", $idTecnico);
        $slct->where("m.stEstado = 0");
        $slct->where("m.Movimentacao in (96,97,128)");
        $slct->where("NOT EXISTS({$condicao})");

        $rs = $this->fetchAll($slct)->toArray();
        if (count($rs) > 0) {
            return true;
        }

        return false;
    }

    public function alteraproponente($idPreProjeto, $idAgente)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $where['idPreProjeto = ?'] = $idPreProjeto;

        return $db->update('sac.PreProjeto', array('idAgente' => $idAgente), $where);
    }

    public function alteraresponsavel($idPreProjeto, $idResponsavel)
    {
        $where['idPreProjeto = ?'] = $idPreProjeto;

        return $this->alterar(array('idUsuario' => $idResponsavel), $where);
    }

    public function buscarPropostaProjetos($where = array())
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('pp' => $this->_name),
            array(
                'pp.idPreProjeto',
                'pp.idAgente',
                'pp.NomeProjeto',
                'pp.Mecanismo',
                'pp.AgenciaBancaria',
                'pp.DtInicioDeExecucao',
                'pp.DtFinalDeExecucao',
                'pp.stTipoDemanda',
                'pp.idUsuario',
                'pp.idEdital',
                'CAST("pp"."ResumoDoProjeto" as TEXT) as ResumoDoProjeto'
            ),
            $this->_schema
        );

        $slct->joinLeft(
            array('resp' => 'SGCacesso'), 'resp.IdUsuario = pp.idUsuario',
            array('resp.Nome', 'resp.Cpf'),
            $this->getSchema('controledeacesso')
        );

        $slct->joinLeft(
            array('pr' => 'Projetos'),
            '"pp"."idPreProjeto" = "pr"."idProjeto"',
            array(
                'pr.idProjeto',
                'PRONAC' => new Zend_Db_Expr('"pr"."AnoProjeto" ' . $this->getExpressionConcat() . '"pr"."Sequencial"')
            ),
            $this->_schema
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order('pp.idPreProjeto');
        $slct->order('pp.NomeProjeto');

        return $this->fetchAll($slct);
    }

    public function buscarPropProjVinculados($idAgenteProponente)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
//        $slct->where('NOT EXISTS(SELECT * FROM Projetos p WHERE p.idProjeto = pp.idPreProjeto)', '');
        $subSql = $this->select()
            ->from(array('p' => 'projetos'), array('*'), $this->getSchema('sac'))
            ->where('p.idprojeto = pp.idPreProjeto');

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('pp' => $this->_name),
            array('pp.idPreProjeto',
                'pp.idAgente',
                'pp.NomeProjeto',
                'pp.Mecanismo',
                'pp.AgenciaBancaria',
                'pp.DtInicioDeExecucao',
                'pp.DtFinalDeExecucao',
                'pp.stTipoDemanda',
                'pp.idUsuario',
                'pp.idEdital')
        );

        $slct->joinLeft(
            array('resp' => 'SGCacesso'), 'resp.IdUsuario = pp.idUsuario',
            array('resp.Nome', 'resp.Cpf'), $this->getSchema('controledeacesso')
        );

        $slct->where('idAgente = ?', $idAgenteProponente);
        $slct->where('stEstado = ?', 1);
        $slct->where(new Zend_Db_Expr("NOT EXISTS ($subSql)"));
        $slct->order('pp.idPreProjeto');
        $slct->order('pp.nomeprojeto');

        return $this->fetchAll($slct);
    }

    public function buscarVinculadosProponenteDirigentes($arrayIdAgentes)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $subSql = $this->select()
            ->from(array('p' => 'Projetos'), array('*'), $this->getSchema('sac'))
            ->where('p.idProjeto = pp.idPreProjeto');

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('pp' => $this->_name),
            array('pp.idPreProjeto',
                'pp.idAgente',
                'pp.NomeProjeto',
                'pp.Mecanismo',
                'pp.AgenciaBancaria',
                'pp.DtInicioDeExecucao',
                'pp.DtFinalDeExecucao',
                'pp.stTipoDemanda',
                'pp.idUsuario',
                'pp.idEdital'),
            $this->_schema
        );

        $slct->joinLeft(
            array('resp' => 'SGCacesso'), 'resp.IdUsuario = pp.idUsuario',
            array('resp.Nome', 'resp.Cpf'),
            $this->getSchema('controledeacesso')
        );

        $slct->where('pp.idAgente in (?)', $arrayIdAgentes);
        $slct->where('stEstado = ?', true);
        $slct->where(new Zend_Db_Expr("NOT EXISTS ($subSql)"));
        $slct->order('pp.idPreProjeto');
        $slct->order('pp.NomeProjeto');

        return $this->fetchAll($slct);
    }

    public function gerenciarResponsaveisPendentes($siVinculo, $idAgente = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $k = array(
            'k.Cpf',
            'k.IdUsuario as idResponsavel',
            'k.Nome AS NomeResponsavel',
            'k.IdUsuario',
        );

        $v = array(
            'v.idVinculo',
            'v.siVinculo',
            'v.idUsuarioResponsavel',
        );

        $sql = $this->select()->distinct()
            ->from(array('a' => 'Agentes'), array(), $this->getSchema('agentes'))
            ->joinLeft(array('v' => 'tbVinculo'), 'a.idAgente = v.idAgenteProponente', $v, $this->getSchema('agentes'))
            ->join(array('k' => 'SGCacesso'), 'k.IdUsuario = v.idUsuarioResponsavel', $k, $this->getSchema('controledeacesso'))
            ->where('a.idAgente= ?', $idAgente)
            ->where('v.siVinculo = ?', $siVinculo)
            ->where('a.CNPJCPF <> k.Cpf')
            ->order('k.Nome  ASC');

        return $db->fetchAll($sql);
    }

    public function gerenciarResponsaveisVinculados($siVinculo, $idAgente = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()->distinct()
            ->from(array('j' => 'PreProjeto'), array(), $this->_schema)
            ->join(array('a' => 'Agentes'), 'j.idAgente = a.idAgente', array(), $this->getSchema('agentes'))
            ->join(array('v' => 'tbVinculoProposta'), 'j.idPreProjeto = v.idPreProjeto', array(), $this->getSchema('agentes'))
            ->join(array('y' => 'tbVinculo'), 'v.idVinculo = y.idVinculo', array('y.idVinculo', 'y.siVinculo', 'y.idUsuarioResponsavel'), $this->getSchema('agentes'))
            ->join(array('k' => 'SGCacesso'), 'k.IdUsuario = y.idUsuarioResponsavel', array('k.Cpf', 'k.IdUsuario as idResponsavel', 'k.Nome AS NomeResponsavel'), $this->getSchema('controledeacesso'))
            ->join(array('r' => 'SGCacesso'), 'r.Cpf = a.CNPJCPF', array('r.IdUsuario'), $this->getSchema('controledeacesso'))
            ->where('j.idAgente= ?', $idAgente)
            ->where('y.siVinculo = ?', $siVinculo)
            ->where('a.CNPJCPF <> k.Cpf')
            ->order(array('k.Nome  ASC'));

        return $db->fetchAll($sql);
    }

    public function listarPropostasResultado($idAgente, $idResponsavel, $idAgenteCombo)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $subSql = $this->select()
            ->from(array('pr' => 'projetos'), array('idprojeto'), $this->getSchema('sac'))
            ->where('a.idPreProjeto = pr.idprojeto');

        $sql = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.nomeprojeto'), $this->getSchema('sac'))
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->where('a.idAgente = ? ', $idAgente)
            ->where('a.stestado = 1')
            ->where("NOT EXISTS($subSql)")
            ->where("a.mecanismo = '1'");

        $subSql = $this->select()
            ->from(array('f' => 'projetos'), array('idprojeto'), $this->getSchema('sac'))
            ->where('a.idPreProjeto = f.idprojeto');

        $sql2 = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.nomeprojeto'), $this->getSchema('sac'))
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->join(array('c' => 'Vinculacao'), 'b.idAgente = c.idVinculoPrincipal', array(), $this->getSchema('agentes'))
            ->join(array('d' => 'Agentes'), 'c.idAgente = d.idAgente', array(), $this->getSchema('agentes'))
            ->join(array('e' => 'SGCacesso'), 'd.CNPJCPF = e.cpf', array(), $this->getSchema('controledeacesso'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->where('e.idusuario = ?', $idResponsavel)
            ->where('a.stestado = 1')
            ->where("NOT EXISTS($subSql)")
            ->where("a.mecanismo = '1'");

        $subSql = $this->select()
            ->from(array('z' => 'Projetos'), array('idprojeto'), $this->getSchema('sac'))
            ->where('a.idPreProjeto = z.idprojeto');

        $sql3 = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.nomeprojeto'), $this->getSchema('sac'))
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->join(array('c' => 'Nomes'), 'b.idAgente = c.idAgente', array('c.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('d' => 'SGCacesso'), 'a.idusuario = d.idusuario', array(), $this->getSchema('controledeacesso'))
            ->join(array('e' => 'tbVinculoProposta'), 'a.idPreProjeto = e.idPreProjeto', array(), $this->getSchema('agentes'))
            ->join(array('f' => 'tbVinculo'), 'e.idVinculo = f.idVinculo', array(), $this->getSchema('agentes'))
            ->where('a.stestado = 1')
            ->where("NOT EXISTS($subSql)")
            ->where("a.mecanismo = ?", "'1'")
            ->where("e.siVinculoProposta = ?", '2')
            ->where('f.idUsuarioResponsavel = ?', $idResponsavel);

        if (!empty($idAgenteCombo)) {
            $sql->where('b.idAgente = ?', $idAgenteCombo);
            $sql2->where('b.idAgente = ?', $idAgenteCombo);
            $sql3->where('b.idAgente = ?', $idAgenteCombo);
        }

        $sql = $this->select()->union(array($sql, $sql2, $sql3), Zend_Db_Select::SQL_UNION_ALL);

        return $db->fetchAll($sql);
    }

    public function listarPropostasCombo($idResponsavel)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()
            ->from(array('a' => 'PreProjeto'), null, $this->_schema)
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->joinLeft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('c' => 'SGCacesso'), 'b.CNPJCPF = c.Cpf', null, $this->getSchema('controledeacesso'))
            ->where('c.IdUsuario::varchar = ?', "$idResponsavel");

        $sql2 = $this->select();
        $sql2->from(array('a' => 'PreProjeto'), null, $this->_schema);
        $sql2->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'));
        $sql2->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'));
        $sql2->join(array('c' => 'tbVinculoProposta'), 'a.idPreProjeto = c.idPreProjeto', null, $this->getSchema('agentes'));
        $sql2->join(array('d' => 'tbVinculo'), 'c.idVinculo = d.idVinculo', null, $this->getSchema('agentes'));
        $sql2->join(array('f' => 'Agentes'), 'd.idAgenteProponente = f.idAgente', null, $this->getSchema('agentes'));
        $sql2->join(array('e' => 'SGCacesso'), 'f.CNPJCPF = e.Cpf', null, $this->getSchema('controledeacesso'));
        $sql2->where('e.IdUsuario::varchar = ?', "$idResponsavel");
        $sql2->where("c.siVinculoProposta = ?", '2');

        $sql3 = $this->select()
            ->from(array('a' => 'Agentes'), array('a.CNPJCPF', 'a.idAgente'), $this->getSchema('agentes'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = a.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('b' => 'Vinculacao'), 'a.idAgente = b.idVinculoPrincipal', null, $this->getSchema('agentes'))
            ->join(array('c' => 'Agentes'), 'b.idAgente = c.idAgente', null, $this->getSchema('agentes'))
            ->join(array('d' => 'SGCacesso'), 'c.CNPJCPF = d.Cpf', null, $this->getSchema('controledeacesso'))
            ->where('d.IdUsuario::varchar = ?', "$idResponsavel");

        $sql4 = $this->select()
            ->from(array('a' => 'Agentes'), array('a.CNPJCPF', 'a.idAgente'), $this->getSchema('agentes'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = a.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('b' => 'tbVinculo'), 'a.idAgente = b.idAgenteProponente', null, $this->getSchema('agentes'))
            ->join(array('c' => 'SGCacesso'), 'b.idUsuarioResponsavel = c.IdUsuario', null, $this->getSchema('controledeacesso'))
            ->where('b.siVinculo = ?', '2')
            ->where('c.IdUsuario::varchar = ?', "$idResponsavel");

        $sql5 = $this->select()
            ->from(array('a' => 'Agentes'), array('a.CNPJCPF', 'a.idAgente'), $this->getSchema('agentes'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = a.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('b' => 'SGCacesso'), 'a.CNPJCPF = b.Cpf', null, $this->getSchema('controledeacesso'))
            ->where('b.IdUsuario::varchar = ?', "$idResponsavel");

        $sql = $this->select()->union(array($sql, $sql2, $sql3, $sql4, $sql5))
            ->group(array('a.CNPJCPF', 'a.idAgente', 'n.Descricao'))
            ->order(array('3 asc'));
//xd($sql->assemble());
//        echo '<pre>';
//        print_r(str_replace('"', '', $sql->assemble()));
//        exit;

        return $db->fetchAll($sql);
    }

    public function relatorioPropostas2($where = array(), $having = array(), $order = array(), $tamanho = -1, $inicio = -1, $count = false, $dados = null)
    {
        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array("p" => $this->_name),
            array("idProjeto" => "idPreProjeto", "NomeProposta" => "NomeProjeto", "idAgente"),
            "SAC"
        );

        if (!($dados->proposta)) {
            $slct->joinInner(
                array("m" => "tbMovimentacao"),
                "p.idPreProjeto = m.idProjeto AND m.stEstado = 0",
                array(),
                "SAC"
            );
            $slct->joinInner(
                array("vr" => "Verificacao"),
                "m.movimentacao = vr.idVerificacao and vr.idTipo = 4",
                array(),
                "SAC"
            );
            $slct->joinInner(
                array("x" => "tbAvaliacaoProposta"),
                "p.idPreProjeto = x.idProjeto AND x.stEstado = 0",
                array(),
                "SAC"
            );

        }
        if (($dados->uf) || ($dados->municipio)) {
            $slct->joinInner(
                array("ab" => "Abrangencia"),
                "p.idPreProjeto = ab.idProjeto AND ab.stAbrangencia = 1",
                array(),
                "SAC"
            );
        }

        if ($dados->uf) {
            $slct->joinInner(
                array("uf" => "UF"),
                "uf.idUF = ab.idUF",
                array(),
                "agentes"
            );
        }

        if (($dados->uf) || ($dados->municipio)) {
            $slct->joinInner(
                array("mu" => "Municipios"),
                "mu.idMunicipioIBGE = ab.idMunicipioIBGE",
                array(),
                "agentes"
            );
        }

        if (($dados->area) || ($dados->segmento)) {
            $slct->joinInner(
                array("pdp" => "PlanoDistribuicaoProduto"),
                "pdp.idProjeto = p.idPreProjeto AND pdp.stPlanoDistribuicaoProduto = 1",
                array(),
                "SAC"
            );
        }

        $slct->joinLeft(
            array("pp" => "tbPlanilhaProposta"),
            "pp.idProjeto = p.idPreProjeto",
            array("valor" => new Zend_Db_Expr("sum(Quantidade*Ocorrencia*ValorUnitario)")),
            "SAC"
        );
        $slct->joinInner(
            array("ag" => "agentes"),
            "ag.idAgente = p.idAgente",
            array("ag.CNPJCPF"),
            "agentes"
        );
        $slct->joinInner(
            array("nm" => "nomes"),
            "nm.idAgente = p.idAgente",
            array("nm.Descricao as Proponente"),
            "agentes"
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->group(array("p.idPreProjeto", "p.NomeProjeto", "p.idAgente", "ag.CNPJCPF", "nm.Descricao"));

        //adiciona quantos filtros foram enviados
        foreach ($having as $coluna => $valor) {
            $slct->having($coluna, $valor);
        }

        if ($count) {
            $this->_totalRegistros = $this->fetchAll($slct)->count();
            return $this->_totalRegistros;
        }

        //adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        return $this->fetchAll($slct);
    }

    public function relatorioPropostas($where = array(), $having = array(), $order = array(), $tamanho = -1, $inicio = -1, $count = false)
    {

        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array("p" => $this->_name),
            array("idProjeto" => "idPreProjeto", "NomeProposta" => "NomeProjeto", "idAgente", "p.stEstado", "p.DtArquivamento"),
            "SAC"
        );

        $slct->joinInner(
            array("m" => "tbMovimentacao"), "p.idPreProjeto = m.idProjeto AND m.stEstado = 0",
            array('m.Movimentacao', 'm.stEstado AS estadoMovimentacao'), "SAC"
        );
        $slct->joinInner(
            array("vr" => "Verificacao"), "m.movimentacao = vr.idVerificacao and vr.idTipo = 4",
            array(), "SAC"
        );
        $slct->joinLeft(
            array("x" => "tbAvaliacaoProposta"), "p.idPreProjeto = x.idProjeto AND x.stEstado = 0",
            array('x.ConformidadeOK', 'x.stEstado AS estadoAvaliacao'), "SAC"
        );

        if (isset($where['ab.idUF = ?']) || isset($where['ab.idMunicipioIBGE = ?'])) {
            $slct->joinInner(
                array("ab" => "Abrangencia"), "p.idPreProjeto = ab.idProjeto AND ab.stAbrangencia = 1",
                array(), "SAC"
            );
        }

        if (isset($where['ab.idUF = ?'])) {
            $slct->joinInner(
                array("uf" => "UF"), "uf.idUF = ab.idUF",
                array(), "agentes"
            );
        }

        if (isset($where['ab.idUF = ?']) || isset($where['ab.idMunicipioIBGE = ?'])) {
            $slct->joinInner(
                array("mu" => "Municipios"), "mu.idMunicipioIBGE = ab.idMunicipioIBGE",
                array(), "agentes"
            );
        }

        if (isset($where['pdp.Area = ?']) || isset($where['pdp.Segmento = ?'])) {
            $slct->joinInner(
                array("pdp" => "PlanoDistribuicaoProduto"), "pdp.idProjeto = p.idPreProjeto AND pdp.stPlanoDistribuicaoProduto = 1",
                array(), "SAC"
            );
        }

        $slct->joinLeft(
            array("pp" => "tbPlanilhaProposta"), "pp.idProjeto = p.idPreProjeto",
            array("valor" => new Zend_Db_Expr("sum(Quantidade*Ocorrencia*ValorUnitario)")), "SAC"
        );

        $slct->joinInner(
            array("ag" => "agentes"), "ag.idAgente = p.idAgente",
            array("ag.CNPJCPF"), "agentes"
        );

        $slct->joinInner(
            array("nm" => "nomes"), "nm.idAgente = p.idAgente",
            array(
                "nm.Descricao as Proponente"
            ), "agentes"
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->where('NOT EXISTS (SELECT * FROM Projetos z WHERE z.idProjeto = p.idPreProjeto)');
        $slct->group(array("p.idPreProjeto", "p.NomeProjeto", "p.idAgente", "p.stEstado", "p.DtArquivamento", "m.Movimentacao", "m.stEstado", "x.ConformidadeOK", "x.stEstado", "ag.CNPJCPF", "nm.Descricao"));

        //adiciona quantos filtros foram enviados
        foreach ($having as $coluna => $valor) {
            $slct->having($coluna, $valor);
        }

        if ($count) {
            return $this->fetchAll($slct)->count();
        }

        //adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        return $this->fetchAll($slct);
    }

    public function buscarPropostaAdmissibilidade($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $p = array(
            'p.idPreProjeto AS idProjeto',
            'p.NomeProjeto AS NomeProposta',
            'p.idAgente'
        );

        $m = array(
            new Zend_Db_Expr('CONVERT(CHAR(20),m.DtMovimentacao, 120) AS DtMovimentacao'),
            new Zend_Db_Expr('DATEDIFF(d, m.DtMovimentacao, ' . $this->getDate() . ') AS diasDesdeMovimentacao'),
            'm.idMovimentacao',
            'm.Movimentacao AS CodSituacao',
        );

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $orgao = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = x.idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        //replace funcao: sac.fnNomeTecnicoMinc(a.idTecnico)
        $tecnico = $this->select()
            ->from(array('Usuarios'), 'usu_nome', 'tabelas')
            ->where('usu_codigo = x.idTecnico');

        $x = array(
            new Zend_Db_Expr('CONVERT(CHAR(20),x.DtAvaliacao, 120) AS DtAdmissibilidade'),
            new Zend_Db_Expr('DATEDIFF(d, x.DtAvaliacao, ' . $this->getDate() . ') AS diasCorridos'),
            'x.idTecnico AS idUsuario',
            'x.DtAvaliacao',
            'x.idAvaliacaoProposta',
            "($tecnico) AS Tecnico",
            "($orgao) AS idSecretaria",
        );

        $sql = $this->select()
            ->from(array("p" => $this->_name), $p, "SAC")
            ->join(array("m" => "tbMovimentacao"), 'p.idPreProjeto = m.idProjeto AND m.stEstado = 0', $m, "SAC")
            ->joinInner(array("x" => "tbAvaliacaoProposta"), "p.idPreProjeto = x.idProjeto AND x.stEstado = 0", $x, "SAC")
            ->joinInner(array("a" => "Agentes"), 'p.idAgente = a.idAgente', array('a.CNPJCPF'), $this->getSchema('agentes'))
            ->joinInner(array("y" => "Verificacao"), 'm.Movimentacao = y.idVerificacao', null, $this->_schema)
            ->joinLeft(array("ap1" => 'tbAvaliacaoProposta'), "p.idPreProjeto = ap1.idProjeto AND ap1.stEnviado = 'S'", array(new Zend_Db_Expr('DATEDIFF(d, ap1.DtEnvio, ' . $this->getDate() . ') AS diasDiligencia')), $this->_schema)
            ->joinLeft(array("ap2" => 'tbAvaliacaoProposta'), "p.idPreProjeto = ap2.idProjeto AND ap2.stEnviado = 'S'", array(new Zend_Db_Expr('DATEDIFF(d, ap2.dtResposta, ' . $this->getDate() . ') AS diasRespostaDiligencia')), $this->_schema)
            ->where(
                new Zend_Db_Expr('NOT EXISTS
                    (
                    SELECT TOP (1) IdPRONAC, AnoProjeto, Sequencial, UfProjeto, Area, Segmento, Mecanismo, NomeProjeto, Processo, CgcCpf, Situacao, DtProtocolo, DtAnalise, Modalidade, Orgao, OrgaoOrigem, DtSaida, DtRetorno, UnidadeAnalise, Analista, DtSituacao, ResumoProjeto, ProvidenciaTomada, Localizacao, DtInicioExecucao, DtFimExecucao, SolicitadoUfir, SolicitadoReal, SolicitadoCusteioUfir, SolicitadoCusteioReal, SolicitadoCapitalUfir, SolicitadoCapitalReal, Logon, idProjeto
                    FROM sac.Projetos AS u
                    WHERE (p.idPreProjeto = idProjeto)
                    )'
                )
            );

        // adicionando clausulas where
        foreach ($where as $coluna => $valor) {
            $sql->where($coluna . '?', $valor);
        }

        // adicionando clausulas order
        $sql->order($order);

        // retornando os registros conforme objeto select
        return $db->fetchAll($sql);
    }

    public function buscarPropostaAdmissibilidadeZend($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array("p" => $this->_name),
            array("idProjeto" => "idPreProjeto", "NomeProposta" => "NomeProjeto", "idAgente")
        );
        $slct->joinInner(
            array("m" => "tbMovimentacao"),
            "p.idPreProjeto = m.idProjeto AND m.stEstado = 0",
            array("idMovimentacao", "CodSituacao" => "m.Movimentacao", "DtMovimentacao" => "CONVERT(CHAR(20),m.DtMovimentacao, 120)", "diasDesdeMovimentacao" => "DATEDIFF(d, m.DtMovimentacao, " . $this->getDate() . ")"),
            "SAC"
        );
        $slct->joinLeft(
            array("x" => "tbAvaliacaoProposta"),
            "p.idPreProjeto = x.idProjeto AND x.stEstado = 0",
            array("idAvaliacaoProposta", "DtAdmissibilidade" => "CONVERT(CHAR(20),x.DtAvaliacao, 120)", "diasCorridos" => "DATEDIFF(d, x.DtAvaliacao, " . $this->getDate() . ")"),
            "SAC"
        );
        $slct->joinInner(
            array("a" => "Agentes"),
            "p.idAgente = a.idAgente",
            array("CNPJCPF"),
            "agentes"
        );
        $slct->joinInner(
            array("y" => "Verificacao"),
            "m.Movimentacao = y.idVerificacao",
            array("Situacao" => "Descricao"),
            "SAC"
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        //adicionando linha order ao select
        $slct->order($order);

        $this->_totalRegistros = $this->fetchAll($slct)->count();
        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        return $this->fetchAll($slct);


    }

    public function buscarTecnicosHistoricoAnaliseVisual($idOrgao)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $subSql = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = a.idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        $sql = $this->select()
            ->distinct()
            ->from(array('a' => 'tbAvaliacaoProposta'), array('a.idTecnico'), "SAC")
            ->join(array('p' => 'PreProjeto'), 'p.idPreProjeto = a.idProjeto', null, "SAC")
            ->join(array('u' => 'Usuarios'), 'u.usu_codigo = a.idTecnico', 'u.usu_nome as Tecnico', 'TABELAS')
            ->where('ConformidadeOK<>1')
            ->where('p.stEstado = 1')
            ->where("($subSql) = ?", $idOrgao)
            ->order('u.usu_nome ASC');

        // retornando os registros conforme objeto select
        return $db->fetchAll($sql);
    }

    public function buscarHistoricoAnaliseVisual($idOrgao, $idTecnico = null, $situacao = null, $dtInicio = null, $dtFim = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $p = array(
            'p.idPreProjeto',
            'p.NomeProjeto',
        );

        //replace funcao: sac.fnNomeTecnicoMinc(a.idTecnico)
        $tecnico = $this->select()
            ->from(array('Usuarios'), 'usu_nome', 'tabelas')
            ->where('usu_codigo = a.idTecnico');

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $subSql = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = a.idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        $a = array(
            'a.idTecnico',
            "($tecnico) as Tecnico",
            'a.DtEnvio',
            new Zend_Db_Expr('CONVERT(CHAR(20),a.DtAvaliacao, 120) AS DtAvaliacao'),
            'a.idAvaliacaoProposta',
            'a.ConformidadeOK',
            'a.stEstado',
            "($subSql) as idOrgao"
        );


        $sql = $this->select()
            ->from(array('a' => 'tbAvaliacaoProposta'), $a, $this->_schema)
            ->join(array('p' => 'PreProjeto'), 'p.idPreProjeto = a.idProjeto', $p, $this->_schema)
            ->where('ConformidadeOK<>1')
            ->where('p.stEstado = 1')
            ->where("($subSql) = ?", $idOrgao)
            ->order('p.idPreProjeto DESC')
            ->order('DtAvaliacao ASC')
            ->limit(20);

        if ($idTecnico) {
            $sql->where('a.idTecnico = ?', $idTecnico);
        }
        if ($situacao) {
            $sql->where('a.ConformidadeOK = ?', $situacao);
        }

        if ($dtInicio) {
            if ($dtFim) {
                $sql->where('a.DtAvaliacao > ? 00:00:00', $dtInicio);
                $sql->where('a.DtAvaliacao < ? 23:59:59', $dtFim);
            } else {
                $sql->where('a.DtAvaliacao > ? 00:00:00', $dtInicio);
                $sql->where('a.DtAvaliacao < ? 23:59:59', $dtInicio);
            }
        }

        // retornando os registros conforme objeto select
        return $db->fetchAll($sql);
    }

    public function buscarAvaliacaoHistoricoAnaliseVisual($idAvaliacao)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()
            ->from(array('a' => 'tbAvaliacaoProposta'), array('a.Avaliacao'), $this->_schema)
            ->where('a.idAvaliacaoProposta = ?', $idAvaliacao);

        return $db->fetchAll($sql);
    }

    public function buscarPropostaAnaliseVisualTecnico($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $db = $this->getAdapter();
        $vw = array(
            'idProjeto',
            'NomeProjeto',
            'Tecnico',
            'idOrgao',
            new Zend_Db_Expr('CONVERT(CHAR(20),vw.DtEnvio, 120) AS DtEnvio'),
            'ConformidadeOK',
            new Zend_Db_Expr('CONVERT(CHAR(20),vw.DtMovimentacao, 120) AS DtMovimentacao'),
            'QtdeDias'
        );

        $sql = $this->select()
            ->from(array('vw' => 'vwAnaliseVisualPorTecnico'), $vw, $this->_schema);

        ($order) ? $sql->order($order) : null;

        foreach ($where as $coluna => $valor) {
            $sql->where($coluna . ' ?', $valor);
        }

        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        return $db->fetchAll($sql);
    }

    public function buscarPropostaAnaliseDocumentalTecnico($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        //replace funcao: sac.fnNomeTecnicoMinc(a.idTecnico)
        $tecnico = $this->select()
            ->from(array('Usuarios'), 'usu_nome', 'tabelas')
            ->where('usu_codigo = a.idTecnico');

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $orgao = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = a.idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        //Replace da funcao: sac.fnDtUltimaDiligenciaDocumental(a.idProjeto)
        $diligencia = $this->select()->from(array('tbMovimentacao'), "max(DtMovimentacao)", $this->_schema)->where('Movimentacao = 97')
            ->where('idProjeto = a.idProjeto');

        $a = array(
            'a.idProjeto',
            "($tecnico) AS Tecnico",
            "($orgao) as idOrgao",
            new Zend_Db_Expr("CONVERT(CHAR(20), ({$diligencia}), 120) AS DtUltima")
        );

        $sql = $this->select()
            ->from(array('a' => 'tbAvaliacaoProposta'), $a, $this->_schema)
            ->join(array('p' => 'PreProjeto'), 'a.idProjeto=p.idPreProjeto', array('p.NomeProjeto'), $this->_schema)
            ->join(array('d' => 'vwDocumentosPendentes'), 'a.idProjeto = d.idProjeto', array('CodigoDocumento'), $this->_schema)
            ->join(array('de' => 'DocumentosExigidos'), 'd.CodigoDocumento = de.Codigo', array('Descricao as Documento'), $this->_schema);

        // adicionando clausulas where
        foreach ($where as $coluna => $valor) {
            $sql->where($coluna . '?', $valor);
        }

        $sql->order($order);

        // retornando os registros conforme objeto select
        return $db->fetchAll($sql);
    }

    public function buscarPropostaAnaliseFinal($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $db = $this->getAdapter();
        $vw = array(
            'idPreProjeto',
            'NomeProjeto',
            'Tecnico',
            'DtEnvio',
            'CONVERT(CHAR(20),DtMovimentacao, 120) AS DtMovimentacao',
            'DtAvaliacao',
            'Dias',
            'idOrgao',
            'ConformidadeOK',
            'QtdeDiasAguardandoEnvio'
        );

        $sql = $this->select()
            ->from(array('vw' => 'vwPropostaProjetoSecretaria'), $vw, $this->_schema);

        // adicionando clausulas where
        foreach ($where as $coluna => $valor) {
            $sql->where($coluna . ' = ?', $valor);
        }

        // adicionando clausulas order
        ($order) ? $sql->order($order) : null;

        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        // retornando os registros conforme objeto select
        return $db->fetchAll($sql);
    }

    public function buscarConformidadeVisualTecnico($idPreProjeto)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()
            ->from(array('vw' => 'vwConformidadeVisualTecnico'), 'tecnico', $this->_schema)
            ->where('idprojeto = ?', $idPreProjeto)
            ->query();

        // retornando os registros conforme objeto select
        return $sql->fetchAll();
    }

    public function buscarVisual($idUsuario = null, $order = array(), $tamanho = -1, $inicio = -1)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $p = array(
            'p.stTipoDemanda AS TipoDemanda',
            'p.idPreProjeto AS idProjeto',
            'p.NomeProjeto AS NomeProposta',
            'p.idAgente',
        );

        //replace funcao: sac.fnNomeTecnicoMinc(a.idTecnico)
        $tecnico = $this->select()
            ->from(array('Usuarios'), 'usu_nome', 'tabelas')
            ->where('usu_codigo = x.idTecnico');

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $orgao = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = x.idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        $x = array(
            'x.idTecnico AS idUsuario',
            "($tecnico) AS Tecnico",
            "($orgao) AS idSecretaria",
            new Zend_Db_Expr('CONVERT(CHAR(20),x.DtAvaliacao, 120) AS DtAdmissibilidade'),
            new Zend_Db_Expr('DATEDIFF(d, x.DtAvaliacao, ' . $this->getDate() . ') AS diasCorridos'),
            'x.idAvaliacaoProposta',
        );

        $m = array(
            'm.idMovimentacao',
            'm.Movimentacao AS CodSituacao',
        );

        $subSql = $this->select()->from('vwRedistribuirAnaliseVisual', array('idProjeto'), $this->_schema);

        if ($idUsuario !== null) {
            $subSql->where("($orgao) = ?", $idUsuario);
        }

        $subSql2 = $this->select()
            ->from(array('u' => 'Projetos'),
                array('IdPRONAC', 'AnoProjeto', 'Sequencial', 'UfProjeto', 'Area', 'Segmento', 'Mecanismo', 'NomeProjeto', 'Processo',
                    'CgcCpf', 'Situacao', 'DtProtocolo', 'DtAnalise', 'Modalidade', 'Orgao', 'OrgaoOrigem', 'DtSaida', 'DtRetorno', 'UnidadeAnalise',
                    'Analista', 'DtSituacao', 'ResumoProjeto', 'ProvidenciaTomada', 'Localizacao', 'DtInicioExecucao', 'DtFimExecucao', 'SolicitadoUfir',
                    'SolicitadoReal', 'SolicitadoCusteioUfir', 'SolicitadoCusteioReal', 'SolicitadoCapitalUfir', 'SolicitadoCapitalReal', 'Logon', 'idProjeto'),
                $this->_schema)
            ->where('p.idPreProjeto = idProjeto')
            ->limit(1);

        $sql = $this->select()
            ->from(array('p' => 'PreProjeto'), null, $this->_schema)
            ->join(array('m' => 'tbMovimentacao'), 'p.idPreProjeto = m.idProjeto AND m.stEstado = 0', $m, $this->_schema)
            ->join(array('x' => 'tbAvaliacaoProposta'), 'p.idPreProjeto = x.idProjeto AND x.stEstado = 0', $x, $this->_schema)
            ->join(array('a' => 'Agentes'), 'p.idAgente = a.idAgente', array('a.CNPJCPF'), $this->getSchema('agentes'))
            ->join(array('y' => 'Verificacao'), 'm.Movimentacao = y.idVerificacao', array('y.Descricao AS Situacao'), $this->_schema)
            ->where('p.stEstado = 1')
            ->where('m.Movimentacao NOT IN(96,128)')
            ->where(new Zend_Db_Expr("p.idPreProjeto IN( $subSql )"))
            ->where(new Zend_Db_Expr("NOT EXISTS ( $subSql2)"))
            ->order($order);

        return $db->fetchAll($sql);
    }

    public function buscarDocumental($idUsuario = null, $order = array(), $tamanho = -1, $inicio = -1)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $p = array(
            'p.idPreProjeto AS idProjeto',
            'p.NomeProjeto AS NomeProposta',
            'p.idAgente',
            'p.stTipoDemanda AS TipoDemanda'
        );

        //replace funcao: sac.fnNomeTecnicoMinc(a.idTecnico)
        $tecnico = $this->select()
            ->from(array('Usuarios'), 'usu_nome', 'tabelas')
            ->where('usu_codigo = x.idTecnico');

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $orgao = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = x.idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        // Replace da funcao: sac.fnIdOrgaoSuperiorAnalista(a.idTecnico)
        $orgaoSub = $this->select()
            ->from(array('vwUsuariosOrgaosGrupos'), 'org_superior', 'tabelas')
            ->where('usu_codigo = idTecnico')
            ->where('sis_codigo = 21')
            ->where('gru_codigo = 92')
            ->group('org_superior');

        $x = array(
            'x.idTecnico AS idUsuario',
            "($tecnico) AS Tecnico",
            "($orgao) AS idSecretaria",
            new Zend_Db_Expr('CONVERT(CHAR(20),x.DtAvaliacao, 120) AS DtAdmissibilidade'),
            new Zend_Db_Expr('DATEDIFF(d, x.DtAvaliacao, ' . $this->getDate() . ') AS diasCorridos'),
            'x.idAvaliacaoProposta',
        );

        $m = array(
            'm.idMovimentacao',
            'm.Movimentacao AS CodSituacao',
        );

        $subSql = $this->select()
            ->from(array('u' => 'Projetos'), array(
                'IdPRONAC', 'AnoProjeto', 'Sequencial', 'UfProjeto', 'Area', 'Segmento', 'Mecanismo', 'NomeProjeto', 'Processo', 'CgcCpf', 'Situacao',
                'DtProtocolo', 'DtAnalise', 'Modalidade', 'Orgao', 'OrgaoOrigem', 'DtSaida', 'DtRetorno', 'UnidadeAnalise', 'Analista', 'DtSituacao', 'ResumoProjeto',
                'ProvidenciaTomada', 'Localizacao', 'DtInicioExecucao', 'DtFimExecucao', 'SolicitadoUfir', 'SolicitadoReal', 'SolicitadoCusteioUfir', 'SolicitadoCusteioReal',
                'SolicitadoCapitalUfir', 'SolicitadoCapitalReal', 'Logon', 'idProjeto'
            ), $this->_schema)
            ->where('p.idPreProjeto = idProjeto')
            ->limit(1);

        $subSql2 = $this->select()
            ->from(array('vwConformidadeDocumentalTecnico'), array('idProjeto'), $this->_schema);

        if ($idUsuario !== null) {
            $subSql2->where(new Zend_Db_Expr("($orgaoSub) = ?"), $idUsuario);
        }

        $sql = $this->select()
            ->from(array('p' => 'PreProjeto'), $p, $this->_schema)
            ->join(array('m' => 'tbMovimentacao'), 'p.idPreProjeto = m.idProjeto AND m.stEstado = 0', $m, $this->_schema)
            ->join(array('x' => 'tbAvaliacaoProposta'), 'p.idPreProjeto = x.idProjeto AND x.stEstado = 0', $x, $this->_schema)
            ->join(array('a' => 'Agentes'), 'p.idPreProjeto = x.idProjeto AND x.stEstado = 0', 'a.CNPJCPF', $this->getSchema('agentes'))
            ->join(array('y' => 'Verificacao'), 'm.Movimentacao = y.idVerificacao', array('y.Descricao AS Situacao'), $this->_schema)
            ->where('p.stEstado = 1')
            ->where('m.Movimentacao NOT IN(96,128)')
            ->where(new Zend_Db_Expr("NOT EXISTS ($subSql)"))
            ->where(new Zend_Db_Expr("p.idPreProjeto IN($subSql2)"))
            ->order($order);

        return $db->fetchAll($sql);
    }

    public function transformarPropostaEmProjeto($idPreProjeto, $cnpjcpf, $idOrgao, $idUsuario, $nrProcesso)
    {
        $sql = "EXEC sac.paPropostaParaProjeto {$idPreProjeto}, '{$cnpjcpf}', {$idOrgao}, {$idUsuario}, {$nrProcesso}";
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        return $db->fetchAll($sql);
    }

    public function buscaragencia($codigo)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $sql = $this->select()
            ->from(array('b' => 'BancoAgencia'), 'Agencia', $this->_schema)
            ->where('b.Agencia = ?', $codigo)
            ->query();

        return $sql->fetchAll();
    }

    public function unidadeAnaliseProposta($idPreProjeto)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array("p" => $this->_name), array("*"));
        $slct->joinInner(array("ap" => "tbAvaliacaoProposta"), "p.idPreProjeto = ap.idProjeto", array("*"), "SAC");

        $slct->where("ap.stEstado = ?", 0);
        $slct->where("p.idPreProjeto = ?", $idPreProjeto);

        return $this->fetchAll($slct);
    }

    public function orgaoSecretaria($idTecnico)
    {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('vwUsuariosOrgaosGrupos'), array('org_superior AS idOrgao'), 'tabelas')
            ->where('usu_codigo = ?', 4676)
            ->where('sis_codigo=21')
            ->where('gru_codigo = 92')
            ->order('org_superior');

        $orgao = $db->fetchAll($sql);

        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('Orgaos'), null, 'tabelas')
            ->join(array('Pessoa_Identificacoes'), 'pid_pessoa = org_pessoa', array('pid_identificacao'), 'tabelas')
            ->where('pid_meta_dado = 1')
            ->where('pid_sequencia = 1')
            ->where('org_codigo = ?', 160);

        $identificacao = $db->fetchAll($sql);

        $orgao[0]->secretaria = $identificacao[0]->pid_identificacao;

        return $orgao;
    }

    public function propostastransformadas($idAgente)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array("pj" => $this->_name), array("*"));
        $slct->joinInner(array("p" => "Projetos"), "pj.idPreProjeto = p.idProjeto", array("*"), "SAC");

        $slct->where("pj.idAgente = ?", $idAgente);
        return $this->fetchAll($slct);
    }

    public function propostasPorEdital($where = array(), $order = array(), $tamanho = -1, $inicio = -1, $count = false)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array("p" => $this->_name),
            array("idProjeto" => "idPreProjeto", "NomeProposta" => "NomeProjeto", "idAgente", "DtCadastro" => "CONVERT(CHAR(20),p.dtAceite, 120)"),
            "SAC"
        );
        $slct->joinLeft(
            array("m" => "tbMovimentacao"),
            "p.idPreProjeto = m.idProjeto AND m.stEstado = 0",
            array("idMovimentacao", "CodSituacao" => "m.Movimentacao", "DtMovimentacao" => "CONVERT(CHAR(20),m.DtMovimentacao, 120)"),
            "SAC"
        );
        $slct->joinLeft(
            array("x" => "tbAvaliacaoProposta"),
            "p.idPreProjeto = x.idProjeto AND x.stEstado = 0",
            array("ConformidadeOK"),
            "SAC"
        );
        $slct->joinLeft(
            array("x1" => "tbAvaliacaoProposta"),
            "p.idPreProjeto = x1.idProjeto",
            array("DtEnvioMinC" => "CONVERT(CHAR(20),x1.DtEnvio , 120)"),
            "SAC"
        );
        $slct->joinLeft(
            array("mv" => "tbMovimentacao"),
            "p.idPreProjeto = mv.idProjeto and mv.stEstado = 0",
            array("stMovimentacao" => "movimentacao"),
            "SAC"
        );
        $slct->joinLeft(
            array("vr" => "Verificacao"),
            "mv.movimentacao = vr.idVerificacao and vr.idTipo = 4",
            array("Movimentacao" => "Descricao"),
            "SAC"
        );
        $slct->joinInner(
            array("a" => "Agentes"),
            "p.idAgente = a.idAgente",
            array("CNPJCPF"),
            "agentes"
        );
        $slct->joinInner(
            array("n" => "Nomes"),
            "p.idAgente = n.idAgente",
            array("NomeAgente" => "Descricao"),
            "agentes"
        );
        $slct->joinInner(
            array("e" => "Edital"),
            "e.idEdital = p.idEdital",
            array("idOrgao"),
            "SAC"
        );
        $slct->joinInner(
            array("fd" => "tbFormDocumento"),
            "fd.idEdital = p.idEdital AND idClassificaDocumento NOT in (23,24,25)",
            array("Edital" => "nmFormDocumento", "idEdital"),
            "bdcorporativo.scQuiz"
        );
        $slct->joinInner(
            array("cl" => "tbClassificaDocumento"),
            "cl.idClassificaDocumento = fd.idClassificaDocumento",
            array("idClassificaDocumento", "dsClassificaDocumento"),
            "bdcorporativo.scSAC"
        );
        $slct->joinInner(
            array("o" => "Orgaos"),
            "o.Codigo = e.idEdital",
            array("SiglaOrgao" => "Sigla"),
            "SAC"
        );
        $slct->joinInner(
            array("ab" => "Abrangencia"),
            "p.idPreProjeto = ab.idProjeto AND ab.stAbrangencia = 1",
            array(),
            "SAC"
        );
        $slct->joinInner(
            array("uf" => "UF"),
            "uf.idUF = ab.idUF",
            array("idUF", "SiglaUF" => "Sigla", "NomeUF" => "Descricao", "Regiao"),
            "agentes"
        );
        $slct->joinInner(
            array("mu" => "Municipios"),
            "mu.idMunicipioIBGE = ab.idMunicipioIBGE",
            array("NomeMunicipio" => "Descricao"),
            "agentes"
        );
        $slct->joinInner(
            array("vr2" => "Verificacao"),
            "e.cdTipoFundo = vr2.idVerificacao and vr2.idTipo = 15",
            array("FundoNome" => "Descricao", "idFundo" => "idVerificacao"),
            "SAC"
        );

        if ($count) {
            $slct2 = $this->select();
            $slct2->setIntegrityCheck(false);
            $slct2->from(
                array('p' => $this->_name),
                array("total" => "count(*)")
            );
            $slct2->joinLeft(
                array("m" => "tbMovimentacao"),
                "p.idPreProjeto = m.idProjeto AND m.stEstado = 0",
                array(),
                "SAC"
            );
            $slct2->joinLeft(
                array("x" => "tbAvaliacaoProposta"),
                "p.idPreProjeto = x.idProjeto AND x.stEstado = 0",
                array(),
                "SAC"
            );
            $slct2->joinLeft(
                array("x1" => "tbAvaliacaoProposta"),
                "p.idPreProjeto = x1.idProjeto",
                array(),
                "SAC"
            );
            $slct2->joinLeft(
                array("mv" => "tbMovimentacao"),
                "p.idPreProjeto = mv.idProjeto and mv.stEstado = 0",
                array(),
                "SAC"
            );
            $slct2->joinLeft(
                array("vr" => "Verificacao"),
                "mv.movimentacao = vr.idVerificacao and vr.idTipo = 4",
                array(),
                "SAC"
            );
            $slct2->joinInner(
                array("a" => "Agentes"),
                "p.idAgente = a.idAgente",
                array(),
                "agentes"
            );
            $slct2->joinInner(
                array("n" => "Nomes"),
                "p.idAgente = n.idAgente",
                array(),
                "agentes"
            );
            $slct2->joinInner(
                array("e" => "Edital"),
                "e.idEdital = p.idEdital",
                array(),
                "SAC"
            );
            $slct2->joinInner(
                array("fd" => "tbFormDocumento"),
                "fd.idEdital = p.idEdital AND idClassificaDocumento NOT in (23,24,25)",
                array(),
                "bdcorporativo.scQuiz"
            );
            $slct2->joinInner(
                array("cl" => "tbClassificaDocumento"),
                "cl.idClassificaDocumento = fd.idClassificaDocumento",
                array(),
                "bdcorporativo.scSAC"
            );
            $slct2->joinInner(
                array("o" => "Orgaos"),
                "o.Codigo = e.idEdital",
                array(),
                "SAC"
            );
            $slct2->joinInner(
                array("ab" => "Abrangencia"),
                "p.idPreProjeto = ab.idProjeto AND ab.stAbrangencia = 1",
                array(),
                "SAC"
            );
            $slct2->joinInner(
                array("uf" => "UF"),
                "uf.idUF = ab.idUF",
                array(),
                "agentes"
            );
            $slct2->joinInner(
                array("mu" => "Municipios"),
                "mu.idMunicipioIBGE = ab.idMunicipioIBGE",
                array(),
                "agentes"
            );
            $slct2->joinInner(
                array("vr2" => "Verificacao"),
                "e.cdTipoFundo = vr2.idVerificacao and vr2.idTipo = 15",
                array(),
                "SAC"
            );

            //adiciona quantos filtros foram enviados
            foreach ($where as $coluna => $valor) {
                $slct2->where($coluna, $valor);
            }
            $rs = $this->fetchAll($slct2)->current();
            if ($rs) {
                return $rs->total;
            } else {
                return 0;
            }
        }

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        //adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        return $this->fetchAll($slct);
    }

    public function checklistEnvioPropostaSemSp($idPreProjeto, $podeAlterarProjeto = false)
    {
        $validacao = new stdClass();
        $listaValidacao = array();

        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        try {

            $proposta = $this->fetchRow(['idPreProjeto = ?' => $idPreProjeto]);

            if(empty($proposta)) {
                throw new Exception("A proposta cultural n&atilde;o encontrada!");
            }

            $wherePropostaEnviadaParaAdmissibilidade = array(
                'idProjeto = ?' => $idPreProjeto,
                'Movimentacao <> ?' => 95,
                'stEstado = ?' => 't'
            );

            $tbMovimentacao = new Proposta_Model_DbTable_TbMovimentacao();
            $propostaEnviadaParaAdmissibilidade = $tbMovimentacao->buscar(
                $wherePropostaEnviadaParaAdmissibilidade,
                array(),
                1
            )->current();

            if (!empty($propostaEnviadaParaAdmissibilidade) && !$podeAlterarProjeto) {
                throw new Exception("A proposta cultural encontra-se na fase de admissibilidade");
            }

            $tbAgentes = new Agente_Model_DbTable_Agentes();
            $proponente = $tbAgentes->fetchRow(['idAgente = ?' => $proposta->idAgente]);

            if (!empty($propostaEnviadaParaAdmissibilidade) && !$podeAlterarProjeto) {
                throw new Exception("Proponente n&atilde;o existe!");
            }

            $tbAvaliacaoProposta = new Admissibilidade_Model_DbTable_TbAvaliacaoProposta();
            $propostaJaAvaliada = $tbAvaliacaoProposta->buscar(['idProjeto = ?' => $idPreProjeto])->current();

            //if(( date('m') == 13 || date('m') == 1 ) && empty($propostaJaAvaliada)) { @todo voltar esta linha, apenas para teste
            if (getenv('APPLICATION_ENV') == 'production' && empty($propostaJaAvaliada)) {
                throw new Exception("Conforme Art 9&#176; da Instru&ccedil;&atilde;o Normativa n&#176; 1, de 24 de 
                junho de 2013, nenhuma proposta poder&aacute; ser enviada nos meses de DEZEMBRO e JANEIRO!");
            }

            $proponenteComEnderecoParaCorrespondencia = $tbAgentes->obterNomeEnderecoCorrespondenciaAgente(
                ['a.idAgente = ?' => $proponente->idAgente]
            );

            if (empty($proponenteComEnderecoParaCorrespondencia)) {
                $validacao->dsInconsistencia = 'Dados cadastrais do proponente inexistente ou n&atilde;o h&aacute; 
                endere&ccedil;o para correspondência selecionado.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array(
                    'module' => 'agente',
                    'controller' => 'agentes',
                    'action' => 'agentes',
                    'id' => $proponente->idAgente
                );
                $listaValidacao[] = clone($validacao);
            }

            $tbInabilitado = new Inabilitado();
            $regularidadeProponente = $tbInabilitado->fetchRow(
                [
                    'CgcCpf = ?' => $proponente->CNPJCPF,
                    'Habilitado = ?' => 'N'
                ]
            );

            if (!empty($regularidadeProponente)) {
                $validacao->dsInconsistencia = 'Proponente em situa&ccedil;&atilde;o IRREGULAR!';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = '';
                $listaValidacao[] = clone($validacao);
            }


            $tbInternet = new Agente_Model_DbTable_Internet();
            $emails = $tbInternet->fetchRow(['idAgente = ?' => $proponente->idAgente, 'Status = ?' => 't']);

            if (empty($emails)) {
                $validacao->dsInconsistencia = 'E-mail do proponente inexistente';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'agente', 'controller' => 'agentes', 'action' => 'agentes', 'id' => $proponente->idAgente);
                $listaValidacao[] = clone($validacao);
            }



            //-- NO CASO DE PESSOA FISICA, VERIFICAR O LANCAMENTO DA DATA DE NASCIMENTO

            if ($proponente->TipoPessoa == 0) {

                $sql = $this->select()
                    ->from(array('tbAgenteFisico'), 'dtNascimento', $this->getSchema('agentes'))
                    ->where('idAgente = ?', $proponente->idAgente);

                $dataNasc = $db->fetchOne($sql);

                if (empty($dataNasc)) {
                    $validacao->dsInconsistencia = 'Data de nascimento inexistente.';
                    $validacao->Observacao = 'PENDENTE';
                    $validacao->Url = array('module' => 'agente', 'controller' => 'agentes', 'action' => 'info-adicionais', 'id' => $proponente->idAgente);
                    $listaValidacao[] = clone($validacao);
                }
            }

            //-- NO CASO DE PESSOA JURIDICA, VERIFICAR O LANCAMENTO DA NATUREZA DO PROPONENTE
            if ($proponente->TipoPessoa  == 1) {

                $sqlNatureza = $this->select()
                    ->from(array('n' => 'Natureza'), '*', $this->getSchema('agentes'))
                    ->where('idAgente = ?', $proponente->idAgente)
                    ->limit(1);

                $natureza = $db->fetchAll($sqlNatureza);

                if (empty($natureza)) {
                    $validacao->dsInconsistencia = 'Natureza do proponente.';
                    $validacao->Observacao = 'PENDENTE';
                    $validacao->Url = '';
                    $listaValidacao[] = clone($validacao);
                }

                //-- VERIFICAR SE HA DIRIGENTE CADASTRADO
                $sql = $this->select()
                    ->from(array('v' => 'vCadastrardirigente'), '*', $this->_schema)
                    ->join(array('p' => 'PreProjeto'), 'v.idVinculoPrincipal=p.idAgente', '*', $this->_schema)
                    ->where('idPreProjeto= ?', $idPreProjeto);

                $dirigenteCadastrado = $db->fetchAll($sql);
                if (empty($dirigenteCadastrado)) {
                    $validacao->dsInconsistencia = 'Cadastro de Dirigente.';
                    $validacao->Observacao = 'PENDENTE';
                    $validacao->Url = '';
                    $listaValidacao[] = clone($validacao);
                }
            }

            // Verifica se o proponente Proposta aprovado em editais (618) ou Proposta  com contratos de patroc&iacute;nios (619)
            $sql = $this->select()
                ->from($this->_name, $this->_getCols(), $this->_schema)
                ->where('idPreProjeto = ?', $idPreProjeto);
            $stProposta = $db->fetchRow($sql)->stProposta;

            $idDocumento = '';
            if ($stProposta == '618') {
                $msg = 'No caso de proposta aprovada em editais &eacute; obrigat&oacute;rio anexar o documento Resultado da Sele&ccedil;&atilde;o p&uacute;blica';
                $idDocumento = 248;
            } elseif ($stProposta == '619') {
                $msg = 'No caso de proposta com contratos de patroc&iacute;nios &eacute; obrigat&oacute;rio anexar o Contrato firmado com o Incentivador';
                $idDocumento = 162;
            }

            if (!empty($idDocumento)) {

                $sql = $this->select()
                    ->from(array('tbDocumentosPreProjeto'), '*', $this->_schema)
                    ->where('idProjeto = ?', $idPreProjeto)
                    ->where('CodigoDocumento = ?', $idDocumento)
                    ->limit(1);
                $documento = $db->fetchRow($sql);

                if (empty($documento)) {
                    $validacao->dsInconsistencia = $msg;
                    $validacao->Observacao = 'PENDENTE';
                    $validacao->Url = array('module' => 'proposta', 'controller' => 'manterpropostaincentivofiscal', 'action' => 'identificacaodaproposta', 'idPreProjeto' => $idPreProjeto);
                    $listaValidacao[] = clone($validacao);
                }
            }

            //-- VERIFICAR SE O LOCAL DE REALIZACAO ESTA CADASTRADO
            //IF NOT EXISTS(SELECT TOP 1 * FROM Abrangencia WHERE idProjeto = @idProjeto)
            $sql = $this->select()
                ->from(array('Abrangencia'), '*', $this->_schema)
                ->where('idProjeto = ?', $idPreProjeto)
                ->limit(1);

            $local = $db->fetchAll($sql);

            if (empty($local)) {
                $validacao->dsInconsistencia = 'O Local de realiza&ccedil;&atilde;o da proposta n&atilde;o foi preenchido.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'localderealizacao', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            //-- VERIFICAR SE EXISTE NO MINIMO 90 DIAS ENTRE A DATA DE ENVIO E O INICIO DO PERIODO DE EXECUCAO DO PROJETO
            $sql = $this->select()
                ->from($this->_name, array('*'), $this->_schema)
                ->where('idPreProjeto = ?', $idPreProjeto)
                ->limit(1);

            if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
                $sql->where('DATEDIFF(DAY,' . $this->getDate() . ',DtInicioDeExecucao) < 90');
            } else {
                $sql->where('DATE_PART(\'day\', "DtInicioDeExecucao" - now()) < 90');
            }
            $minimo90 = $db->fetchAll($sql);


            if (!empty($minimo90)) {
                $validacao->dsInconsistencia = 'A diferen&ccedil;a em dias entre a data de envio do projeto ao MinC e a data de in&iacute;cio de execu&ccedil;&atilde;o do projeto est&aacute; menor do que 90 dias.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'manterpropostaincentivofiscal', 'action' => 'identificacaodaproposta', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            //-- VERIFICAR SE O PLANO DE DISTRIBUICAO DO PRODUTO ESTA PREENCHIDO
            $sql = $this->select()
                ->from(array('PlanoDistribuicaoProduto'), '*', $this->_schema)
                ->where('idProjeto =  ?', $idPreProjeto)
                ->limit(1);

            $planoDistribuicao = $db->fetchAll($sql);
            if (empty($planoDistribuicao)) {
                $validacao->dsInconsistencia = 'O Plano Distribui&ccedil;&atilde;o de Produto n&atilde;o foi preenchido.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'plano-distribuicao', 'action' => 'index', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            //--Verificar a existencia do produto principal
            //SELECT @QtdeOutros=stPrincipal FROM PlanoDistribuicaoProduto  WHERE idProjeto = @idProjeto and stPrincipal = 1
            $sql = $this->select()
                ->from(array('PlanoDistribuicaoProduto'), 'stPrincipal', $this->_schema)
                ->where('idProjeto =  ?', $idPreProjeto)
                ->where('stPrincipal = ?', 't');

            $quantidade = count($db->fetchAll($sql));

            if ($quantidade = 0) {
                $validacao->dsInconsistencia = 'N&atilde;o h&aacute; produto principal selecionado na proposta.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'plano-distribuicao', 'action' => 'index', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            } else if ($quantidade > 1) {
                $validacao->dsInconsistencia = 'Só poder&aacute; haver um produto principal em cada proposta, a sua est&aacute; com mais de um produto.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'plano-distribuicao', 'action' => 'index', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            //-- VERIFICAR SE EXISTE NA PLANILHA ORCAMENTARIA ITENS DA FONTE INCENTIVO FISCAL FEDERAL.
            $sql = $this->select()
                ->from(array('tbPlanilhaProposta'), '*', $this->_schema)
                ->where('idProjeto =  ?', $idPreProjeto)
                ->where('FonteRecurso = ?', 109)
                ->limit(1);

            $planilhaOrcamentaria = $db->fetchAll($sql);

            if (empty($planilhaOrcamentaria)) {
                $validacao->dsInconsistencia = 'N&atilde;o existe item or&ccedil;ament&aacute;rio referente a fonte de recurso - Incentivo Fiscal Federal.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'manterorcamento', 'action' => 'produtoscadastrados', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            //-- VERIFICAR SE EXISTE NA PLANILHA ORCAMENTARIA PARA CADA PRODUTO DESCRITO NO PLANO DE DISTRIBUICAO DO PRODUTO
            //IF EXISTS(SELECT * FROM PlanoDistribuicaoProduto pp WHERE idProjeto = @idProjeto and
            //NOT EXISTS(SELECT * FROM tbPlanilhaProposta pl WHERE idProjeto = @idProjeto and pp.idProduto=pl.idProduto and idProduto <> 0))
            $subSql = $this->select()
                ->from(array('pl' => 'tbPlanilhaProposta'), 'pl.idProduto', $this->_schema)
                ->where('pl.idProjeto = ?', $idPreProjeto);

            $sql = $this->select()
                ->from(array('pp' => 'PlanoDistribuicaoProduto'), '*', $this->_schema)
                ->where('pp.idProjeto = ?', $idPreProjeto)
                ->where(new Zend_Db_Expr('"pp"."idProduto" NOT IN(' . $subSql .')'));

            $planilhaProduto = $db->fetchAll($sql);

            if (!empty($planilhaProduto)) {
                $validacao->dsInconsistencia = 'Existe produto cadastrado sem a respectiva planilha orcament&aacute;ria cadastrada.';
                $validacao->Observacao = 'PENDENTE';
                $validacao->Url = array('module' => 'proposta', 'controller' => 'manterorcamento', 'action' => 'produtoscadastrados', 'idPreProjeto' => $idPreProjeto);
                $listaValidacao[] = clone($validacao);
            }

            //-- VERIFICAR O PERCENTUAL DA REMUNERACAO PARA CAPTACAO DE RECURSOS
//            $sql = $this->select()
//                ->from(
//                    array('tbPlanilhaProposta'),
//                    'SUM("Quantidade" * "Ocorrencia" * "ValorUnitario") as total',
//                    $this->_schema
//                )
//                ->where('idProjeto =  ?', $idPreProjeto)
//                ->where('FonteRecurso = ?', 109)
//                ->where('idPlanilhaItem <> ? ', 5249);
//
//            $total = $db->fetchAll($sql);
//            $total = empty($total[0]->total) ? 0 : $total[0]->total;

            //--pega o valor de remuneracao para captacao
//            $sql = $this->select()
//                ->from(array('tbPlanilhaProposta'), 'SUM("Quantidade" * "Ocorrencia" * "ValorUnitario") as total', $this->_schema)
//                ->where('idProjeto =  ?', $idPreProjeto)
//                ->where('FonteRecurso = ?', 109)
//                ->where('idPlanilhaItem <> ? ', 5249);
//
//            $custoAdm = $db->fetchAll($sql);
//            $custoAdm = empty($custoAdm[0]->total) ? 0 : $custoAdm[0]->total;

            $validado = true;
            foreach ($listaValidacao as $valido) {
                if ($valido->Observacao == 'PENDENTE') {
                    $validado = false;
                    break;
                }
            }

            if ($validado) {
                $validacao->dsInconsistencia = 'A proposta cultural n&atilde;o possui pend&ecirc;ncias';
                $validacao->Observacao = true;
                $validacao->Url = '';
                return $validacao;

            } else {
                $validacao->dsInconsistencia = '<font color=red><b> A PROPOSTA CULTURAL N&Atilde;O FOI ENVIADA AO MINIST&Eacute;RIO DA CULTURA DEVIDO &Agrave;S PEND&Ecirc;NCIAS ASSINALADAS ACIMA.</b></font>';
                $validacao->Observacao = '';
                $validacao->Url = '';
                $listaValidacao[] = clone($validacao);
            }


            return $listaValidacao;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function listarProdutos($idPreProjeto)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()
            ->from(
                array('pre' => $this->_name),
                array(
                    'pre.idPreProjeto as PreProjeto',
                    'pre.idPreProjeto as idProposta'
                ),
                $this->_schema
            )
            ->join(
                array('pd' => 'PlanoDistribuicaoProduto'),
                'pre.idPreProjeto = pd.idProjeto',
                array('pd.stPrincipal as ProdutoPrincipal'),
                $this->_schema
            )
            ->join(
                array('p' => 'Produto'),
                'pd.idProduto = p.Codigo',
                array(
                    'p.Codigo as CodigoProduto',
                    'p.Descricao as DescricaoProduto'
                ),
                $this->_schema
            )
            ->where('pd.stPlanoDistribuicaoProduto = ?', 't')
            ->where('idPreProjeto = ?', $idPreProjeto)
            ->order('pd.stPrincipal DESC')
            ->group(array('p.Codigo', 'p.Descricao', 'idPreProjeto', 'pd.stPrincipal'));

        return $db->fetchAll($sql);
    }

//    public function listarEtapasProdutos($idPreProjeto)
//    {
//        $db = Zend_Db_Table::getDefaultAdapter();
//        $db->setFetchMode(Zend_Db::FETCH_OBJ);
//
//        $sql = $this->select()
//            ->from(array('tbplanilhaetapa'), array('idplanilhaetapa as idEtapa', 'descricao as DescricaoEtapa'), $this->getSchema('sac'))
//            ->where("tpCusto = 'P'");
//
//        throw new Exception('M&eacute;todo transferido para Proposta_Model_DbTable_TbPlanilhaEtapa');
//
//        return $db->fetchAll($sql);
//    }

    //@todo lugar certo &eacute; tbPlanilhaProposta, remover do ManterOrcamentoDAO tbm
    public function listarItensProdutos($idPreProjeto, $idItem = null, $fetchMode = Zend_Db::FETCH_OBJ)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode($fetchMode);

        $pp = array(
            'pp.idEtapa as idEtapa',
            'pp.idPlanilhaItem as idItem',
            'pp.UfDespesa as IdUf',
            'pp.Quantidade',
            'pp.Ocorrencia',
            'pp.ValorUnitario',
            'pp.QtdeDias',
            'pp.idPlanilhaProposta',
            'Verificador' => new Zend_Db_Expr('CONCAT("pp"."idProduto", "pp"."idEtapa", "pp"."MunicipioDespesa")'),
        );

        $sql = $this->select()->distinct()
            ->from(array('pre' => 'PreProjeto'), null, $this->getSchema('sac'))
            ->join(array('pp' => 'tbPlanilhaProposta'), 'pre.idPreProjeto = pp.idProjeto', $pp, $this->_schema)
            ->join(array('p' => 'Produto'), 'pp.idProduto = p.Codigo', array('p.Codigo as CodigoProduto'), $this->_schema)
            ->join(array('ti' => 'tbPlanilhaItens'), 'ti.idPlanilhaItens = pp.idPlanilhaItem', array('ti.Descricao as DescricaoItem'), $this->_schema)
            ->join(array('uf' => 'Uf'), 'uf.CodUfIbge = pp.UfDespesa', array('uf.Descricao as DescricaoUf', 'uf.Uf as SiglaUF'), $this->_schema)
            ->join(
                array('municipio' => 'Municipios'),
                'municipio.idMunicipioIBGE::varchar(6) = pp.MunicipioDespesa::varchar(6)',
                array('municipio.Descricao as Municipio'),
                $this->getSchema('agentes')
            )
            ->join(array('mec' => 'Mecanismo'), 'mec.Codigo::integer = pre.Mecanismo::integer', array('mec.Descricao as DescricaoMecanismo'), $this->_schema)
            ->join(array('un' => 'tbPlanilhaUnidade'), 'un.idUnidade = pp.Unidade', 'un.Descricao as Unidade', $this->_schema)
            ->join(
                array('veri' => 'Verificacao'),
                'veri.idVerificacao = pp.FonteRecurso',
                array('veri.idVerificacao as idFonteRecurso', 'veri.Descricao as DescricaoFonteRecurso'),
                $this->getSchema('sac')
            )
            ->where('idPreProjeto = ?', $idPreProjeto)
            ->order('ti.Descricao');

        if ($idItem) {
            $sql->where("pp.idPlanilhaItem = ?", $idItem);
        }

        return $db->fetchAll($sql);
    }

    public function buscarProdutos($idPreProjeto)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('pre' => $this->_name),
            array(
                'p.Codigo as codigoproduto',
                'p.Descricao as descricaoproduto',
                'pre.idPreProjeto as preprojeto',
                'pre.idPreProjeto as idproposta'
            ),
            $this->_schema
        );

        $select->joinInner(
            array('pd' => 'PlanoDistribuicaoProduto'),
            'pre.idPreProjeto = pd.idProjeto',
            null,
            $this->_schema
        );

        $select->joinInner(
            array('p' => 'Produto'),
            'pd.idProduto = p.Codigo',
            null,
            $this->_schema
        );

        $select->where('pd.stPlanoDistribuicaoProduto = ?', 't');
        $select->where('pre.idPreProjeto = ?', $idPreProjeto);

        $select->group(array('p.Codigo', 'p.Descricao', 'pre.idPreProjeto'));

        return $db->fetchAll($select);
    }

    public function buscarEtapasProdutos($idPreProjeto)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = "SELECT
                    distinct
                    p.Codigo as CodigoProduto,
                    pp.idProduto as idProduto,
                    pp.idEtapa as idEtapa,
                    te.Descricao as DescricaoEtapa,
                    pre.idPreProjeto as idPreProjeto
                    FROM sac.PreProjeto pre
                    INNER JOIN sac.tbPlanilhaProposta pp ON (pre.idPreProjeto = pp.idProjeto)
                    INNER JOIN sac.Produto p ON (pp.idProduto = p.Codigo)
                    INNER JOIN sac..tbPlanilhaEtapa te on te.idPlanilhaEtapa = pp.idEtapa
                    WHERE idPreProjeto = {$idPreProjeto}";

        $sql .= " ORDER BY te.DescricaoEtapa ";

        $sql = " SELECT idPlanilhaEtapa as idEtapa, Descricao as DescricaoEtapa FROM sac.tbPlanilhaEtapa
            WHERE tpCusto = 'P' ";

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->distinct();
        $select->from(
            array('pre' => 'PreProjeto'),
            array(
                'p.Codigo as codigoproduto',
                'pp.idProduto as idproduto',
                'pp.idEtapa as idetapa',
                'te.Descricao as descricaoetapa',
                'pre.idPreProjeto as idpreprojeto'
            ),
            $this->_schema
        );

        $select->joinInner(
            array('pp' => 'tbPlanilhaProposta'),
            'pre.idPreProjeto = pp.idprojeto',
            null,
            $this->_schema
        );

        $select->joinInner(
            array('p' => 'Produto'),
            'pp.idProduto = p.Codigo',
            null,
            $this->_schema
        );

        $select->joinInner(
            array('p' => 'Produto'),
            'pd.idproduto = p.codigo',
            null,
            $this->_schema
        );

        try {
        } catch (Zend_Exception_Db $e) {
            $this->view->message = "Erro ao buscar Etapas: " . $e->getMessage();
        }
        return $db->fetchAll($sql);
    }

    public function propostas(
        $idAgente,
        $idResponsavel,
        $idAgenteCombo,
        $where = array(),
        $order = array(),
        $start = 0,
        $limit = 20,
        $search = null
    )
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $subSql = $this->select()
            ->from(array('pr' => 'Projetos'), array('idProjeto'), $this->_schema)
            ->where('a.idPreProjeto = pr.idProjeto');

        $sql = $this->select();
        $sql->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.NomeProjeto as nomeprojeto'), $this->_schema);
        $sql->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'));
        $sql->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'));
        $sql->where('a.idAgente = ? ', $idAgente);
        $sql->where('a.stEstado = true');
        $sql->where("NOT EXISTS($subSql)");
        $sql->where("a.Mecanismo = '1'");

        $sql2 = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.NomeProjeto as nomeprojeto'), $this->_schema)
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->join(array('c' => 'Vinculacao'), 'b.idAgente = c.idVinculoPrincipal', array(), $this->getSchema('agentes'))
            ->join(array('d' => 'Agentes'), 'c.idAgente = d.idAgente', array(), $this->getSchema('agentes'))
            ->join(array('e' => 'SGCacesso'), 'd.CNPJCPF = e.Cpf', array(), $this->getSchema('controledeacesso'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->where('e.IdUsuario = ?', $idResponsavel)
            ->where('a.stEstado = true')
            ->where(new Zend_Db_Expr("NOT EXISTS($subSql)"))
            ->where("a.Mecanismo = '1'");

        $sql3 = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.NomeProjeto'), $this->getSchema('sac'))
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->join(array('c' => 'Nomes'), 'b.idAgente = c.idAgente', array('c.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('d' => 'SGCacesso'), 'a.idUsuario = d.IdUsuario', array(), $this->getSchema('controledeacesso'))
            ->join(array('e' => 'tbVinculoProposta'), 'a.idPreProjeto = e.idPreProjeto', array(), $this->getSchema('agentes'))
            ->join(array('f' => 'tbVinculo'), 'e.idVinculo = f.idVinculo', array(), $this->getSchema('agentes'))
            ->where('a.stEstado = true')
            ->where(new Zend_Db_Expr("NOT EXISTS($subSql)"))
            ->where("a.Mecanismo = '1'")
            ->where('e.siVinculoProposta = ?', '2')
            ->where('f.idUsuarioResponsavel = ?', $idResponsavel);

        if (!empty($idAgenteCombo)) {
            $sql->where('b.idAgente = ?', $idAgenteCombo);
            $sql2->where('b.idAgente = ?', $idAgenteCombo);
            $sql3->where('b.idAgente = ?', $idAgenteCombo);
        }

        $sql = $this->select()->union(array($sql, $sql2, $sql3), Zend_Db_Select::SQL_UNION);

        $sqlFinal = $this->select();
        $sqlFinal->isUseSchema(false);
        $sqlFinal->from(array("p" => $sql), '*', null);
//        ->joinInner( array('mov' => 'tbmovimentacao'), 'p.idPreProjeto = mov.idprojeto', array(), $this->_schema)
//        ->joinInner( array('ver' => 'Verificacao'), 'mov.Movimentacao = ver.idVerificacao', array('Descricao as situacao'), $this->_schema)
//        ->where('mov.stestado = ? ', 0);

        foreach ($where as $coluna => $valor) {
            $sqlFinal->where($coluna, $valor);
        }

        if (!empty($search['value'])) {
            $sqlFinal->where('p.idPreProjeto like ? OR p.nomeprojeto like ? OR  p.nomeproponente like ?', '%' . $search['value'] . '%');
        }

        if (is_array($order) && count(array_filter($order)) > 0) {
            $sqlFinal->order($order);
        }

        if (!is_null($start) && $limit) {
            $start = (int)$start;
            $limit = (int)$limit;
            $sqlFinal->limitPage($start, $limit);
        }

        return $db->fetchAll($sqlFinal);
    }

    public function propostasTotal($idAgente, $idResponsavel, $idAgenteCombo, $where = array(), $order = array(), $start = 0, $limit = 20, $search = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $subSql = $this->select()
            ->from(array('pr' => 'Projetos'), array('idProjeto'), $this->_schema)
            ->where('a.idPreProjeto = pr.idProjeto');

        $sql = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.NomeProjeto'), $this->_schema)
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->where('a.idAgente = ? ', $idAgente)
            ->where('a.stEstado = true')
            ->where("NOT EXISTS($subSql)")
            ->where("a.Mecanismo = '1'");

        $sql2 = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.NomeProjeto'), $this->_schema)
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->join(array('c' => 'Vinculacao'), 'b.idAgente = c.idVinculoPrincipal', array(), $this->getSchema('agentes'))
            ->join(array('d' => 'Agentes'), 'c.idAgente = d.idAgente', array(), $this->getSchema('agentes'))
            ->join(array('e' => 'SGCacesso'), 'd.CNPJCPF = e.Cpf', array(), $this->getSchema('controledeacesso'))
            ->joinleft(array('n' => 'Nomes'), 'n.idAgente = b.idAgente', array('n.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->where('e.IdUsuario = ?', $idResponsavel)
            ->where('a.stEstado = true')
            ->where("NOT EXISTS($subSql)")
            ->where("a.Mecanismo = '1'");

        $sql3 = $this->select()
            ->from(array('a' => 'PreProjeto'), array('a.idPreProjeto', 'a.NomeProjeto'), $this->getSchema('sac'))
            ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.idAgente'), $this->getSchema('agentes'))
            ->join(array('c' => 'Nomes'), 'b.idAgente = c.idAgente', array('c.Descricao as nomeproponente'), $this->getSchema('agentes'))
            ->join(array('d' => 'SGCacesso'), 'a.idUsuario = d.IdUsuario', array(), $this->getSchema('controledeacesso'))
            ->join(array('e' => 'tbVinculoProposta'), 'a.idPreProjeto = e.idPreProjeto', array(), $this->getSchema('agentes'))
            ->join(array('f' => 'tbVinculo'), 'e.idVinculo = f.idVinculo', array(), $this->getSchema('agentes'))
            ->where('a.stEstado = true')
            ->where("NOT EXISTS($subSql)")
            ->where("a.Mecanismo = '1'")
            ->where("e.siVinculoProposta = '2'")
            ->where('f.idUsuarioResponsavel = ?', $idResponsavel);

        if (!empty($idAgenteCombo)) {
            $sql->where('b.idAgente = ?', $idAgenteCombo);
            $sql2->where('b.idAgente = ?', $idAgenteCombo);
            $sql3->where('b.idAgente = ?', $idAgenteCombo);
        }

        $sql = $this->select()->union(array($sql, $sql2, $sql3), Zend_Db_Select::SQL_UNION);

        $sqlFinal = $this->select();
        $sqlFinal->isUseSchema(false);
        $sqlFinal->from(
            array("p" => new Zend_Db_Expr("({$sql})")),
            array('count(distinct "p"."idPreProjeto")')
        );

        foreach ($where as $coluna => $valor) {
            $sqlFinal->where($coluna, $valor);
        }

        if (!empty($search['value'])) {
            $sqlFinal->where('p.idPreProjeto like ? OR p.NomeProjeto like ? OR  p.nomeproponente like ?', '%' . $search['value'] . '%');
        }

        $sqlFinal->order($order);

        if (!is_null($start) && $limit) {
            $start = (int)$start;
            $limit = (int)$limit;
            $sqlFinal->limitPage($start, $limit);
        }
        return $db->fetchOne($sqlFinal);
    }

    public function valorTotalSolicitadoNaProposta($idPreProjeto)
    {

        $select = new Zend_Db_Expr("SELECT sac.fnSolicitadoNaProposta({$idPreProjeto}) as valorTotalSolicitado");

        try {
            $db = Zend_Db_Table::getDefaultAdapter();
        } catch (Zend_Exception_Db $e) {
            $this->view->message = $e->getMessage();
        }
        return $db->fetchOne($select);
    }


    public function spChecklistParaApresentacaoDeProposta($idPreProjeto)
    {

        $select = new Zend_Db_Expr("EXEC sac.spChecklistParaApresentacaoDeProposta {$idPreProjeto}");

        try {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_Db::FETCH_OBJ);
            $result = $db->fetchAll($select);
        } catch (Zend_Exception_Db $e) {
            $this->view->message = $e->getMessage();
        }
        return $result;
    }

    public function buscarProponenteProposta($idPreProjeto)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);

        $sql = $this->select()
            ->from(array('pre' => 'PreProjeto'), array(), $this->_schema)
            ->join(array('a' => 'agentes'), 'pre.idAgente = a.idAgente', array('*'), $this->getSchema('agentes'))
            ->where("pre.idPreProjeto = ?", $idPreProjeto);;
        return $db->fetchRow($sql);

    }


    public function verificarCNAEProponenteComProdutoPrincipal($idPreProjeto)
    {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(['proposta' => $this->_name], '', $this->_schema);

        $select->joinInner(['agentes' => 'agentes'], 'proposta.idAgente = agentes.idAgente', '', $this->getSchema('agentes'));

        $select->joinInner(['vw' => 'vwPessoaJuridica_CNAE'], 'agentes.CNPJCPF = vw.NR_CNPJ', '*', $this->_schema);

        $select->joinInner(['produtoprincipal' => 'vwPlanoDeDistribuicaoProduto'],
            'proposta.idPreProjeto = produtoprincipal.idProjeto', '', $this->_schema);

        $select->joinInner(['cnae' => 'tbCnaeCultural'], 'produtoprincipal.Area = cnae.cdArea AND produtoprincipal.Segmento = cnae.cdSegmento AND vw.CD_CNAE  = cnae.cdCnae', '', $this->_schema);

        $select->where('agentes.TipoPessoa = ?', 1);
        $select->where('produtoprincipal.stPrincipal = ?', 1);
        $select->where('proposta.idPreProjeto  = ?', $idPreProjeto);
        $select->limit(1);

        return $this->fetchRow($select);

    }
}