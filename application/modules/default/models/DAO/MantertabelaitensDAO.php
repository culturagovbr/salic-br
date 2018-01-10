<?php
/**
 * DAO MantertabelaitensDAO
 * @author Equipe RUP - Politec
 * @since 13/12/2010
 * @version 1.0
 * @package application
 * @subpackage application.model.DAO
 * @copyright � 2010 - Minist�rio da Cultura - Todos os direitos reservados.
 * @link http://www.cultura.gov.br
 */

class MantertabelaitensDAO extends  MinC_Db_Table_Abstract
{
    protected $_name='tbSolicitarItem';
    protected $_schema = 'sac';
    protected $_primary = 'idSolicitarItem';
    /**
     * exibirprodutoetapaitem
     *
     * @param bool $item
     * @param bool $nomeItem
     * @param bool $idEtapa
     * @param bool $idProduto
     * @static
     * @access public
     * @return void
     * @todo mudar todas as chamadas para $this->listarProdutoEtapaItem
     */
    public static function exibirprodutoetapaitem ($item=null, $nomeItem=null, $idEtapa=null, $idProduto=null)
    {
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $sql = "SELECT distinct pr.Codigo as idProduto, pr.Descricao as Produto
 				FROM sac.dbo.tbItensPlanilhaProduto p
				INNER JOIN sac.dbo.Produto pr on (p.idProduto = pr.Codigo)
				INNER JOIN sac.dbo.TbPlanilhaItens i on (p.idPlanilhaItens = i.idPlanilhaItens)
				INNER JOIN sac.dbo.TbPlanilhaEtapa e on (p.idPlanilhaEtapa = e.idPlanilhaEtapa)	";


        if(!empty($nomeItem)){
            $sql .=" AND i.Descricao ".$nomeItem;
        }
        if(!empty($item)){
            $sql .=" WHERE i.idPlanilhaItens = ".$item;
        }
        if(!empty($idEtapa)){
            $sql .=" AND e.idPlanilhaEtapa = ".$idEtapa;
        }
        if(!empty($idProduto)){
            $sql .=" AND pr.Codigo = ".$idProduto;
        }
        $sql .=" ORDER BY pr.Codigo ASC";


        return $db->fetchAll($sql);
    }

    /**
     * listarProdutoEtapaItem
     *
     * @param bool $item
     * @param bool $nomeItem
     * @param bool $idEtapa
     * @param bool $idProduto
     * @static
     * @access public
     * @return void
     */
    public function listarProdutoEtapaItem ($item=null, $nomeItem=null, $idEtapa=null, $idProduto=null, $where=array())
    {

        $sql = $this->select()->distinct()
            ->from(array('p' => 'tbItensPlanilhaProduto'), null, $this->_schema)
            ->join(
                array('pr' => 'Produto')
                ,'p.idProduto = pr.Codigo'
                , array(
                    'pr.Codigo as idProduto'
                    ,'pr.Descricao as Produto'
                    ,'i.Descricao as NomeDoItem'
                )
                , $this->_schema
            )
            ->join(array('i' => 'tbPlanilhaItens'), 'p.idPlanilhaItens = i.idPlanilhaItens', null, $this->_schema)
            ->join(array('e' => 'tbPlanilhaEtapa'), 'p.idPlanilhaEtapa = e.idPlanilhaEtapa', null, $this->_schema)
            ;

        if(!empty($nomeItem)) {
            $sql->where('i.Descricao = ?', $nomeItem);
        }
        if(!empty($item)) {
            $sql->where('i.idPlanilhaItens = ?', $item);
        }
        if(!empty($idEtapa)) {
            $sql->where('e.idPlanilhaEtapa = ?', $idEtapa);
        }
        if(!empty($idProduto)) {
            $sql->where('pr.Codigo = ?', $idProduto);
        }

        if(!empty($where)){
            foreach ($where as $coluna => $valor) {
                $sql->where($coluna, $valor);
            }
        }

        $sql->order('pr.Codigo ASC');
//xd($sql->assemble());
        return $this->fetchAll($sql);
    }


    public function exibirEtapa($idProduto) {

        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $sql = $db->select()->distinct();
        $sql->from(array('p' => 'tbitensplanilhaproduto'), null, $this->_schema
        );
        $sql->joinInner(array('pr'=>'produto'), 'p.idproduto = pr.codigo', null, $this->_schema);

        $sql->joinInner(array('i'=>'tbplanilhaitens'), 'p.idplanilhaitens = i.idplanilhaitens', null, $this->_schema);

        $sql->joinInner(array('e'=>'tbplanilhaetapa'), 'p.idplanilhaetapa = e.idplanilhaetapa',  array('e.idplanilhaetapa as idEtapa', 'e.Descricao as Etapa'), $this->_schema);

        $sql->where('p.idproduto = ?', $idProduto);
        $sql->order('Etapa ASC');

        return $db->fetchAll($sql);
    }

    public function exibirItem($idProduto, $idEtapa) {

        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $sql = $db->select();
        $sql->from( array('p' => 'tbitensplanilhaproduto'), null, $this->_schema
        );
        $sql->joinInner(array('pr'=>'produto'), 'p.idproduto = pr.codigo', null, $this->_schema);

        $sql->joinInner(array('i'=>'tbplanilhaitens'), 'p.idplanilhaitens = i.idplanilhaitens',
                        array('idItem' => 'i.idplanilhaitens',
                             'NomeDoItem' => 'i.Descricao'), $this->_schema
                        );
        $sql->joinInner(array('e'=>'tbplanilhaetapa'), 'p.idplanilhaetapa = e.idplanilhaetapa', null, $this->_schema);

        $sql->where('p.idproduto = ? ', $idProduto);
        $sql->where('e.idplanilhaetapa = ?', $idEtapa);

        $sql->order('NomeDoItem  ASC');

        return $db->fetchAll($sql);
    }

    public static function buscaprodutoetapaitem($item=null,$nomeItem=null) {
        $sql = "SELECT pr.Codigo as idProduto,
                       p.idPlanilhaItens,
                       e.idPlanilhaEtapa as idEtapa,
                       pr.Descricao as Produto,
                       e.Descricao as Etapa,
                       i.Descricao as NomeDoItem
                FROM sac.dbo.tbItensPlanilhaProduto p
		INNER JOIN sac.dbo.Produto pr on (p.idProduto = pr.Codigo)
		INNER JOIN sac.dbo.TbPlanilhaItens i on (p.idPlanilhaItens = i.idPlanilhaItens)
		INNER JOIN sac.dbo.TbPlanilhaEtapa e on (p.idPlanilhaEtapa = e.idPlanilhaEtapa)";
        if(!empty($item)){
            $sql .=" WHERE i.idPlanilhaItens = ".$item;
        }
        if(!empty($nomeItem)){
            $sql .=" AND i.Descricao ".$nomeItem;
        }
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchRow($sql);
    }

    public function produtoEtapaItem ($item=null, $nomeItem=null)
    {

        $sql = $this->select()
            ->from(array('p' => 'tbItensPlanilhaProduto'), 'p.idPlanilhaItens', $this->_schema)
            ->join(array('pr' => 'Produto'), 'p.idProduto = pr.Codigo', array('pr.Codigo as idProduto','pr.Descricao as Produto'), $this->_schema)
            ->join(array('i' => 'tbPlanilhaItens'), 'p.idPlanilhaItens = i.idPlanilhaItens', array('i.Descricao as NomeDoItem'), $this->_schema)
            ->join(array('e' => 'tbPlanilhaEtapa'), 'p.idPlanilhaEtapa = e.idPlanilhaEtapa', array('e.idPlanilhaEtapa as idEtapa', 'e.Descricao as Etapa'), $this->_schema)
            ;

        if(!empty($item)){
            //$sql .=" WHERE i.idPlanilhaItens = ".$item;
            $sql->where("i.idPlanilhaItens = ? ", $item);
        }
        if(!empty($nomeItem)){
            //$sql .=" AND i.Descricao ".$nomeItem;
            $sql->where("i.Descricao = ? ", $nomeItem);
        }

        return $this->fetchRow($sql);
    }

    public static function buscaproduto($where=null) {
        $sql = "SELECT Codigo as codproduto, Descricao as Produto
				FROM sac.dbo.Produto WHERE stEstado = 0 ORDER BY Produto "; //WHERE stEstado = 0
        if(!empty($where)){
            $sql .=" AND i.Descricao ".$where;
        }
        
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($sql);
    } // fecha m�todo buscaprodutoetapaitem()

    /**
     * buscaProduto
     *
     * @param bool $where
     * @access public
     * @return void
     */
    public function listarProduto($where=null)
    {

        $sql = $this->select()
            ->from(array('Produto'), array('Codigo as codproduto', 'Descricao as Produto'), $this->_schema)
            ->where('stEstado = ?', true)
            ->order('Produto')
            ;

        return $this->fetchAll($sql);
    }

    public static function buscaetapa() {
        $sql = "SELECT idPlanilhaEtapa as codetapa, Descricao as Etapa
				FROM sac.dbo.TbPlanilhaEtapa";
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($sql);
    } // fecha m�todo buscaprodutoetapaitem()

    /**
     * listarEtapa
     *
     * @access public
     * @return void
     */
    public function listarEtapa()
    {

        $sql = $this->select()
            ->from(array('tbPlanilhaEtapa'), array('idPlanilhaEtapa as codetapa', 'Descricao as Etapa'), $this->_schema)
            ;

        return $this->fetchAll($sql);
    }

    public static function buscaitem() {
        $sql = "Select idPlanilhaItens as coditens, Descricao as Item, idUsuario from sac.dbo.tbPlanilhaItens
		 order by Descricao";
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($sql);
    } // fecha m�todo buscaprodutoetapaitem()

    /**
     * listarItem
     *
     * @access public
     * @return void
     */
    public function listarItem() {

        $sql = $this->select()
            ->from('tbPlanilhaItens', array('idPlanilhaItens as coditens', 'Descricao as Item', 'idUsuario'), $this->_schema)
            ->order('Descricao');

        return $this->fetchAll($sql);
    }

    /**
     * solicitacoes
     *
     * @param mixed $idAgente
     * @static
     * @access public
     * @return void
     * $todo migrar metodo para $this->solicitacao
     */
    public function solicitacoes($idagente) {

        $select = $this->select();
        $select->setintegritycheck(false);
        $select->from(
            array('sol' => $this->_name),
            array(
                "prod.Codigo as idproduto",
                "prod.Descricao as produto",
                "et.idPlanilhaEtapa",
                "et.Descricao as etapa",
                "sol.idSolicitarItem",
                new zend_db_expr('(case when "sol"."idPlanilhaItens" > 0 then "it"."Descricao" 
                                           else "sol"."NomeDoItem" end) as itemsolicitado'),
                "sol.Descricao as justificativa",
                new zend_db_expr( '(case "sol"."stEstado" when 0 then \'solicitado\' 
                                           when 1 then \'atendido\' 
                                           else \'negado\' end) as estado'),
                "Resposta"
            ),
            $this->_schema
        );

        $select->joininner(
            array('prod'=>'Produto'), 'sol.idProduto = prod.Codigo',
            null,
            $this->_schema
        );

        $select->joininner(
            array('et'=>'tbPlanilhaEtapa'), 'sol.idEtapa = et.idPlanilhaEtapa',
            null,
            $this->_schema
        );

        $select->joinleft(
            array('it' => 'tbPlanilhaItens'), 'sol.idPlanilhaItens = it.idPlanilhaItens',
            null,
            $this->_schema
        );
        $select->where('sol.idAgente = '.$idagente);
        $select->order('sol.idSolicitarItem');


        return $this->fetchAll($select);
    }

    /**
     * solicitacao
     *
     * @param mixed $idAgente
     * @access public
     * @return void
     */
    public function solicitacao($idAgente)
    {
        $col = array(
            'prod.Codigo as idproduto',
            'prod.Descricao as produto',
            'et.idPlanilhaEtapa',
            'et.Descricao as etapa',
            'sol.idSolicitarItem',
            new Zend_Db_Expr('CASE
                WHEN sol."idPlanilhaItens" > 0 THEN it."Descricao"
                ELSE sol."NomeDoItem"
            END as itemsolicitado'),
            'sol.Descricao as justificativa',
            new Zend_Db_Expr('CASE sol."stEstado"
                WHEN 0 THEN \'Solicitado\'
                WHEN 1 THEN \'Atendido\'
                ELSE \'Negado\'
            END as estado'),
            new Zend_Db_Expr('"Resposta"')
        );

        $sql = $this->select()
            ->from(array('sol' => 'tbSolicitarItem'), $col, $this->_schema)
            ->join(array('prod' => 'Produto'), 'sol.idProduto = prod.Codigo', null, $this->_schema)
            ->join(array('et' => 'tbPlanilhaEtapa'), 'sol.idEtapa = et.idPlanilhaEtapa', null, $this->_schema)
            ->joinLeft(array('it' => 'tbPlanilhaItens'),  'sol.idPlanilhaItens = it.idPlanilhaItens', null, $this->_schema)
            ->where('sol.idAgente = ?', $idAgente)
            ->order('sol.idSolicitarItem')
            ;
//xd($sql->assemble());
        return $this->fetchAll($sql);
    }

    public function cadastraritem($dadosassociar) {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);


        $cadastrar = $this->insert($dadosassociar);

        if ($cadastrar) {
            return true;
        }
        else {
            return false;
        }

       /* $resultado = $db->query($sql);
        return $resultado;*/
    } // fecha m�todo cadastraritem()

    /**
     * cadastrarItemObj
     *
     * @param mixed $dadosassociar
     * @access public
     * @return void
     */
    public function cadastrarItemObj($dadosassociar)
    {

        $cadastrar = $this->insert($dadosassociar);

        if ($cadastrar) {
            return true;
        }
        else {
            return false;
        }
    }

    public static function buscarItem($idAgente) {
        $sql = "SELECT TOP 1 idPlanilhaItens FROM sac.dbo.tbPlanilhaItens where idUsuario = ".$idAgente." order by idPlanilhaItens desc";
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_ASSOC);
        return $db->fetchRow($sql);
    } // fecha m�todo buscarItem()

    public static function associaritem($dadosassociar)
    {
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $cadastrar = $db->insert("tbsolicitaritem", $dadosassociar);

        if ($cadastrar) {
            return true;
        }
        else {
            return false;
        }
    }

    public function associarItemObj($dadosassociar)
    {
        $cadastrar = $this->insert($dadosassociar);

        if ($cadastrar) {
            return true;
        }
        else {
            return false;
        }
    }

    public static function buscarSolicitacoes($where=array(),$nomeItem=null) {


         $sql = "SELECT prod.Codigo as idProduto,
                        prod.Descricao as Produto,
			et.idPlanilhaEtapa,
                        et.Descricao as Etapa,
			sol.idSolicitarItem,
			       CASE
			            WHEN  sol.IdPlanilhaItens > 0 THEN it.Descricao
			            ELSE sol.NomeDoItem
			       END as ItemSolicitado,
			       sol.Descricao as Justificativa,
			       CASE sol.stEstado
			            WHEN 0 THEN 'Solicitado'
			            WHEN 1 THEN 'Atendido'
			            ELSE 'Negado'
			       END as Estado,Resposta
			 FROM sac.dbo.tbSolicitarItem sol
			      INNER JOIN sac.dbo.Produto prod ON sol.idProduto = prod.Codigo
			      INNER JOIN sac.dbo.tbPlanilhaEtapa et ON sol.idEtapa = et.idPlanilhaEtapa
			      LEFT JOIN sac.dbo.TbPlanilhaItens it ON sol.idPlanilhaItens = it.idPlanilhaItens";

        $ct=1;
        foreach ($where as $coluna=>$valor)
        {
            if($ct==1)
                $sql .= " WHERE ".$coluna." = '".$valor."'";
            else
                $sql .= " AND ".$coluna." = '".$valor."'";
            $ct++;
        }
        if(!empty($nomeItem)){
            $sql .="AND (sol.NomeDoItem = '{$nomeItem}' OR it.Descricao = '{$nomeItem}')";
        }
        $sql .= " ORDER BY sol.idSolicitarItem";

        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($sql);


    }

    public function listarSolicitacoes($where=array(), $nomeItem=null)
    {

        $cols = array(
            'prod.Codigo as idproduto',
            'prod.Descricao as produto',
            'et.idPlanilhaEtapa',
            'et.Descricao as etapa',
            'sol.idSolicitarItem',
            new Zend_Db_Expr('CASE
                WHEN  "sol"."idPlanilhaItens" > 0 THEN "it"."Descricao"
                ELSE "sol"."NomeDoItem"
            END as itemsolicitado'),
            'sol.Descricao as Justificativa',
            new Zend_Db_Expr('CASE sol."stEstado"
                WHEN 0 THEN \'Solicitado\'
                WHEN 1 THEN \'Atendido\'
                ELSE \'Negado\'
            END as estado'),
            new Zend_Db_Expr('"Resposta"')
        );

        $sql = $this->select()
            ->from(array('sol' => 'tbSolicitarItem'), $cols, $this->_schema)
            ->join(array('prod' => 'Produto'), 'sol.idProduto = prod.Codigo', null,$this->_schema)
            ->join(array('et' => 'tbPlanilhaEtapa'), 'sol.idEtapa = et.idPlanilhaEtapa', null, $this->_schema)
            ->joinLeft(array('it' => 'tbPlanilhaItens'), 'sol.idPlanilhaItens = it.idPlanilhaItens', null,$this->_schema);
        foreach ($where as $coluna=>$valor)
        {
            $sql->where($coluna .' = ?', $valor);
        }

        if(!empty($nomeItem)){
            $sql->where('sol.NomeDoItem = ?', $nomeItem);
            $sql->orWhere('"it"."Descricao" = ?', $nomeItem);
        }
        $sql->order('sol.idSolicitarItem');

//xd($sql->assemble());
        return $this->fetchAll($sql);
    }
}
