<?php

class Admissibilidade_Model_TbAvaliacaoProposta extends MinC_Db_Model
{
    protected $_idAvaliacaoProposta;
    protected $_idProjeto;
    protected $_idTecnico;
    protected $_DtEnvio;
    protected $_DtAvaliacao;
    protected $_Avaliacao;
    protected $_ConformidadeOK;
    protected $_stEstado;
    protected $_dsResposta;
    protected $_dtResposta;
    protected $_idArquivo;
    protected $_idCodigoDocumentosExigidos;
    protected $_stEnviado;
    protected $_stProrrogacao;

    /**
     * @return mixed
     */
    public function getIdAvaliacaoProposta()
    {
        return $this->_idAvaliacaoProposta;
    }

    /**
     * @param mixed $idAvaliacaoProposta
     */
    public function setIdAvaliacaoProposta($idAvaliacaoProposta)
    {
        $this->_idAvaliacaoProposta = $idAvaliacaoProposta;
    }

    /**
     * @return mixed
     */
    public function getIdProjeto()
    {
        return $this->_idProjeto;
    }

    /**
     * @param mixed $idProjeto
     */
    public function setIdProjeto($idProjeto)
    {
        $this->_idProjeto = $idProjeto;
    }

    /**
     * @return mixed
     */
    public function getIdTecnico()
    {
        return $this->_idTecnico;
    }

    /**
     * @param mixed $idTecnico
     */
    public function setIdTecnico($idTecnico)
    {
        $this->_idTecnico = $idTecnico;
    }

    /**
     * @return mixed
     */
    public function getDtEnvio()
    {
        return $this->_DtEnvio;
    }

    /**
     * @param mixed $DtEnvio
     */
    public function setDtEnvio($DtEnvio)
    {
        $this->_DtEnvio = $DtEnvio;
    }

    /**
     * @return mixed
     */
    public function getDtAvaliacao()
    {
        return $this->_DtAvaliacao;
    }

    /**
     * @param mixed $DtAvaliacao
     */
    public function setDtAvaliacao($DtAvaliacao)
    {
        $this->_DtAvaliacao = $DtAvaliacao;
    }

    /**
     * @return mixed
     */
    public function getAvaliacao()
    {
        return $this->_Avaliacao;
    }

    /**
     * @param mixed $Avaliacao
     */
    public function setAvaliacao($Avaliacao)
    {
        $this->_Avaliacao = $Avaliacao;
    }

    /**
     * @return mixed
     */
    public function getConformidadeOK()
    {
        return $this->_ConformidadeOK;
    }

    /**
     * @param mixed $ConformidadeOK
     */
    public function setConformidadeOK($ConformidadeOK)
    {
        $this->_ConformidadeOK = $ConformidadeOK;
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
    public function getDsResposta()
    {
        return $this->_dsResposta;
    }

    /**
     * @param mixed $dsResposta
     */
    public function setDsResposta($dsResposta)
    {
        $this->_dsResposta = $dsResposta;
    }

    /**
     * @return mixed
     */
    public function getDtResposta()
    {
        return $this->_dtResposta;
    }

    /**
     * @param mixed $dtResposta
     */
    public function setDtResposta($dtResposta)
    {
        $this->_dtResposta = $dtResposta;
    }

    /**
     * @return mixed
     */
    public function getIdArquivo()
    {
        return $this->_idArquivo;
    }

    /**
     * @param mixed $idArquivo
     */
    public function setIdArquivo($idArquivo)
    {
        $this->_idArquivo = $idArquivo;
    }

    /**
     * @return mixed
     */
    public function getIdCodigoDocumentosExigidos()
    {
        return $this->_idCodigoDocumentosExigidos;
    }

    /**
     * @param mixed $idCodigoDocumentosExigidos
     */
    public function setIdCodigoDocumentosExigidos($idCodigoDocumentosExigidos)
    {
        $this->_idCodigoDocumentosExigidos = $idCodigoDocumentosExigidos;
    }

    /**
     * @return mixed
     */
    public function getStEnviado()
    {
        return $this->_stEnviado;
    }

    /**
     * @param mixed $stEnviado
     */
    public function setStEnviado($stEnviado)
    {
        $this->_stEnviado = $stEnviado;
    }

    /**
     * @return mixed
     */
    public function getStProrrogacao()
    {
        return $this->_stProrrogacao;
    }

    /**
     * @param mixed $stProrrogacao
     */
    public function setStProrrogacao($stProrrogacao)
    {
        $this->_stProrrogacao = $stProrrogacao;
    }


}
