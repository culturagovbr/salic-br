<?php
class PlanilhaItem   extends MinC_Db_Table_Abstract
{
    protected $_banco = "SAC";
    protected $_schema = "SAC";
    protected $_name = "tbPlanilhaItens";

    /**
     * @return Zend_Db_Table_Rowset_Abstract
     * @todo Ver com Romulo sobre a integridade da Planilha Item / Produto
     */
    public function pesquisar($item)
    {
        
        /*$select = "SELECT
                    pa.*,
                    (
                        SELECT sum(b1.vlComprovacao) AS vlPagamento
                        FROM bdcorporativo.scsac.tbComprovantePagamentoxPlanilhaAprovacao AS a1
                        INNER JOIN bdcorporativo.scsac.tbComprovantePagamento AS b1 ON a1.idComprovantePagamento = b1.idComprovantePagamento
                        INNER JOIN sac.dbo.tbPlanilhaAprovacao AS c1 ON a1.idPlanilhaAprovacao = c1.idPlanilhaAprovacao
                        WHERE c1.stAtivo = 'S' AND c1.idPlanilhaAprovacao = pa.idPlanilhaAprovacao AND c1.idPronac = pa.idPronac
                        GROUP BY a1.idPlanilhaAprovacao
                    ) as valorComprovado,
                    ROUND((pa.QtItem * pa.nrOcorrencia * pa.VlUnitario),2) as valorAprovado
                FROM sac.dbo.tbPlanilhaAprovacao AS pa
                    INNER JOIN sac.dbo.tbPlanilhaItens as pit ON pit.idPlanilhaItens = pa.idPlanilhaItem
                    INNER JOIN sac.dbo.tbPlanilhaEtapa AS pEtapa ON pEtapa.idPlanilhaEtapa = pa.idEtapa
                    LEFT JOIN sac.dbo.Produto AS prod ON prod.Codigo = pa.idProduto
                WHERE
                    pa.idPlanilhaAprovacao = ?
                    AND pa.stAtivo = 'S'
                ORDER BY prod.Descricao ASC";*/

        $select = "SELECT
                    pa.*,
                    (
                        SELECT sum(b1.vlComprovacao) AS vlPagamento
                        FROM bdcorporativo.scsac.tbComprovantePagamentoxPlanilhaAprovacao AS a1
                        INNER JOIN bdcorporativo.scsac.tbComprovantePagamento AS b1 ON a1.idComprovantePagamento = b1.idComprovantePagamento
                        INNER JOIN sac.dbo.tbPlanilhaAprovacao AS c1 ON a1.idPlanilhaAprovacao = c1.idPlanilhaAprovacao
                        WHERE c1.idPlanilhaItem = pa.idPlanilhaItem
                            AND c1.nrFonteRecurso = pa.nrFonteRecurso
                            AND c1.idProduto = pa.idProduto
                            AND c1.idEtapa = pa.idEtapa
                            AND c1.idUFDespesa = pa.idUFDespesa
                            AND c1.idMunicipioDespesa = pa.idMunicipioDespesa
                            AND c1.idPronac = pa.idPronac
                        GROUP BY c1.idPlanilhaItem
                    ) AS vlComprovado,

                    ROUND((pa.QtItem * pa.nrOcorrencia * pa.VlUnitario),2) as valorAprovado
                FROM sac.dbo.tbPlanilhaAprovacao AS pa
                    INNER JOIN sac.dbo.tbPlanilhaItens as pit ON pit.idPlanilhaItens = pa.idPlanilhaItem
                    INNER JOIN sac.dbo.tbPlanilhaEtapa AS pEtapa ON pEtapa.idPlanilhaEtapa = pa.idEtapa
                    LEFT JOIN sac.dbo.Produto AS prod ON prod.Codigo = pa.idProduto
                WHERE
                    pa.idPlanilhaAprovacao = ?
                    AND pa.stAtivo = 'S'
                    AND pa.nrFonteRecurso = 109 -- BATIZADO: Incentivo Fiscal Federal
                ORDER BY prod.Descricao ASC";

        

        $statement = $this->getAdapter()->query($select, array($item));



        return $statement->fetchObject();
    }
}
