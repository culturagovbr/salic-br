<?php

class Admissibilidade_Model_TbAvaliacaoPropostaMapper extends MinC_Db_Mapper
{
    public function __construct()
    {
        parent::setDbTable('Admissibilidade_Model_DbTable_TbAvaliacaoProposta');
    }

    public function save($model)
    {
        return parent::save($model);
    }
}