<?php
/**
 * DAO Recurso
 * @author Equipe RUP - Politec
 * @since 27/07/2010
 * @version 1.0
 * @package application
 * @subpackage application.model.DAO
 * @copyright � 2010 - Minist�rio da Cultura - Todos os direitos reservados.
 * @link http://www.cultura.gov.br
 */

class ManterpropostaeditalDAO extends Zend_Db_Table
{
	/* dados da tabela */
	protected $_schema  = "";
	protected $_name    = "sac.dbo.PreProjeto";
	protected $_primary = "idPreProjeto";



	
//	public static function cadastrar($dados)
//	{
//		$db= Zend_Db_Table::getDefaultAdapter();
//		$db->setFetchMode(Zend_DB::FETCH_OBJ);
//
//		$cadastrar = $db->insert("sac.dbo.tbRecurso", $dados);
//
//
//		if ($cadastrar)
//		{
//			return true;
//		}
//		else
//		{
//			return false;
//		}
//	} // fecha m�todo cadastrar()



	
//	public static function alterar($dados, $idPronac)
//	{
//		$db= Zend_Db_Table::getDefaultAdapter();
//		$db->setFetchMode(Zend_DB::FETCH_OBJ);
//
//		$where   = "IdPRONAC = $idPronac";
//		$alterar = $db->update("sac.dbo.Projetos", $dados, $where);
//
//		if ($alterar)
//		{
//			return true;
//		}
//		else
//		{
//			return false;
//		}
//	} // fecha m�todo alterar()



	public static function buscaredital($array) {
            $sql = "Select p.idPreProjeto,
                           nm.Descricao   as Proponente,
                           p.idAgente     as idAgente,
                           p.NomeProjeto,
                           p.Mecanismo,
                           stTipoDemanda,
                           p.idEdital,
                           AG.CNPJCPF,
                           fd.nmFormDocumento as Edital,
                           Mec.Descricao as MecanismoDesc
                           --edi.Objeto
                      From sac.dbo.PreProjeto p
                 left join bdcorporativo.scQuiz.tbFormDocumento fd on fd.idEdital = p.idEdital OR  p.idEdital IS NULL
                INNER JOIN agentes.dbo.Agentes ag on ag.idAgente = p.idAgente
                INNER JOIN agentes.dbo.Nomes nm on nm.idAgente = ag.idAgente
                INNER JOIN sac.dbo.Edital ed on ed.idEdital = p.idEdital
                INNER JOIN sac.dbo.Mecanismo Mec on Mec.Codigo = p.Mecanismo
                Where p.stestado = 1
                and p.idUsuario= '{$array['idUsuario']}'
                and stTipoDemanda not like 'NA'
                and not exists (select * from sac.dbo.projetos pr where p.idPreProjeto = pr.idProjeto )
                AND fd.idClassificaDocumento not in (23,24,25)";

            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
            return $db->fetchAll($sql);
	} // fecha m�todo buscaredital()
	
	
		public static function buscarpreprojeto()
	{
		$sql = "select pp.NomeProjeto, nm.Descricao as Nome from sac.dbo.PreProjeto pp
                INNER JOIN agentes.dbo.Nomes nm on nm.idAgente = pp.idAgente
                where pp.idPreProjeto = '23546'";

		$db= Zend_Db_Table::getDefaultAdapter();
		$db->setFetchMode(Zend_DB::FETCH_OBJ);
		return $db->fetchAll($sql);
	} // fecha m�todo buscarpreprojeto()


		public static function buscaendereco($cpf)
	{
		$sql = "select Ag.idAgente, Ag.CNPJCPF, en.TipoEndereco,  en.Divulgar, en.TipoLogradouro, en.TipoEndereco, nm.Descricao as Nome, mun.Descricao as Municipio, uf.Descricao as UF, en.Numero, en.Bairro, en.Cep, en.Complemento, en.Logradouro, en.Numero, uf.Descricao as UF 
                from agentes.dboEnderecoNacional en
                INNER JOIN agentes.dboAgentes Ag on Ag.idAgente = en.idAgente
                INNER JOIN agentes.dbo.Nomes nm on nm.idAgente = Ag.idAgente
                INNER JOIN agentes.dbo.UF uf on uf.idUF = en.UF
                LEFT JOIN agentes.dbo.Municipios mun on mun.idMunicipioIBGE = en.Cidade
                where Ag.CNPJCPF= '" . $cpf . "'";
		

		$db= Zend_Db_Table::getDefaultAdapter();
		$db->setFetchMode(Zend_DB::FETCH_OBJ);
		return $db->fetchAll($sql);
	}
        
    public static function buscaEditalConfirmarAvancada($array = array()) {
        $sql = " SELECT e.idEdital as idEditalTb,
                        convert(varchar(12),e.NrEdital)as NrEditalTb,
                        convert(char(4),YEAR(e.DtEdital)) as Ano,
                        tabelas.dbo.fnEstruturaOrgao(e.idOrgao,1) as Unidade ,
                        f.nmFormDocumento as nmDocumento,
                        e.DtEdital as DtEditalTb,v.Descricao as TipoFundo,
                        u.dtIniFase as dtIniFase,c.dsClassificaDocumento as Classificacao,
                        u.dtFimFase as dtFimFase,Objeto
                   FROM Sac.dbo.Edital e
             INNER JOIN bdcorporativo.scQuiz.tbFormDocumento f ON (e.idEdital = f.idEdital)
             INNER JOIN sac.dbo.Verificacao v on (e.cdTipoFundo = v.idVerificacao)
             INNER JOIN bdcorporativo.scsac.tbEditalXtbFaseEdital u ON (u.idEdital = e.idEdital)
             INNER JOIN bdcorporativo.scsac.tbClassificaDocumento c ON (f.idClassificaDocumento = c.idClassificaDocumento)
                  WHERE u.idFaseEdital = '2' AND
                        f.stModalidadeDocumento is not null";
        if($array['nrEdital']) {
            $sql .= " and e.NrEdital = '{$array['nrEdital']}'";
        }
        if($array['dtEditalInicial'] && $array['dtEditalFinal']) {
            $sql .= " and e.DtEdital BETWEEN '{$array['dtEditalInicial']}' and '{$array['dtEditalFinal']}'";
        }
        if($array['dtInicoInscricaoInicial'] && $array['dtInicoInscricaoFinal']) {
            $sql .= " and u.dtIniFase BETWEEN '{$array['dtInicoInscricaoInicial']}' and '{$array['dtInicoInscricaoFinal']}'";
        }
        if($array['dtFinalInscricaoInicial'] && $array['dtFinalInscricaoFinal']) {
            $sql .= " and u.dtFimFase BETWEEN '{$array['dtFinalInscricaoInicial']}' and '{$array['dtFinalInscricaoFinal']}'";
        }
        if($array['nmEdital']) {
            $sql .= " and Objeto like '%{$array['nmEdital']}%'
                       or nmDocumento like '%{$array['nmEdital']}%'";
        }
        $sql .= " ORDER BY c.dsClassificaDocumento,v.Descricao,tabelas.dbo.fnEstruturaOrgao(e.idOrgao,1),e.NrEdital";
        $db  = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function buscaEditalConfirmar($array = array()) {

        $objAcesso= new Acesso();

        $sql = " SELECT e.idEdital as idEditalTb,
                        convert(varchar(12),e.NrEdital)as NrEditalTb,
                        convert(char(4),YEAR(e.DtEdital)) as Ano,
                        tabelas.dbo.fnEstruturaOrgao(e.idOrgao,1) as Unidade ,
                        f.nmFormDocumento as nmDocumento,
                        e.DtEdital as DtEditalTb,v.Descricao as TipoFundo,
                        u.dtIniFase as dtIniFase,c.dsClassificaDocumento as Classificacao,
                        u.dtFimFase as dtFimFase,Objeto
                   FROM Sac.dbo.Edital e
             INNER JOIN bdcorporativo.scQuiz.tbFormDocumento f ON (e.idEdital = f.idEdital)
             INNER JOIN sac.dbo.Verificacao v on (e.cdTipoFundo = v.idVerificacao)
             INNER JOIN bdcorporativo.scsac.tbEditalXtbFaseEdital u ON (u.idEdital = e.idEdital)
             INNER JOIN bdcorporativo.scsac.tbClassificaDocumento c ON (f.idClassificaDocumento = c.idClassificaDocumento)
                  WHERE u.idFaseEdital = '2' AND
                        f.stModalidadeDocumento is not null and
                        u.dtIniFase <= {$objAcesso->getDate()} AND u.dtFimFase >= {$objAcesso->getDate()}
               ORDER BY c.dsClassificaDocumento,v.Descricao,tabelas.dbo.fnEstruturaOrgao(e.idOrgao,1),e.NrEdital";

        $db  = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function buscaEditalConfirmarLocalizar($array = array()) {
        $sql = " SELECT e.idEdital as idEditalTb,
                        e.Objeto,
                        convert(varchar(12),e.NrEdital)as NrEditalTb,
                        convert(char(4),YEAR(e.DtEdital)) as Ano,
                        tabelas.dbo.fnEstruturaOrgao(e.idOrgao,1) as Unidade ,
                        f.nmFormDocumento as nmDocumento,
                        e.DtEdital as DtEditalTb,v.Descricao as TipoFundo,
                        u.dtIniFase as dtIniFase,c.dsClassificaDocumento as Classificacao,
                        u.dtFimFase as dtFimFase,Objeto
                   FROM Sac.dbo.Edital e
             INNER JOIN bdcorporativo.scQuiz.tbFormDocumento f ON (e.idEdital = f.idEdital)
             INNER JOIN sac.dbo.Verificacao v on (e.cdTipoFundo = v.idVerificacao)
             INNER JOIN bdcorporativo.scsac.tbEditalXtbFaseEdital u ON (u.idEdital = e.idEdital)
             INNER JOIN bdcorporativo.scsac.tbClassificaDocumento c ON (f.idClassificaDocumento = c.idClassificaDocumento)
                  WHERE u.idFaseEdital = '2' AND
                        f.stModalidadeDocumento is not null ";
        if(isset($array['idEdital'])) {
            $sql .= " and e.idEdital = {$array['idEdital']}";
        }
        $sql .= " ORDER BY c.dsClassificaDocumento,v.Descricao,tabelas.dbo.fnEstruturaOrgao(e.idOrgao,1),e.NrEdital";
        $db  = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function listarEditalResumo($array = array()) {
        $objAcesso= new Acesso();
        $sql = " SELECT f.nmFormDocumento,
                        count(f.nmFormDocumento) as qtd
                   FROM Sac.dbo.Edital e
             INNER JOIN bdcorporativo.scQuiz.tbFormDocumento f ON (e.idEdital = f.idEdital)
             INNER JOIN sac.dbo.Verificacao v on (e.cdTipoFundo = v.idVerificacao)
             INNER JOIN bdcorporativo.scsac.tbEditalXtbFaseEdital u ON (u.idEdital = e.idEdital)
             INNER JOIN bdcorporativo.scsac.tbClassificaDocumento c ON (f.idClassificaDocumento = c.idClassificaDocumento)
                  WHERE u.idFaseEdital = '2' AND
                        f.stModalidadeDocumento is not null and
                        u.dtIniFase <= {$objAcesso->getDate()} AND u.dtFimFase >= {$objAcesso->getDate()}
               group by f.nmFormDocumento";
        $db  = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function exibirDadosPropostaEditalCompleto($array = array()) {
        $sql = "  Select p.idPreProjeto,
                        p.idAgente,
                        NomeProjeto,
                        Mecanismo,
                        stTipoDemanda,
                        p.idEdital,
                        p.ResumoDoProjeto,
                        nm.Descricao as nomeAgente
                    From sac.dbo.PreProjeto p
               left join bdcorporativo.scQuiz.tbFormDocumento fd on fd.idEdital = p.idEdital OR  p.idEdital IS NULL
               left join agentes.dbo.Nomes nm on nm.idAgente = p.idAgente
                   Where stestado=1
                     and stTipoDemanda not like 'NA'
                     and not exists (select * from sac.dbo.projetos pr where p.idPreProjeto = pr.idProjeto )
                     AND fd.idClassificaDocumento not in (23,24,25)
                     and idPreProjeto = {$array['idPreProjeto']}
                GROUP BY NomeProjeto, Mecanismo, p.idAgente, p.idEdital, idPreProjeto, stTipoDemanda, p.ResumoDoProjeto,nm.Descricao";

        $db  = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function buscarNomeAgente($array = array()) {
        $sql = "select Descricao
                  from agentes.dbo.Nomes
                 where idAgente = {$array['idAgente']} --39318";
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function inserirProposta($array = array()) {
        $db= Zend_Db_Table::getDefaultAdapter();
    	$db->insert('sac.dbo.PreProjeto', $array);
    	return $db->lastInsertId();
    }

    public static function buscarDadosProposta($array = array()) {
        $sql = "select idPreProjeto
                  from sac.dbo.PreProjeto
                 where idAgente          = {$array['idAgente']}
                   and NomeProjeto       = '{$array['nomeProjeto']}'
                   and stTipoDemanda     = 'ED'
                   and p.idEdital        = {$array['idEdital']}
                   and p.ResumoDoProjeto = '{$array['resumoProjeto']}'";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }
    public static function alterarDadosProposta($array = array()) {
        $sql = "update sac.dbo.PreProjeto
                   set NomeProjeto     = '{$array['NomeProjeto']}',
                       ResumoDoProjeto = '{$array['ResumoDoProjeto']}'
                 where idPreProjeto    = {$array['idPreProjeto']}
                   and idAgente        = {$array['idAgente']}
                   and idEdital        = {$array['idEdital']}";
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function buscarPropostaEdital($idAgente){
        $sql = "Select  
                    p.idPreProjeto,
                    idagente,
                    NomeProjeto,
                    Mecanismo,
                    stTipoDemanda,
                    p.idEdital
                From sac.dbo.PreProjeto p
                left join bdcorporativo.scQuiz.tbFormDocumento fd on fd.idEdital = p.idEdital OR  p.idEdital IS NULL
                Where stestado=1
                and idAgente={$idAgente }
                and stTipoDemanda not like 'NA'
                and not exists (select * from sac.dbo.projetos pr where p.idPreProjeto = pr.idProjeto )AND
                fd.idClassificaDocumento not in (23,24,25)
                GROUP BY NomeProjeto, Mecanismo, idagente, p.idEdital, idPreProjeto, stTipoDemanda";

        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);

    }
}