<?php

class Usuariosorgaosgrupos extends MinC_Db_Table_Abstract
{

    protected $_schema = 'tabelas';
    protected $_name = 'UsuariosXOrgaosXGrupos';
    protected $_primary = 'uog_usuario';

    /**
     * Migração da view "tabelas"."vwUsuariosOrgaosGrupos"
     */
    public function obterUsuariosOrgaosEGrupos()
    {
        $zendDbQuery = $this->select();
        $zendDbQuery->setIntegrityCheck(false);
        $zendDbQuery->from(
            $this->_name,
            [
                'uog_usuario',
                'uog_orgao',
                'uog_grupo',
                'uog_status',
            ],
            $this->getSchema('tabelas')
        );
        $zendDbQuery->joinInner(
            'Grupos',
            'Grupos.gru_codigo = UsuariosXOrgaosXGrupos.uog_grupo',
            [
                'gru_codigo',
                'gru_nome',
                'gru_status',
                'gru_sistema'
            ],
            $this->getSchema('tabelas')
        );
        $zendDbQuery->joinInner(
            'Sistemas',
            'Sistemas.sis_codigo = Grupos.gru_sistema',
            [
                'sis_codigo',
                'sis_sigla',
                'sis_nome',
            ],
            $this->getSchema('tabelas')
        );
        $zendDbQuery->joinInner(
            'Orgaos',
            'Orgaos.org_codigo = UsuariosXOrgaosXGrupos.uog_orgao',
            [
                'org_superior' => new Zend_Db_Expr('"tabelas"."fnCodigoOrgaoEstrutura"("UsuariosXOrgaosXGrupos"."uog_orgao", 1)'),
                'org_siglaautorizado' => new Zend_Db_Expr('"tabelas"."fnEstruturaOrgao"("UsuariosXOrgaosXGrupos"."uog_orgao", 0)'),
                'org_codigo',
                'org_pessoa',
            ],
            $this->getSchema('tabelas')
        );
        $zendDbQuery->joinInner(
            'Pessoa_Identificacoes',
            'Pessoa_Identificacoes.pid_pessoa = Orgaos.org_pessoa and Pessoa_Identificacoes.pid_meta_dado = 1 and Pessoa_Identificacoes.pid_sequencia = 1',
            [
                'org_nomeautorizado' => 'pid_identificacao',
                'pid_meta_dado',
                'pid_pessoa',
                'pid_sequencia',
            ],
            $this->getSchema('tabelas')
        );
        $zendDbQuery->joinInner(
            'Usuarios',
            'Usuarios.usu_codigo = UsuariosXOrgaosXGrupos.uog_usuario',
            [
                'id_unico' => new Zend_Db_Expr('(trim(BOTH \' \' FROM cast("Usuarios"."usu_pessoa" as varchar(120))) || trim(BOTH \' \' FROM cast("UsuariosXOrgaosXGrupos"."uog_orgao" as varchar(120))) || trim(BOTH \' \' FROM cast("uog_grupo" as varchar(120))))'),
                'usu_orgaolotacao' => new Zend_Db_Expr('"tabelas"."fnEstruturaOrgao"("usu_orgao", 0)'),
                'usu_codigo',
                'usu_identificacao',
                'usu_nome',
                'usu_orgao',
                'usu_pessoa'
            ],
            $this->getSchema('tabelas')
        );
        $zendDbQuery->where('"Grupos"."gru_status" > ?', 0);

        return $this->fetchAll($zendDbQuery);
    }

    public function buscarUsuariosOrgaosGrupos($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array('g.gru_nome', 'g.gru_codigo')
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array('s.sis_codigo')
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array('u.usu_codigo', 'u.usu_identificacao', 'u.usu_nome')
        );
        $slct->joinInner(array('o' => 'Orgaos'), 'ug.uog_orgao = o.org_codigo', array('o.org_sigla', 'o.org_codigo')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        $slct->order($order);

        return $this->fetchAll($slct);
    }

    public function buscarUsuariosOrgaosGruposNomes($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array()
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array('s.sis_codigo')
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array('u.usu_identificacao', 'u.usu_nome')
        );
        $slct->joinInner(array('o' => 'Orgaos'), 'ug.uog_orgao = o.org_codigo', array('u.usu_identificacao', 'u.usu_nome', 'u.usu_codigo', 'o.org_sigla', 'o.org_codigo')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        $slct->order($order);
        x($slct->__toString());
        return $this->fetchAll($slct);
    }

    public function buscarUsuariosOrgaosGruposSistemas($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array('g.gru_nome', 'g.gru_codigo')
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array()
        );
        $slct->joinInner(array('o' => 'Orgaos'), 'ug.uog_orgao = o.org_codigo', array()
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        $slct->order($order);

        return $this->fetchAll($slct);
    }

    public function buscarUsuariosOrgaosGruposUnidades($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->distinct();
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array()
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array()
        );
        $slct->joinInner(array('o' => 'Orgaos'), 'ug.uog_orgao = o.org_codigo', array('o.org_sigla', 'o.org_codigo')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        $slct->order($order);

        return $this->fetchAll($slct);
    }

    public function buscarViewUsuariosOrgaoGrupos($where = array(), $orWhere = array())
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(array('uog' => 'vwUsuariosOrgaosGrupos'));
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }
        foreach ($orWhere as $coluna => $valor) {
            $select->orWhere($coluna, $valor);
        }
        $select->order('usu_nome');
        $select->order('org_siglaautorizado');
        return $this->fetchAll($select);
    }

    public function buscarUsuariosOrgaosGruposSigla($where = array(), $order = array(), $tamanho = -1, $inicio = -1, $total = null)
    {
        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array('g.gru_codigo', 'g.gru_nome', 'ug.uog_status', 'ug.uog_orgao')
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array('u.usu_orgao', 'u.usu_identificacao', 'u.usu_nome', 'u.usu_telefone', 'u.usu_status', 'usu_codigo',
                new Zend_Db_Expr('tabelas.dbo.fnEstruturaOrgao(ug.uog_orgao, 0) AS org_siglaautorizado'),
                new Zend_Db_Expr('tabelas.dbo.fnEstruturaOrgao(u.usu_orgao, 0) AS usu_orgaolotacao'))
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        $slct->order($order);
        if (!isset($total)) {
            return $this->fetchAll($slct);
        } else {
            $row = $this->fetchAll($slct);
            return $row->count();
        }
    }

    public function buscarUnidades($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array('')
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array(new Zend_Db_Expr('tabelas.dbo.fnEstruturaOrgao(u.usu_orgao, 0) AS usu_orgaolotacao'), 'u.usu_orgao')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        $slct->order($order);
        return $this->fetchAll($slct);
    }

    public function buscarUnidadesAutorizadas($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array('')
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array(new Zend_Db_Expr('tabelas.dbo.fnEstruturaOrgao(ug.uog_orgao, 0) AS org_siglaautorizado'))
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        $slct->order($order);
        return $this->fetchAll($slct);
    }

    public function buscarPerfil($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array('g.gru_nome', 'g.gru_codigo')
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array()
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        $slct->order($order);

        return $this->fetchAll($slct);
    }

    public function buscarStatus($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $slct = $this->select();
        $slct->distinct();
        $slct->setIntegrityCheck(false);
        $slct->from(array('ug' => $this->_name), array()
        );
        $slct->joinInner(array('g' => 'Grupos'), 'ug.uog_grupo = g.gru_codigo', array()
        );
        $slct->joinInner(array('s' => 'Sistemas'), 'g.gru_sistema = s.sis_codigo', array()
        );
        $slct->joinInner(array('u' => 'Usuarios'), 'ug.uog_usuario = u.usu_codigo', array()
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        $slct->order($order);
        return $this->fetchAll($slct);
    }

    public function salvar($dados, $comando)
    {
        //INSTANCIANDO UM OBJETO DE ACESSO AOS DADOS DA TABELA
        $tmpTblUsuariosOrgaosGrupos = new Usuariosorgaosgrupos();

        if ($comando == 1) {
            $tmpTblUsuariosOrgaosGrupos = $tmpTblUsuariosOrgaosGrupos->createRow();
        } else {
            $tmpTblUsuariosOrgaosGrupos = $this->buscar(
                array('uog_usuario = ?' => $dados['uog_usuario'],
                    'uog_orgao   = ?' => $dados['uog_orgao'],
                    'uog_grupo   = ?' => $dados['uog_grupo']//,
                    //'uog_status  = ?' => $dados['uog_status']
                ))->current();
        }

        //ATRIBUINDO VALORES AOS CAMPOS QUE FORAM PASSADOS
        if (isset($dados['uog_usuario'])) {
            $tmpTblUsuariosOrgaosGrupos->uog_usuario = $dados['uog_usuario'];
        }
        if (isset($dados['uog_orgao'])) {
            $tmpTblUsuariosOrgaosGrupos->uog_orgao = $dados['uog_orgao'];
        }
        if (isset($dados['uog_grupo'])) {
            $tmpTblUsuariosOrgaosGrupos->uog_grupo = $dados['uog_grupo'];
        }
        if (isset($dados['uog_status'])) {
            $tmpTblUsuariosOrgaosGrupos->uog_status = $dados['uog_status'];
        }

        $id = $tmpTblUsuariosOrgaosGrupos->save();

        if (!empty($id)) {
            return $id;
        } else {
            return false;
        }
    }

    public function buscardadosAgentes($idorgao, $idgrupo = 129)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('uog' => 'vwUsuariosOrgaosGrupos'), array(
                'uog.usu_codigo',
                'uog.usu_nome',
                'uog.gru_nome as perfil',
                'uog.gru_codigo'
            )
        );
        $select->joinInner(
            array('ag' => 'Agentes'), 'ag.CNPJCPF = uog.usu_identificacao', array('ag.idAgente')
            , "agentes"
        );
        $select->where('uog.sis_codigo = ?', 21);
        //$select->where('uog.org_superior = ?', $idorgao);
        $select->where('uog.uog_orgao = ?', $idorgao);
        $select->where('uog.gru_codigo = ?', $idgrupo);
        $select->order(array('uog.usu_nome'));
        return $this->fetchAll($select);
    }

    public function buscardadosAgentesArray($where)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('uog' => 'vwUsuariosOrgaosGrupos'), array(
                'uog.usu_codigo',
                'uog.usu_nome',
                'uog.gru_nome as perfil',
                'uog.gru_codigo'
            )
        );
        $select->joinInner(
            array('ag' => 'Agentes'), 'ag.CNPJCPF = uog.usu_identificacao', array('ag.idAgente')
            , "agentes"
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }

        $select->where('uog.uog_status = ?', 1);

        $select->where('uog.sis_codigo = ?', 21);


        $select->order(array('uog.usu_nome'));
        return $this->fetchAll($select);
    }

    public function buscarOrgaoSuperior($idorgao)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('uog' => 'vwUsuariosOrgaosGrupos'), array(
                'uog.org_superior',
            )
        );
        $select->where('uog.uog_orgao = ?', $idorgao);
        return $this->fetchAll($select);
    }

    public function buscarOrgaoSuperiorUnico($idorgao)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('uog' => 'vwUsuariosOrgaosGrupos'),
            array('uog.org_superior')
        );
        $select->where('uog.uog_orgao = ?', $idorgao);
        $select->order('uog_status DESC');
        return $this->fetchRow($select);
    }

    public function obterGruposPorUsuarioEOrgao($usu_codigo, $usu_orgao)
    {
//        select * --grupos.
//    from tabelas.dbo.UsuariosXOrgaosXGrupos associativa
//   inner join tabelas.dbo.Grupos grupos on grupos.gru_codigo = associativa.uog_grupo

        $objQuery = $this->select();
        $objQuery->setIntegrityCheck(false);
        $objQuery->from(
            array('usuariosOrgaoGrupo' => 'UsuariosXOrgaosXGrupos'),
            '',
            $this->_schema
        );
        $objQuery->joinInner(
            array('grupos' => 'Grupos'),
            'grupos.gru_codigo = usuariosOrgaoGrupo.uog_grupo',
            'grupos.*',
            $this->_schema
        );
        $objQuery->where('usuariosOrgaoGrupo.uog_usuario = ?', $usu_codigo);
        $objQuery->where('usuariosOrgaoGrupo.uog_orgao = ?', $usu_orgao);
        $objQuery->where('usuariosOrgaoGrupo.uog_status = ?', 1);
        $objQuery->order('grupos.gru_nome ASC');
        $resultado = $this->fetchAll($objQuery);
        if ($resultado) {
            return $resultado->toArray();
        }
    }


    public function obterTecnicoComMenosProjetosParaAnaliseMesAtual($where = array())
    {
        $subSelect = $this->select()
            ->from(["a" => "tbAvaliacaoProposta"], [new Zend_Db_Expr('count(*)')], $this->getSchema("sac"))
            ->where('a.idTecnico = ug.uog_usuario')
            ->where('"a"."DtAvaliacao" >= ?', new Zend_Db_Expr('date_trunc(\'month\', CURRENT_DATE)'));

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('ug' => $this->_name),
            array(
                'ug.uog_usuario',
                'ug.uog_orgao',
                'ug.uog_grupo',
                'projetosParaAnalise' => new Zend_Db_Expr('('.$subSelect.')')),
            $this->_schema
        );
        $select->joinInner(
            array('g' => 'Grupos'),
            'ug.uog_grupo = g.gru_codigo',
            array('g.gru_nome', 'g.gru_codigo'),
            $this->_schema
        );
        $select->joinInner(
            array('s' => 'Sistemas'),
            'g.gru_sistema = s.sis_codigo',
            array(),
            $this->_schema
        );
        $select->joinInner(
            array('u' => 'Usuarios'),
            'ug.uog_usuario = u.usu_codigo',
            array('u.usu_codigo'),
            $this->_schema
        );
        $select->joinInner(
            array('o' => 'Orgaos'),
            'ug.uog_orgao = o.org_codigo',
            array(),
            $this->_schema
        );

        foreach ($where as $coluna => $valor) {
            $select->where($coluna, $valor);
        }
        $select->order("projetosParaAnalise DESC");
        $select->limit(1);

        return $this->fetchRow($select);
    }
}

