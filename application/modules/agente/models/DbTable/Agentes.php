<?php

class Agente_Model_DbTable_Agentes extends MinC_Db_Table_Abstract
{
    protected $_name = 'Agentes';
    protected $_schema = 'agentes';
    protected $_primary = 'idAgente';

    public function buscarAgentes(
        $cnpjcpf = null,
        $nome = null,
        $idAgente = null
    )
    {

        $schemaAgentes = $this->getSchema('agentes');

        $a = array(
            'a.idAgente'
        , 'a.CNPJCPF'
        , 'a.CNPJCPFSuperior'
        , 'a.TipoPessoa'
        );

        $e = array(
            'e.TipoLogradouro'
        , 'e.Cidade'
        , 'e.Cep as cep'
        , 'e.UfDescricao'
        , 'e.Status'
        , 'e.TipoEndereco'
        , 'e.idEndereco'
        , 'e.Logradouro'
        , 'e.Numero'
        , 'e.Complemento'
        , 'e.Bairro'
        , 'e.Divulgar as divulgarendereco'
        , 'e.Status as enderecocorrespondencia'
        );

        $t = array(
            't.stTitular'
        , 't.cdArea'
        , 't.cdSegmento'
        );

        $camposCidade = [
            'm.Descricao as dscidade'
        ];

        $select = $this->select();
        $select->setIntegrityCheck(false)->distinct();
        $select->from(array('a' => 'Agentes'), $a, $schemaAgentes);
        $select->joinLeft(array('n' => 'Nomes'), 'n.idAgente = a.idAgente', array('n.Descricao as nome'), $schemaAgentes)
            ->joinLeft(array('e' => 'EnderecoNacional'), 'e.idAgente = a.idAgente', $e, $schemaAgentes)
            ->joinLeft(array('m' => 'Municipios'), 'm.idMunicipioIBGE = e.Cidade', $camposCidade, $schemaAgentes)
            ->joinLeft(array('u' => 'UF'), 'u.idUF = m.idUFIBGE', 'u.Sigla as dsuf', $schemaAgentes)
            ->joinLeft(array('ve' => 'Verificacao'), 've.idVerificacao = e.TipoEndereco', 've.Descricao as dstipoendereco', $schemaAgentes)
            ->joinLeft(array('vl' => 'Verificacao'), 'vl.idVerificacao = e.TipoLogradouro', 'vl.Descricao as dsTipoLogradouro', $schemaAgentes)
            ->joinLeft(array('t' => 'tbTitulacaoConselheiro'), 't.idAgente = a.idAgente', $t, $schemaAgentes)
            ->joinLeft(array('v' => 'Visao'), 'v.idAgente = a.idAgente', '*', $schemaAgentes)
            ->joinLeft(array('sa' => 'Area'), 'sa.Codigo = t.cdArea', 'sa.Descricao as dsarea', $this->getSchema('sac'))
            ->joinLeft(array('ss' => 'Segmento'), 'ss.Codigo = t.cdSegmento', 'ss.Descricao as dssegmento', $this->getSchema('sac'))
            ->where('a.TipoPessoa = false or a.TipoPessoa = true');

        if (!empty($cnpjcpf)) {
            $select->where('a.CNPJCPF = ?', $cnpjcpf);
        }
        if (!empty($nome)) {
            $select->where('n.Descricao LIKE ?', '%' . $nome . '%');
        }
        
        if (!empty($idAgente)) {
            # busca de acordo com o id do agente
            $select->where('a.idAgente = ?', $idAgente);
        }


        $result = $this->fetchAll($select);
        $result = ($result) ? $result->toArray() : array();

        foreach ($result as &$value) {
            $value['CNPJCPF'] = Mascara::addMaskCpfCnpj($value['CNPJCPF']);
            if (isset($value['cep'])) {
                $value['cep'] = Mascara::addMaskCEP($value['cep']);
            }
        }

        return $result;
    }

    public function BuscarComponente()
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('A' => $this->_name)
        );
        $select->joinInner(
            array('N' => 'Nomes'), 'N.idAgente = A.idAgente', array('N.Descricao as nomeConselheiro', 'N.idAgente as agente')
        );
        $select->joinInner(
            array('V' => 'Visao'), 'V.idAgente = A.idAgente'
        );
        $select->where('V.Visao = ?', 210);
        $select->order('N.Descricao');

        return $this->fetchAll($select);
    }

    public function BuscaAgente($cnpjcpf = null)
    {
        $select = $this->select(Zend_Db_Table_Abstract::SELECT_WITH_FROM_PART);
        $select->setIntegrityCheck(false);
        $select->where('CNPJCPF = ?', trim($cnpjcpf));

        return $this->fetchAll($select);
    }

    public function inserirAgentes($dados)
    {
        $insert = $this->insert($dados);
        return $insert;
    }

    public function buscarAgenteENome(
        $where = array(),
        $order = array(),
        $tamanho = -1,
        $inicio = -1
    )
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name), '*', $this->_schema);
        $slct->joinInner(array('m' => 'Nomes'), 'a.idAgente = m.idAgente', array('*'), $this->_schema);

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        return $this->fetchAll($slct);
    }

    public function buscarFornecedor($where)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('A' => $this->_name), array('A.CNPJCPF', 'A.idAgente')
        );

        $select->joinInner(
            array('N' => 'Nomes'), 'N.idAgente = A.idAgente', array('N.Descricao AS nome')
        );
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }
        $select->group('A.CNPJCPF');
        $select->group('A.idAgente');
        $select->group('N.Descricao');

        $select->order('N.Descricao');

        return $this->fetchAll($select);
    }

    public function buscarFornecedorFiscalizacao($where)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('A' => $this->_name), array('A.CNPJCPF', 'A.idAgente')
        );
        $select->joinInner(
            array('U' => 'vwUsuariosOrgaosGrupos'), 'U.usu_identificacao = A.CNPJCPF', array(), 'tabelas'
        );
        $select->joinInner(
            array('N' => 'Nomes'), 'N.idAgente = A.idAgente', array('N.Descricao AS nome')
        );
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }
        $select->group('A.CNPJCPF');
        $select->group('A.idAgente');
        $select->group('N.Descricao');

        $select->order('N.Descricao');

        return $this->fetchAll($select);
    }

    public function buscarAgenteVinculoResponsavel(
        $where = array(),
        $order = array(),
        $tamanho = -1,
        $inicio = -1
    )
    {

        $objAgentes = $this->select();
        $objAgentes->distinct();
        $objAgentes->setIntegrityCheck(false);
        $objAgentes->from(
            array('ag' => $this->_name), array('ag.CNPJCPF'), $this->_schema
        );
        $objAgentes->joinInner(
            array('nm' => 'Nomes'), "nm.idAgente = ag.idAgente", array('nm.Descricao as NomeAgente'), $this->_schema
        );
        $objAgentes->joinLeft(
            array('vr' => 'tbVinculo'), "vr.idUsuarioResponsavel  = ag.idAgente", array("vr.idVinculo as idVinculoResponsavel"), $this->_schema
        );
        $objAgentes->joinInner(
            array('vprp' => 'tbVinculoProposta'), "vprp.idVinculo = vr.idVinculo", array("vprp.siVinculoProposta", "vprp.idPreProjeto", 'vprp.idVinculoProposta'), $this->_schema
        );
        $objAgentes->joinLeft(array('pr' => 'Projetos'), 'vprp.idPreProjeto = pr.idProjeto', array('pr.AnoProjeto ' . parent::getConcatExpression() . ' pr.Sequencial as pronac'), $this->getSchema('sac'));

        foreach ($where as $coluna => $valor) {
            $objAgentes->where($coluna, $valor);
        }
        return $this->fetchAll($objAgentes);
    }

    public function buscarAgenteVinculoProponente(
        $where = array(),
        $order = array(),
        $tamanho = -1,
        $inicio = -1
    )
    {

        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('ag' => $this->_name),
            array('ag.CNPJCPF', 'ag.idAgente'),
            $this->_schema
        );
        $slct->joinInner(
            array('nm' => 'Nomes'), "nm.idAgente = ag.idAgente",
            array('nm.Descricao as nomeagente'),
            $this->_schema

        );
        $slct->joinLeft(
            array('vp' => 'tbVinculo'), "vp.idAgenteProponente  = ag.idAgente",
            array("vp.idVinculo as idVinculoproponente", "sivinculo", "idUsuarioResponsavel"),
            $this->_schema
        );
        $slct->joinLeft(
            array('vprp' => 'tbvinculoproposta'), "vprp.idVinculo = vp.idVinculo",
            array("vprp.sivinculoproposta", "vprp.idPreProjeto", "vprp.idVinculo"),
            $this->_schema
        );

        $slct->joinLeft(
            array('pr' => 'projetos'), "pr.idprojeto = vprp.idPreProjeto",
            array('pr.idpronac'),
            $this->getSchema('sac')
        );

        $slct->joinLeft(
            array('usu' => 'usuarios'), "usu.usu_identificacao = ag.CNPJCPF",
            array('usu.usu_identificacao as usuariovinculo'),
            $this->getSchema('tabelas')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        //echo $slct;die;
        return $this->fetchAll($slct);
    }

    public function buscarNovoProponente($where = array(), $idResponsavel)
    {
        $select = $this->select();
        $select->distinct();
        $select->setIntegrityCheck(false);
        $select->from(
            array('ag' => $this->_name),
            array('ag.CNPJCPF', 'ag.idAgente'),
            $this->_schema
        );

        $select->joinInner(
            array('nm' => 'Nomes'), "nm.idAgente = ag.idAgente",
            array('nm.Descricao as nomeagente'),
            $this->_schema
        );

        $select->joinLeft(
            array('vp' => 'tbVinculo'), "vp.idAgenteProponente = ag.idAgente and vp.idUsuarioResponsavel = $idResponsavel",
            array("vp.idVinculo as idVinculoproponente", "siVinculo", "idUsuarioResponsavel"),
            $this->_schema
        );

        $select->joinLeft(
            array('v' => 'Visao'), "v.idAgente = ag.idAgente and v.Visao = 146",
            array('v.Visao as usuariovinculo'),
            $this->_schema
        );

        foreach ($where as $coluna => $valor) {
            if ($valor) {
                $select->where($coluna, $valor);
            }
        }
        return $this->fetchAll($select);
    }

    public function todosPareceristas()
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('a' => $this->_name),
            array('*')
        );

        $slct->joinInner(
            array('n' => 'Nomes'),
            'a.idAgente = n.idAgente',
            array('n.idAgente AS idParecerista', 'n.Descricao AS Nome')
        );

        $slct->joinInner(array('v' => 'Visao'), 'n.idAgente = v.idAgente', array());

        $slct->where('v.Visao = ?', 209);
        $slct->where('n.TipoNome = ?', 18);

        $slct->order('n.Descricao');
        return $this->fetchAll($slct);
    }

    public function pareceristasDoOrgao($idOrgao)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name), array());

        $slct->joinInner(array('n' => 'Nomes'), 'a.idAgente = n.idAgente', array('n.idAgente AS idParecerista', 'n.Descricao AS Nome'));

        $slct->joinInner(array('u' => 'vwUsuariosOrgaosGrupos'), 'a.CNPJCPF = u.usu_identificacao AND u.sis_codigo = 21 AND u.gru_codigo = 94 OR u.gru_codigo = 105', array('u.org_superior AS idOrgao'), 'TABELAS');

        $slct->joinInner(array('v' => 'Visao'), 'n.idAgente = v.idAgente', array());


        $dadosWhere = array('v.Visao = ?' => 209, 'n.TipoNome = ?' => 18, 'u.org_superior = ?' => $idOrgao);

        foreach ($dadosWhere as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order('n.Descricao');
        return $this->fetchAll($slct);
    }

    public function consultaPareceristasDoOrgao($idOrgao = null)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(array('a' => $this->_name), array(), array(), $this->getSchema('agentes'));

        $slct->joinInner(array('n' => 'Nomes'), 'a.idAgente = n.idAgente', array('n.idAgente AS idParecerista', 'n.Descricao AS Nome'));

        if ($idOrgao == null) {
            $slct->joinInner(array('u' => 'vwUsuariosOrgaosGrupos'), 'a.CNPJCPF = u.usu_identificacao', array(), 'TABELAS');
        } else {
            $slct->joinInner(array('u' => 'vwUsuariosOrgaosGrupos'), 'a.CNPJCPF = u.usu_identificacao', array('u.uog_orgao AS idOrgao'), 'TABELAS');
        }

        $slct->joinInner(array('v' => 'Visao'), 'n.idAgente = v.idAgente', array());

        $dadosWhere['v.Visao = ?'] = 209;
        $dadosWhere['n.TipoNome = ?'] = 18;
        $dadosWhere['u.sis_codigo = ?'] = 21;
        $dadosWhere['u.gru_codigo = ?'] = 94;

        if (!empty($idOrgao)) {
            $dadosWhere['u.uog_orgao = ?'] = $idOrgao;
        }
        foreach ($dadosWhere as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order('n.Descricao');
        return $this->fetchAll($slct);
    }

    public function buscarPareceristas(
        $idOrgao,
        $idArea,
        $idSegmento
    )
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(
            array('a' => $this->_name),
            array(), $this->getSchema('agentes')
        );
        $slct->joinInner(
            array('n' => 'Nomes'), 'a.idAgente = n.idAgente',
            array('u.usu_codigo AS id', 'n.Descricao AS nome'), $this->getSchema('agentes')
        );
        $slct->joinInner(
            array('u' => 'vwUsuariosOrgaosGrupos'), 'a.CNPJCPF = u.usu_Identificacao AND sis_codigo = 21 AND (gru_codigo = 94 OR gru_codigo = 105)',
            array(), 'TABELAS'
        );
        $slct->joinInner(
            array('v' => 'Visao'), 'n.idAgente = v.idAgente',
            array(), $this->getSchema('agentes')
        );
        $slct->joinInner(
            array('c' => 'tbCredenciamentoParecerista'), 'a.idAgente = c.idAgente',
            array(), $this->getSchema('agentes')
        );

        $dadosWhere["v.Visao = ?"] = 209;
        $dadosWhere["n.TipoNome = ?"] = 18;
        $dadosWhere["c.idCodigoArea = ?"] = $idArea;
        $dadosWhere["c.idCodigoSegmento = ?"] = $idSegmento;
        $dadosWhere["c.idverificacao = ?"] = 251;
        $dadosWhere["u.org_superior = ?"] = $idOrgao;
        $dadosWhere["NOT EXISTS(SELECT TOP 1 * FROM agentes.dbo.tbAusencia WHERE " . $this->getDate() . " BETWEEN dtInicioAusencia AND dtFimAusencia AND idAgente = a.idAgente)"] = '';

        foreach ($dadosWhere as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order('n.Descricao');

        return $this->fetchAll($slct);
    }

    public function buscarPareceristasIphan(
        $idOrgao,
        $idArea,
        $idSegmento
    )
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(
            array('a' => $this->_name),
            array(), 'agentes'
        );
        $slct->joinInner(
            array('n' => 'Nomes'), 'a.idAgente = n.idAgente',
            array('u.usu_codigo AS id', 'n.Descricao AS nome'), 'agentes'
        );
        $slct->joinInner(
            array('u' => 'vwUsuariosOrgaosGrupos'), 'a.CNPJCPF = u.usu_Identificacao AND sis_codigo = 21 AND (gru_codigo = 94 OR gru_codigo = 105)',
            array(), 'TABELAS'
        );
        $slct->joinInner(
            array('v' => 'Visao'), 'n.idAgente = v.idAgente',
            array(), 'agentes'
        );
        $slct->joinInner(
            array('c' => 'tbCredenciamentoParecerista'), 'a.idAgente = c.idAgente',
            array(), 'agentes'
        );

        $dadosWhere["v.Visao = ?"] = 209;
        $dadosWhere["n.TipoNome = ?"] = 18;
        $dadosWhere["c.idCodigoArea = ?"] = $idArea;
        $dadosWhere["c.idCodigoSegmento = ?"] = $idSegmento;
        $dadosWhere["u.org_superior = ?"] = 91;
        $dadosWhere["u.usu_orgao = ?"] = $idOrgao;  // especificidade do IPHAN - puxa codigo do orgao 
        $dadosWhere["NOT EXISTS(SELECT TOP 1 * FROM agentes.dbo.tbAusencia WHERE " . $this->getDate() . " BETWEEN dtInicioAusencia AND dtFimAusencia AND idAgente = a.idAgente)"] = '';

        foreach ($dadosWhere as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order('n.Descricao');

        return $this->fetchAll($slct);
    }

    public function consultaPareceristasPainel($nome, $cpf)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name), array('*'));

        $slct->joinInner(array('n' => 'Nomes'), 'a.idAgente = n.idAgente', array('n.idAgente AS idParecerista', 'n.Descricao AS Nome')
        );

        $slct->joinInner(array('v' => 'Visao'), 'n.idAgente = v.idAgente', array()
        );

        $slct->where('v.Visao = ?', 209);
        $slct->where('n.TipoNome = ?', 18);

        if (!empty($nome)) {
            $slct->where("n.Descricao like '%" . $nome . "%'");
        }
        if (!empty($cpf)) {
            $slct->where('a.CNPJCPF = ?', $cpf);
        }

        $slct->distinct();
        $slct->order('n.Descricao');
        return $this->fetchAll($slct);
    }

    public function dadosParecerista($where)
    {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array("ag" => $this->_name)
            , array("ag.idAgente")
        );
        $select->joinInner(
            array("nm" => "Nomes")
            , "nm.idAgente = ag.idAgente"
            , array(
                "nmParecerista" => "nm.Descricao"
            )
            , $this->getSchema('agentes')
        );
        $select->joinLeft(
            array("cp" => "tbCredenciamentoParecerista")
            , "cp.idAgente = ag.idAgente"
            , array(
                "nmParecerista" => "nm.Descricao",
                "qtPonto"
            )
            , $this->getSchema('agentes')
        );
        $select->joinLeft(
            array("ar" => "Area")
            , "ar.Codigo = cp.idCodigoArea"
            , array(
                'Area' => 'ar.Descricao'
            )
            , $this->_schema
        );
        $select->joinLeft(
            array("seg" => "Segmento")
            , "seg.Codigo = cp.idCodigoSegmento"
            , array(
                'Segmento' => 'seg.Descricao'
            )
            , $this->_schema
        );
        $select->joinLeft(
            array("au" => "tbAusencia")
            , "au.idAgente = ag.idAgente and au.idTipoAusencia = 2 and au.dtFimAusencia >= " . $this->getDate()
            , array(
                'au.dtFimAusencia',
                'au.dtInicioAusencia',
            )
            , $this->getSchema('agentes')
        );
        $select->joinInner(
            array("usu" => "Usuarios")
            , "ag.CNPJCPF = usu.usu_identificacao"
            , array()
            , 'TABELAS'
        );
        $select->joinInner(
            array("uog" => "UsuariosXOrgaosXGrupos")
            , "usu.usu_codigo = uog.uog_usuario and uog.uog_status = 1"
            , array()
            , 'TABELAS'
        );
        $select->joinInner(
            array("org" => "Orgaos")
            , "org.Codigo = uog.uog_orgao"
            , array()
            , $this->_schema
        );

        $select->where('uog.uog_grupo = 94');
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }
        return $this->fetchRow($select);
    }

    public function buscarAgentesCpfVinculo($where = array())
    {
        $sl = $this->select();
        $sl->setIntegrityCheck(false);
        $sl->from(
            array('ag' => $this->_name), array('ag.CNPJCPF', 'ag.idAgente')
        );
        $sl->joinLeft(
            array('v' => 'tbVinculo'), "v.idAgenteProponente = ag.idAgente ", array('v.siVinculo')
        );
        $sl->joinInner(
            array('nm' => 'Nomes'), "nm.idAgente = ag.idAgente", array('nm.Descricao')
        );

        foreach ($where as $key => $valor) {
            $sl->where($key, $valor);
        }
        return $this->fetchAll($sl);
    }

    public function buscarDirigentes($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name),
            array('CNPJCPFDirigente' => 'a.CNPJCPF', 'idAgente')
            , $this->getSchema('agentes')
        );

        $slct->joinInner(array('v' => 'Vinculacao'),
            'a.idAgente = v.idAgente',
            array()
            , $this->getSchema('agentes')
        );

        $slct->joinInner(array('n' => 'Nomes'),
            'a.idAgente = n.idAgente',
            array('NomeDirigente' => 'n.Descricao')
            , $this->getSchema('agentes')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order($order);
        return $this->fetchAll($slct);
    }

    public function buscarUfMunicioAgente(
        $where = array(),
        $order = array(),
        $tamanho = -1,
        $inicio = -1,
        $retornaSelect = false
    )
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name),
            array('a.CNPJCPF',
                'a.idAgente'),
            $this->getSchema('agentes')
        );
        $slct->joinInner(array('e' => 'EnderecoNacional'),
            'a.idAgente = e.idAgente',
            array(),
            $this->getSchema('agentes')
        );
        $slct->joinInner(array('mun' => 'Municipios'),
            'mun.idMunicipioIBGE = e.Cidade',
            array('mun.idMunicipioIBGE',
                'mun.idUFIBGE',
                'mun.Descricao as DescricaoMunicipio'),
            $this->getSchema('agentes')
        );
        $slct->joinInner(array('uf' => 'UF'),
            'uf.idUF = mun.idUFIBGE',
            array('*'),
            $this->getSchema('agentes')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        if ($retornaSelect)
            return $slct;
        else
            return $this->fetchAll($slct);
    }

    public function buscarAgenteVinculo(
        $where = array(),
        $order = array(),
        $tamanho = -1,
        $inicio = -1
    )
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('a' => $this->_name));
        $slct->joinInner(array('v' => 'TbVinculo'),
            'a.idAgente=v.idUsuarioResponsavel');
        $slct->joinInner(array('n' => 'Nomes'),
            'a.idAgente=n.idAgente');

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        return $this->fetchAll($slct);
    }

    public function gerenciarResponsaveisListas($siVinculo, $idUsuario)
    {
        $a = $this->select();
        $a->setIntegrityCheck(false);
        $a->from(
            array('a' => $this->_name),
            array('CNPJCPF as CNPJCPFProponente'),
            $this->_schema
        );
        $a->joinInner(
            array('b' => 'tbVinculo'), "a.idAgente = b.idAgenteProponente",
            array('idVinculo', 'siVinculo', 'idUsuarioResponsavel'), $this->_schema
        );
        $a->joinInner(
            array('c' => 'SGCacesso'), "c.IdUsuario = b.idUsuarioResponsavel",
            array('IdUsuario', 'Cpf as CPFResponsavel', 'Nome AS NomeResponsavel'), $this->getSchema('controledeacesso')
        );
        $a->joinInner(
            array('d' => 'Nomes'), "b.idAgenteProponente = d.idAgente",
            array('Descricao as Proponente'), $this->_schema
        );
        $a->where('c.IdUsuario = ?', $idUsuario);
        $a->where('b.siVinculo = ?', $siVinculo);
        $a->where('a.CNPJCPF <> c.Cpf');

        $b = $this->select();
        $b->setIntegrityCheck(false);
        $b->from(
            array('a' => $this->_name),
            array('CNPJCPF as CNPJCPFProponente'),
            $this->_schema
        );
        $b->joinInner(
            array('b' => 'tbVinculo'), "a.idAgente = b.idAgenteProponente",
            array('idVinculo', 'siVinculo', 'idUsuarioResponsavel'), $this->_schema
        );
        $b->joinInner(
            array('c' => 'SGCacesso'), "a.CNPJCPF = c.Cpf",
            array('IdUsuario'), $this->getSchema('controledeacesso')
        );
        $b->joinInner(
            array('e' => 'SGCacesso'), "e.IdUsuario = b.idUsuarioResponsavel",
            array('Cpf AS CPFResponsavel', 'Nome AS NomeResponsavel'), $this->getSchema('controledeacesso')
        );
        $b->joinInner(
            array('d' => 'Nomes'), "b.idAgenteProponente = d.idAgente",
            array('Descricao as Proponente'), $this->_schema
        );
        $b->where('c.IdUsuario = ?', $idUsuario);
        $b->where('b.siVinculo = ?', $siVinculo);
        $b->where(new Zend_Db_Expr('a.CNPJCPF = c.Cpf'));

        $c = $this->select();
        $c->setIntegrityCheck(false);
        $c->from(
            array('a' => $this->_name),
            array('CNPJCPF as CNPJCPFProponente'),
            $this->_schema
        );
        $c->joinInner(
            array('b' => 'tbVinculo'), "a.idAgente = b.idAgenteProponente",
            array('idVinculo', 'siVinculo', 'idUsuarioResponsavel'), $this->_schema
        );
        $c->joinInner(
            array('c' => 'Vinculacao'), "b.idAgenteProponente = c.idVinculoPrincipal",
            array(), $this->_schema
        );
        $c->joinInner(
            array('d' => 'Visao'), "c.idAgente = d.idAgente",
            array(), $this->_schema
        );
        $c->joinInner(
            array('e' => 'Agentes'), "d.idAgente = e.idAgente",
            array(), $this->_schema
        );
        $c->joinInner(
            array('f' => 'SGCacesso'), "e.CNPJCPF = f.Cpf",
            array('IdUsuario'), $this->getSchema('controledeacesso')
        );
        $c->joinInner(
            array('g' => 'SGCacesso'), "g.IdUsuario = b.idUsuarioResponsavel",
            array('Cpf as CPFResponsavel', 'Nome as NomeResponsavel'), $this->getSchema('controledeacesso')
        );
        $c->joinInner(
            array('h' => 'Nomes'), "c.idVinculoPrincipal = h.idAgente",
            array('Descricao as Proponente'), $this->_schema
        );
        $c->where('d.Visao = ?', 198);
        $c->where('f.IdUsuario = ?', $idUsuario);
        $c->where('b.siVinculo = ?', $siVinculo);
        $c->where('a.CNPJCPF <> f.Cpf');

        $slctUnion = $this->select()
            ->union(array('(' . $a . ')', '(' . $b . ')', '(' . $c . ')'))
            ->order('NomeResponsavel');

        return $this->fetchAll($slctUnion);
    }

    public function listarVincularPropostaCombo($idResponsavel)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('a' => $this->_name),
            array('CNPJCPF AS CNPJCPFProponente'),
            $this->_schema
        );
        $slct->joinInner(
            array('b' => 'tbVinculo'), 'a.idAgente = b.idAgenteProponente',
            array('idVinculo', 'siVinculo', 'idUsuarioResponsavel'), $this->_schema
        );
        $slct->joinInner(array('c' => 'SGCacesso'), 'a.CNPJCPF = c.Cpf',
            array('IdUsuario'), $this->getSchema('controledeacesso')
        );
        $slct->joinInner(array('d' => 'Nomes'),
            'b.idAgenteProponente = d.idAgente',
            array('Descricao AS Proponente'), $this->_schema
        );
        $slct->joinInner(array('e' => 'SGCacesso'), 'e.IdUsuario = b.idUsuarioResponsavel',
            array('Cpf AS CPFResponsavel', 'Nome AS NomeResponsavel'), $this->getSchema('controledeacesso')
        );

        $slct->where('c.IdUsuario = ?', $idResponsavel);
        $slct->where('b.siVinculo = ?', '2');
        $slct->where(new Zend_Db_Expr('a.CNPJCPF = c.Cpf'));
//        echo $slct;
        return $this->fetchAll($slct);
    }

    /**
     * Migração da função fnNome do banco agentes
     * @return varchar[150]
     */
    public function obterNomeAgente($idAgente)
    {
        $objQuery = $this->select();
        $objQuery->setIntegrityCheck(false);
        $objQuery->from(
            array('Agentes' => $this->_name),
            array('TipoPessoa'),
            $this->_schema
        );

        $objQuery->joinInner(
            array('Nomes' => 'Nomes'),
            'Nomes.idAgente = Agentes.idAgente',
            array('Descricao'),
            $this->_schema
        );

        $objQuery->where('Agentes.idAgente = ?', $idAgente);
        $objQuery->where('Agentes.Status = ?', 1);

        $resultadoBuscaAgente = $this->fetchRow($objQuery);
        $nome = '';
        if ($resultadoBuscaAgente) {
            $resultadoBuscaAgente = $resultadoBuscaAgente->toArray();
            if ($resultadoBuscaAgente['TipoPessoa'] == 0) {
                $objQueryPessoaFisica = $this->select();
                $objQueryPessoaFisica->setIntegrityCheck(false);
                $objQueryPessoaFisica->from(
                    array('Nomes' => 'Nomes'),
                    array('Descricao'),
                    $this->_schema
                );
                $objQueryPessoaFisica->where('idAgente = ?', $idAgente);
                $objQueryPessoaFisica->where('TipoNome = ?', 18);
                $resultadoPessoaFisica = $this->fetchRow($objQueryPessoaFisica);
                $nome = 'Nome de pesssoa f&iacute;sica n&atilde;o informado';
                if ($resultadoPessoaFisica) {
                    $resultadoPessoaFisica = $resultadoPessoaFisica->toArray();
                    $nome = $resultadoPessoaFisica['Descricao'];
                }
            } else {
                $objQueryPessoaJuridica = $this->select();
                $objQueryPessoaJuridica->setIntegrityCheck(false);
                $objQueryPessoaJuridica->from(
                    array('Nomes' => 'Nomes'),
                    array('Descricao'),
                    $this->_schema
                );
                $objQueryPessoaJuridica->where('idAgente = ?', $idAgente);
                $objQueryPessoaJuridica->where('TipoNome = ?', 19);
                $resultadoPessoaJuridica = $this->fetchRow($objQueryPessoaJuridica);
                $nome = 'Nome de pesssoa jur&iacute;dica n&atilde;o informado';
                if ($resultadoPessoaJuridica) {
                    $resultadoPessoaJuridica = $resultadoPessoaJuridica->toArray();
                    $nome = $resultadoPessoaJuridica['Descricao'];
                }
            }
        }

        return $nome;
    }

    public function buscarVinculados(
        $cnpjcpfSuperior = null,
        $nome = null,
        $idAgente = null,
        $idVinculado = null,
        $idVinculoPrincipal = null
    )
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $a = array(
            'a.idAgente'
        , 'a.CNPJCPF'
        , 'a.CNPJCPFSuperior'
        );

        $objSelect = $this->select();
        $objSelect->from(array('a' => $this->_name), $a, $this->_schema)
            ->joinLeft(array('n' => 'Nomes'), 'n.idAgente = a.idAgente', array('n.Descricao AS Nome'), 'agentes')
            ->joinLeft(array('vis' => 'Visao'), 'a.idAgente = vis.idAgente', null, 'agentes')
            ->joinLeft(array('ver' => 'Verificacao'), 'ver.idVerificacao = vis.Visao', null, 'agentes')
            ->joinLeft(array('vin' => 'Vinculacao'), 'a.idAgente = vin.idAgente', null, 'agentes')
            ->joinLeft(array('tp' => 'Tipo'), 'tp.idTipo = ver.IdTipo', null, 'agentes')
            ->where('a.TipoPessoa = false OR a.TipoPessoa = true')
            ->where('n.TipoNome::int = 18 OR n.TipoNome::int = 19')
            ->where('vis.Visao = 198');

        if (!empty($cnpjcpfSuperior)) { // busca pelo cnpj/cpf com o vinculo principal
            $objSelect->where('a.CNPJCPFSuperior = ?', $cnpjcpfSuperior);
        }
        if (!empty($nome)) { // filtra pelo nome
            $objSelect->where('n.Descricao LIKE ?', "$nome%");
        }
        if (!empty($idAgente)) { // busca pelo idAgente
            $objSelect->where('vin.idAgente =  ?', $idAgente);
        }
        if (!empty($idVinculado)) { // busca pelo idVinculado
            $objSelect->where('vin.idVinculado =  ?', $idVinculado);
        }
        if (!empty($idVinculoPrincipal)) {// busca pelo idVinculoPrincipal
            $objSelect->where('vin.idVinculoPrincipal =  ?', $idVinculoPrincipal);
        }

        $objSelect->order(array('n.Descricao'));
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($objSelect);
    }

    public function obterNomeEnderecoCorrespondenciaAgente($where) {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('a' => $this->_name),
            array(
                'a.idAgente',
                'a.CNPJCPF',
                'a.Usuario',
            ),
            $this->_schema
        );

        $select->joinLeft(
            array('n' => 'Nomes'),
            'n.idAgente = a.idAgente',
            array(
                'n.idNome',
                'n.TipoNome',
                'n.Descricao as Nome'
            ),
            $this->_schema
        );

        $select->joinLeft(
            array('e' => 'EnderecoNacional'),
            'e.idAgente = a.idAgente AND e.Status = \'t\'',
            array(
                'e.idEndereco',
                'e.TipoEndereco',
                'e.TipoLogradouro',
                'e.Logradouro',
                'e.Numero',
                'e.Bairro',
                'e.Complemento',
                'e.Cidade',
                'e.UF',
                'e.Cep',
                'e.Divulgar as DivulgarEndereco',
                'e.Status as Correspondencia',
            ),
            $this->_schema
        );

        $select->where('n.TipoNome = 18 OR n.TipoNome = ?', 19);

        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        return $this->fetchRow($select);

    }
}