<?php

/**
 * @todo: duplicado, definir qual arquivo eh o correto (Proposta_Model_DbTable_DocumentosExigidos)
 */
class DocumentosExigidos extends MinC_Db_Table_Abstract
{

    protected $_name = 'DocumentosExigidos';
    protected $_schema = 'sac';
    protected $_primary = 'Codigo';

    function listarDocumentosExigido($idCodigoDocumentosExigidos = '')
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array($this->_name),
            array(
                'Codigo',
                'Descricao',
                'Area',
                'Opcao',
                'stEstado'
            )
        );
        $select->where('Opcao in ?', new Zend_Db_Expr('(1,2)'));
        if ($idCodigoDocumentosExigidos)
            $select->where('Codigo = ?', $idCodigoDocumentosExigidos);


        return $this->fetchAll($select);
    }

    function listaDocumentosObrigatoriosPF()
    {

    }

    function listaDocumentosObrigatoriosPJ()
    {

    }
}