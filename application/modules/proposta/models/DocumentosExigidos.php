<?php

class Proposta_Model_DocumentosExigidos extends MinC_Db_Model
{
    protected $_Codigo;
    protected $_Descricao;
    protected $_Area;
    protected $_Opcao;
    protected $_stEstado;
    protected $_stUpload;

    const RESULTADO_DA_SELECAO_PUBLICA = 248;
    const CONTRATO_FIRMADO_COM_INCENTIVADOR = 162;

    /**
     * @return mixed
     */
    public function getCodigo()
    {
        return $this->_Codigo;
    }

    /**
     * @param mixed $Codigo
     */
    public function setCodigo($Codigo)
    {
        $this->_Codigo = $Codigo;
    }

    /**
     * @return mixed
     */
    public function getDescricao()
    {
        return $this->_Descricao;
    }

    /**
     * @param mixed $Descricao
     */
    public function setDescricao($Descricao)
    {
        $this->_Descricao = $Descricao;
    }

    /**
     * @return mixed
     */
    public function getArea()
    {
        return $this->_Area;
    }

    /**
     * @param mixed $Area
     */
    public function setArea($Area)
    {
        $this->_Area = $Area;
    }

    /**
     * @return mixed
     */
    public function getOpcao()
    {
        return $this->_Opcao;
    }

    /**
     * @param mixed $Opcao
     */
    public function setOpcao($Opcao)
    {
        $this->_Opcao = $Opcao;
    }

    /**
     * @return mixed
     */
    public function getStEstado()
    {
        return $this->_stEstado;
    }

    /**
     * @param mixed $stEstado
     */
    public function setStEstado($stEstado)
    {
        $this->_stEstado = $stEstado;
    }

    /**
     * @return mixed
     */
    public function getStUpload()
    {
        return $this->_stUpload;
    }

    /**
     * @param mixed $stUpload
     */
    public function setStUpload($stUpload)
    {
        $this->_stUpload = $stUpload;
    }



}