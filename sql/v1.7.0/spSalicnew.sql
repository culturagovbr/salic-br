-- =========================================================================================
-- Autor: Rômulo Menhô Barbosa
-- Data de Criação: 25/03/2002
-- Descrição: Montar tabelas para agilizar consultas (SALICNET / SALICNEW)
-- Data de Alteração: 28/04/2015
-- Motivo: Inclusão de novas tabelas 
-- =========================================================================================

IF OBJECT_ID ( 'dbo.spSalicnew', 'P' ) IS NOT NULL 
    DROP PROCEDURE dbo.spSalicnew;
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET NOCOUNT ON
GO
CREATE PROCEDURE dbo.spSalicnew 
AS  

-- =========================================================================================
-- APAGAR TABELA
-- =========================================================================================
DELETE FROM Intranet 
-- =========================================================================================
-- Proponentes 
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,CampoA,CampoB,ValorA,ValorB,ValorC,ValorD )
     SELECT 1,P.CgcCpf,I.Nome,Mecanismo,dbo.fnProponenteVlSolicitado(p.CgcCpf),
            dbo.fnProponenteVlAprovado(p.CgcCpf),
            dbo.fnTotalCaptadoProponente(p.CgcCpf),
            dbo.fnProponenteVlAprovado(p.CgcCpf)-dbo.fnTotalCaptadoProponente(p.CgcCpf)
            FROM Projetos p 
            INNER JOIN Interessado i on (p.CgcCpf = i.CgcCpf)
            INNER JOIN Segmento s    on (p.Segmento = s.Codigo)
            INNER JOIN Area a        on (p.Area = a.Codigo)
            GROUP BY P.CgcCpf,I.Nome,Mecanismo
            HAVING dbo.fnProponenteVlSolicitado(p.CgcCpf)>0
-- =========================================================================================
-- Captação de recursos, da renúncia, apoio privado por ano	     	   		  
-- =========================================================================================
DECLARE @Ano int
SET @Ano = 1993
WHILE @Ano <= year(getdate())
  BEGIN
    INSERT INTO Intranet (Tipo,ChaveA,ValorA,ValorB,ValorC )
         SELECT 2,@Ano,dbo.fnCaptadoAnoMec(@Ano),
                dbo.fnRenunciaAnoMec(@Ano),
                dbo.fnCaptadoAnoMec(@Ano)-dbo.fnRenunciaAnoMec(@Ano)
     SET @Ano = @Ano + 1
  END
-- =========================================================================================
-- Comparativo por ano
-- =========================================================================================
  --DECLARE @Ano int
SET @Ano = 1993
WHILE @Ano <= year(getdate())
  BEGIN
    INSERT INTO Intranet (Tipo,ChaveA,QtdeA,ValorA,QtdeB,ValorB,QtdeC,ValorC )
         SELECT 3,@Ano,
                dbo.fnQtdeProjetoApresentado(@Ano),
                dbo.fnTotalSolicitadoAno(@Ano),
                dbo.fnQtdeProjetoAprovado(@Ano),
                dbo.fnTotalAprovadoAno(@Ano),
                dbo.fnQtdeProjetoCaptou(@Ano),
                dbo.fnTotalCustoAno(@Ano)
    SET @Ano = @Ano + 1
  END
-- =========================================================================================
-- Comparativo por ano - Mecenanto
-- =========================================================================================
  --DECLARE @Ano int
SET @Ano = 1993
WHILE @Ano <= year(getdate())
  BEGIN
    INSERT INTO Intranet (Tipo,ChaveA,QtdeA,ValorA,QtdeB,ValorB,QtdeC,ValorC )
         SELECT 4,@Ano,
                dbo.fnQtdeProjetoApresentadoMec(@Ano),
                dbo.fnTotalSolicitadoAnoMec(@Ano),
                dbo.fnQtdeProjetoAprovadoMec(@Ano),
                dbo.fnTotalAprovadoAnoMec(@Ano),
                dbo.fnQtdeProjetoCaptouMec(@Ano),
                dbo.fnTotalCaptadoAnoMec(@Ano)
    SET @Ano = @Ano + 1
  END
-- =========================================================================================
-- Catalogo de projeto do Mecenanto por ano, região, uf, área e segmento cultural
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,ChaveC,ChaveD,ChaveE,CampoA,CampoB,CampoC, ValorA)
   SELECT 5,AnoRecibo,u.Regiao,UFProjeto,Area,Segmento,p.AnoProjeto+p.Sequencial,NomeProjeto,Nome,sum(CaptacaoReal)
          FROM Projetos p 
          INNER JOIN vCaptadoAnoProjeto c on (p.AnoProjeto = c.AnoProjeto and p.Sequencial  = c.Sequencial)
          INNER JOIN Interessado i on (p.CgcCpf = i.CgcCpf)
          INNER JOIN UF u on (p.UFProjeto = u.UF)
          GROUP BY AnoRecibo,u.Regiao,UFProjeto,Area,Segmento,p.AnoProjeto,p.Sequencial,NomeProjeto,Nome
-- =========================================================================================
-- Cem maiores incentivadores por UF
-- =========================================================================================
--DECLARE @Ano int
DECLARE @UF char(2)
DECLARE TheCursor CURSOR FOR
  SELECT UF FROM UF
  OPEN TheCursor        
  WHILE @@FETCH_STATUS = @@FETCH_STATUS
    BEGIN
      FETCH NEXT FROM TheCursor into @UF               
      IF @@FETCH_STATUS = -2
         CONTINUE
      IF @@FETCH_STATUS = -1
         BREAK
      SET @Ano = 1993
      WHILE @Ano <= year(getdate())
        BEGIN
          INSERT INTO Intranet 
                         (Tipo,ChaveA,QtdeA,ChaveB,CampoA,ValorA)
                   SELECT TOP 100 6,UF,DatePart(yy,DtRecibo),c.CgcCpfMecena,Nome,
                          sum(CaptacaoReal)
                     FROM Captacao C
                     INNER JOIN Interessado I on (c.CgcCpfMecena = i.CgcCpf)    
	             GROUP BY UF,DatePart(yy,DtRecibo),c.CgcCpfMecena,Nome
                     HAVING DatePart(yy,DtRecibo)=@Ano AND UF=@UF
                     ORDER BY sum(CaptacaoReal) desc
          SET @Ano = @Ano + 1
        END
   END             
   CLOSE TheCursor
   DEALLOCATE TheCursor
-- =========================================================================================
-- TEMPO MÉDIO PARA APROVAÇÃO DE PROJETOS (TIPO - 7)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA,QtdeB,QtdeC,QtdeD,QtdeE)
   SELECT 7,YEAR(a.DtProtocolo) as Ano ,c.Descricao as Area,
          AVG(DATEDIFF(d,sac.dbo.fnDtEnvioVinculada (a.idPronac),sac.dbo.fnDtRetornoMinC (a.idPronac))) as qtVinculada,
          AVG(DATEDIFF(d,sac.dbo.fnDtRetornoMinC (a.idPronac),b.DtAprovacao)) as qtSefic,
          AVG(DATEDIFF(d,a.DtProtocolo,b.DtAprovacao)) as qtAprovar,
          AVG(DATEDIFF(d,b.DtAprovacao,b.DtPublicacaoAprovacao)) as qtPublicar,
          AVG(DATEDIFF(d,a.DtProtocolo,b.DtPublicacaoAprovacao)) as qtDiasTotal 
          FROM Projetos a
          INNER JOIN Aprovacao b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
          INNER JOIN Area c      on (a.Area = c.Codigo)
          WHERE a.Mecanismo = '1'
                AND b.TipoAprovacao='1' 
	            AND b.DtPublicacaoAprovacao is not null 
	            AND year(a.DtProtocolo)>2008
          GROUP BY YEAR(a.DtProtocolo),c.Descricao
          ORDER BY YEAR(a.DtProtocolo),c.Descricao 

-- =========================================================================================
--PROPOSTAS CADASTRADAS E NÃO ENVIADAS AO MINC (TIPO 8)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA)
	SELECT 8,year(p.dtAceite) as Ano,u.Regiao, COUNT(*) AS QTDE
	FROM SAC.DBO.PreProjeto p
	INNER JOIN Agentes.DBO.EnderecoNacional e on (p.idAgente = e.idAgente)
	INNER JOIN Agentes.DBO.UF u on (e.UF = u.idUF)
	WHERE e.Status = 1 
		  AND Mecanismo = 1 
		  AND YEAR(p.dtAceite) is not null 
		  --AND YEAR(p.dtAceite) >= 2010
		  AND NOT EXISTS(SELECT TOP 1 * FROM SAC.DBO.tbMovimentacao m WHERE movimentacao=96 
		  AND m.idprojeto = p.idpreprojeto)
	GROUP BY YEAR(p.dtAceite),u.Regiao
	ORDER BY YEAR(p.dtAceite),u.Regiao
-- =========================================================================================
--PROPOSTAS CADASTRADAS E ENVIADAS AO MINC (TIPO 9)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA)
	SELECT 9,YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)) as Ano,u.Regiao, COUNT(*) AS QTDE
	FROM SAC.DBO.PreProjeto p
	INNER JOIN Agentes.DBO.EnderecoNacional e on (p.idAgente = e.idAgente)
	INNER JOIN Agentes.DBO.UF u on (e.UF = u.idUF)
	WHERE e.Status = 1 
		  AND Mecanismo = 1 
		  AND YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)) is not null
	GROUP BY YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)),u.Regiao
	ORDER BY YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)),u.Regiao
-- =========================================================================================
--PROPOSTAS TRANSFORMADAS EM PROJETOS (TIPO 10)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA)
	SELECT 10,YEAR(pr.DtProtocolo) as Ano,u.Regiao,COUNT(*) AS QTDE
	FROM SAC.DBO.PreProjeto p
	INNER JOIN SAC.DBO.Projetos pr on (p.idPreProjeto = pr.idProjeto)
	INNER JOIN SAC.DBO.UF u on (pr.UFProjeto = u.UF)
	WHERE pr.Mecanismo = 1 
	GROUP BY YEAR(pr.DtProtocolo),u.Regiao
	ORDER BY YEAR(pr.DtProtocolo),u.Regiao
-- =========================================================================================
--PROPOSTAS TRANSFORMADAS EM PROJETOS E APROVADAS (TIPO 11)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA)
	SELECT 11,YEAR(p.DtProtocolo) as Ano,u.Regiao,COUNT(*) AS QTDE
	FROM SAC.DBO.Projetos p
	INNER JOIN SAC.DBO.PreProjeto x on (p.idProjeto = x.idPreProjeto)
	INNER JOIN Agentes.DBO.UF u on (p.UfProjeto = u.Sigla)
	WHERE p.Mecanismo = '1' 
		  AND EXISTS(SELECT TOP 1 * FROM Aprovacao pr WHERE p.IdPRONAC = pr.IdPRONAC)
	GROUP BY YEAR(p.DtProtocolo),u.Regiao 
	ORDER BY YEAR(p.DtProtocolo),u.Regiao 	
-- =========================================================================================
--PROPOSTAS ENVIADAS AO MINC POR ANO E MÊS (TIPO 12)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA)
	SELECT 12,YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)) as Ano,MONTH(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)) as Mes, COUNT(*) AS QTDE
	FROM SAC.DBO.PreProjeto p
	INNER JOIN Agentes.DBO.EnderecoNacional e on (p.idAgente = e.idAgente)
	INNER JOIN Agentes.DBO.UF u on (e.UF = u.idUF)
	WHERE e.Status = 1 
		  AND Mecanismo = 1  
		  AND YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)) is not null
	GROUP BY YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)),MONTH(dbo.fnDtEnvioAvaliacao(p.idpreprojeto))
	ORDER BY YEAR(dbo.fnDtEnvioAvaliacao(p.idpreprojeto)),MONTH(dbo.fnDtEnvioAvaliacao(p.idpreprojeto))
-- =========================================================================================
--PROJETOS AVALIADOS NA CNIC POR ANO (TIPO 13)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA)
	SELECT 13,YEAR(dtEnvioPauta)as Ano,c.Regiao,COUNT(*) AS QTDE
	FROM SAC.DBO.Projetos a
	INNER JOIN SAC.DBO.PreProjeto b on (a.idProjeto = b.idPreProjeto) 
	INNER JOIN Agentes.DBO.UF c on (a.UfProjeto = c.Sigla)
	INNER JOIN BDCORPORATIVO.SCSAC.tbPauta d on (a.idPronac = d.idPronac)
	WHERE a.Mecanismo = '1'
	GROUP BY YEAR(dtEnvioPauta) ,c.Regiao 
	ORDER BY YEAR(dtEnvioPauta) ,c.Regiao 
-- =========================================================================================
--PROJETOS AVALIADOS NA CNIC POR ANO  E ÁREA(TIPO 14)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA,QtdeB)
	SELECT 14,YEAR(d.dtEnvioPauta)as Ano,c.Descricao as Area,COUNT(*) AS QTDE, COUNT(e.AnoProjeto) AS QTDELIBERADO
	FROM SAC.DBO.Projetos a
	INNER JOIN SAC.DBO.PreProjeto b on (a.idProjeto = b.idPreProjeto) 
	INNER JOIN SAC.DBO.Area c on (a.Area = c.Codigo)
	INNER JOIN BDCORPORATIVO.SCSAC.tbPauta d on (a.idPronac = d.idPronac)
	LEFT JOIN SAC.DBO.Liberacao e on (a.AnoProjeto = e.AnoProjeto and a.Sequencial = e.Sequencial)
	WHERE a.Mecanismo = '1'
	      --AND DATEDIFF(YEAR,dtEnvioPauta,(GETDATE())) <= 3
	GROUP BY YEAR(d.dtEnvioPauta) ,c.Descricao 
	ORDER BY YEAR(d.dtEnvioPauta) ,c.Descricao 
-- =========================================================================================
--PROJETOS EM EXECUÇÃO - CONTA LIBERADA (TIPO 15)
-- =========================================================================================
INSERT INTO Intranet 
      (Tipo,
       QtdeA,
       ChaveA,
       CampoA,
       ChaveB,
       ChaveC,
       ChaveD,
       ChaveE,
       CampoC,
       CampoD,
       CampoE)
SELECT 15,
       a.IdPRONAC,
       a.AnoProjeto+a.Sequencial,
       a.NomeProjeto,
       c.Regiao,
       c.Descricao,
       d.Descricao,
       a.Situacao,
       CONVERT(CHAR(10),a.dtInicioExecucao,103),
       CONVERT(CHAR(10),a.DtFimExecucao,103),
       CONVERT(CHAR(10),sac.dbo.fnFimCaptacao(a.AnoProjeto,a.Sequencial),103)
 FROM Projetos a
INNER JOIN Liberacao b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
INNER JOIN sac.dbo.Uf c on (a.UfProjeto = c.Uf)
INNER JOIN sac.dbo.Area d on (a.Area = d.codigo)
WHERE --CONVERT(CHAR(8), GETDATE(),112) <= CONVERT(CHAR(8), sac.dbo.fnFimCaptacao(a.AnoProjeto,a.Sequencial),112)
      a.Mecanismo = '1'
      AND a.Situacao  in ('B11','B14','C08','C10','C13','C16','C21','C22','C26','C30','D02','D03','D16','D17','D20','D22','D23',
	                      'D28','D29','D32','D33','D34','D35','E10','E11','E12','E13','E15','E16','E50','E59','E60','E61','E71')
	  AND a.Orgao NOT IN (167,270)
	  ORDER BY a.NomeProjeto	  
-- =========================================================================================
--PROJETOS COM CAPTAÇÃO ABAIXO DE 20% E SEM LIBERAÇÃO DE CONTA (TIPO 16)
-- =========================================================================================
INSERT INTO Intranet 
      (Tipo,
       QtdeA,
       ChaveA,
       CampoA,
       ChaveB,
       ChaveC,
       ChaveD,
       ChaveE,
       CampoB,
       CampoC,
       CampoD,
       CampoE)
SELECT 16,
       a.IdPRONAC,
       a.AnoProjeto+a.Sequencial,
       a.NomeProjeto,
       c.Regiao,
       c.Descricao,
       d.Descricao,
       a.Situacao,
       e.Descricao,
       CONVERT(CHAR(10),a.dtInicioExecucao,103),
       CONVERT(CHAR(10),a.DtFimExecucao,103),
       CONVERT(CHAR(10),sac.dbo.fnFimCaptacao(a.AnoProjeto,a.Sequencial),103)
FROM Projetos a
INNER JOIN sac.dbo.Uf c on (a.UfProjeto = c.Uf)
INNER JOIN sac.dbo.Area d on (a.Area = d.codigo)
INNER JOIN sac.dbo.Situacao e on (a.Situacao = e.codigo)
WHERE --CONVERT(CHAR(8), GETDATE(),112) <= CONVERT(CHAR(8), dbo.fnFimCaptacao(a.AnoProjeto,a.Sequencial),112)
      a.Mecanismo = '1'
      AND a.Situacao  in ('B11','B14','C08','C10','C13','C16','C21','C22','C26','C30','D02','D03','D16','D17','D20','D22','D23',
	                      'D28','D29','D32','D33','D34','D35','E10','E11','E12','E13','E15','E16','E50','E59','E60','E61','E71')
	  AND sac.dbo.fnPercentualCaptado(a.AnoProjeto,a.Sequencial) > 0 AND sac.dbo.fnPercentualCaptado(a.AnoProjeto,a.Sequencial) < 20
	  AND NOT EXISTS(SELECT TOP 1 * FROM  sac.dbo.Liberacao d WHERE a.AnoProjeto = d.AnoProjeto and a.Sequencial = d.Sequencial) 
	  AND a.Orgao NOT IN (167,270)
	  ORDER BY a.NomeProjeto	  
-- =========================================================================================
--COMPARATIVO - (TIPO 17)
-- =========================================================================================
INSERT INTO Intranet 
      (Tipo,
       ChaveA,
       QtdeA,
       QtdeB,
       QtdeC,
       QtdeD)
	SELECT 17,YEAR(pr.DtProtocolo) as Ano,
	       (SELECT COUNT(*) AS QTDE
	      	       FROM SAC.DBO.PreProjeto a1
	               INNER JOIN SAC.DBO.Projetos b1 on (a1.idPreProjeto = b1.idProjeto)
	               INNER JOIN SAC.DBO.UF c1 on (b1.UFProjeto = c1.UF)
	               WHERE a1.Mecanismo = 1 AND a1.idPreProjeto = b1.idProjeto and YEAR(b1.DtProtocolo) = YEAR(pr.DtProtocolo)
	               ) as PropostasTransformadas,
	               
	       (SELECT COUNT(*) AS QTDE
	               FROM SAC.DBO.Projetos a2
	               INNER JOIN SAC.DBO.PreProjeto b2 on (a2.idProjeto = b2.idPreProjeto)
	               INNER JOIN Agentes.DBO.UF c2 on (a2.UfProjeto = c2.Sigla)
	               WHERE a2.Mecanismo = '1' AND b2.idPreProjeto = a2.idProjeto and YEAR(a2.DtProtocolo) = YEAR(pr.DtProtocolo)
		                 AND EXISTS(SELECT TOP 1 * FROM Aprovacao d2 WHERE a2.IdPRONAC = d2.IdPRONAC)
	               GROUP BY YEAR(a2.DtProtocolo)) as ProjetosAprovados,
	       (SELECT COUNT(*) AS QTDE
	               FROM SAC.DBO.Projetos a3
	               INNER JOIN SAC.DBO.PreProjeto b3 on (a3.idProjeto = b3.idPreProjeto)
	               INNER JOIN Agentes.DBO.UF c3 on (a3.UfProjeto = c3.Sigla)
	               WHERE a3.Mecanismo = '1' AND b3.idPreProjeto = a3.idProjeto and YEAR(a3.DtProtocolo) = YEAR(pr.DtProtocolo)
		                 AND  NOT EXISTS(SELECT TOP 1 * FROM Aprovacao d3 WHERE a3.IdPRONAC = d3.IdPRONAC)
	               GROUP BY YEAR(a3.DtProtocolo)) as ProjetosNaoAprovados,
	       (SELECT COUNT(*) AS QTDE
                   FROM Projetos a4
                   INNER JOIN SAC.DBO.Liberacao b4 on (a4.AnoProjeto = b4.AnoProjeto and a4.Sequencial = b4.Sequencial)
                   INNER JOIN SAC.DBO.Uf c4 on (a4.UfProjeto = c4.Uf)
                   WHERE CONVERT(CHAR(8), GETDATE(),112) < CONVERT(CHAR(8), sac.dbo.fnFimCaptacao(a4.AnoProjeto,a4.Sequencial),112)
                         AND a4.Mecanismo = '1' AND a4.AnoProjeto = b4.AnoProjeto and a4.Sequencial = b4.Sequencial 
                         AND YEAR(a4.DtProtocolo) = YEAR(pr.DtProtocolo)
	                     AND a4.situacao  in ('B11','B14','C08','C10','C13','C16','C21','C22','C26','C30','D02','D03','D16','D17','D20','D22','D23',
	                      'D28','D29','D32','D33','D34','D35','E10','E11','E12','E13','E15','E16','E50','E59','E60','E61','E71')) AS ProjetoEmExecucao              
	FROM SAC.DBO.PreProjeto p
	INNER JOIN Agentes.DBO.EnderecoNacional e on (p.idAgente = e.idAgente)
	INNER JOIN Agentes.DBO.UF u on (e.UF = u.idUF)
	INNER JOIN SAC.DBO.Projetos pr on (p.idPreProjeto = pr.idProjeto)
	WHERE e.Status = 1 
		  AND pr.Mecanismo = 1 
	GROUP BY YEAR(pr.DtProtocolo)
	ORDER BY YEAR(pr.DtProtocolo)
-- =========================================================================================
--COMPARATIVO DE CAPTAÇÃO DE RECURSOS  POR ANO, REGIÃO / MUNICIPIOS (RIO E SÃO PAULO) - (TIPO 18)
-- =========================================================================================

INSERT INTO Intranet (Tipo,ChaveA,ChaveB,ValorA)
	SELECT 18,
       YEAR(a.DtRecibo),
	   CASE
	     WHEN d.Descricao = 'Rio de Janeiro'
	       THEN 'CIDADE RJ'
	     WHEN d.Descricao = 'São Paulo'
	       THEN 'CIDADE SP'
	       ELSE  d.Regiao
	   END, sum(a.CaptacaoReal) 
       FROM sac.dbo.Captacao a
       INNER JOIN sac.dbo.Projetos b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
       INNER JOIN sac.dbo.Interessado c on (b.CgcCpf = c.CgcCpf)
	   INNER JOIN sac.dbo.UF d on (c.UF = d.UF)
       WHERE c.Cidade IN ('Rio de Janeiro', 'São Paulo')
       GROUP BY YEAR(a.DtRecibo),d.Regiao,d.Descricao
     UNION ALL
	SELECT 18,
       YEAR(a.DtRecibo),d.Regiao, sum(a.CaptacaoReal) 
       FROM sac.dbo.Captacao a
       INNER JOIN sac.dbo.Projetos b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
       INNER JOIN sac.dbo.Interessado c on (b.CgcCpf = c.CgcCpf)
	   INNER JOIN sac.dbo.UF d on (c.UF = d.UF)
       WHERE c.Cidade NOT IN ('Rio de Janeiro', 'São Paulo')
       GROUP BY YEAR(a.DtRecibo),d.Regiao,d.Descricao
       ORDER BY 1,2

-- =========================================================================================
--COMPARATIVO DE PROPOSTAS TRANSFORMADAS E PROJETOS E APROVADOS POR REGIÃO (TIPO 19)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,QtdeA,QtdeB,QtdeC)
	SELECT 19,YEAR(a.DtProtocolo) as Ano,b.Regiao,
	       (SELECT COUNT(*) FROM SAC.DBO.Projetos c
	                        INNER JOIN SAC.DBO.UF d on (c.UFProjeto = d.UF)
	                        WHERE c.Mecanismo = 1 and b.Regiao = d.Regiao and YEAR(c.DtProtocolo)= YEAR(Getdate())
	                        GROUP BY YEAR(c.DtProtocolo),d.Regiao) AS qtProjetosTransformadas,
	       (SELECT COUNT(*) FROM SAC.DBO.Projetos e
	                        INNER JOIN SAC.DBO.UF f on (e.UFProjeto = f.UF)
	                        WHERE e.Mecanismo = 1 AND b.Regiao = f.Regiao AND YEAR(e.DtProtocolo)= YEAR(Getdate())
	                              AND EXISTS(SELECT TOP 1 * FROM Aprovacao pr WHERE e.IdPRONAC = pr.IdPRONAC)
	                        GROUP BY YEAR(e.DtProtocolo),f.Regiao) AS qtProjetosAprovados,
	       ((SELECT COUNT(*) FROM SAC.DBO.Projetos c
	                        INNER JOIN SAC.DBO.UF d on (c.UFProjeto = d.UF)
	                        WHERE c.Mecanismo = 1 and b.Regiao = d.Regiao and YEAR(c.DtProtocolo)= YEAR(Getdate())
	                        GROUP BY YEAR(c.DtProtocolo),d.Regiao) 
	       - (SELECT COUNT(*) FROM SAC.DBO.Projetos e
	                        INNER JOIN SAC.DBO.UF f on (e.UFProjeto = f.UF)
	                        WHERE e.Mecanismo = 1 AND b.Regiao = f.Regiao AND YEAR(e.DtProtocolo)= YEAR(Getdate())
	                              AND EXISTS(SELECT TOP 1 * FROM Aprovacao pr WHERE e.IdPRONAC = pr.IdPRONAC)
	                        GROUP BY YEAR(e.DtProtocolo),f.Regiao)) AS qtResultado                      
	FROM SAC.DBO.Projetos a
	INNER JOIN SAC.DBO.UF b on (a.UFProjeto = b.UF)
	WHERE a.Mecanismo = 1  and YEAR(a.DtProtocolo)= YEAR(Getdate())
	GROUP BY YEAR(a.DtProtocolo),b.Regiao
	ORDER BY YEAR(a.DtProtocolo),b.Regiao
-- =========================================================================================
-- MAILING DO PROPONENTE (TIPO 20)
-- =========================================================================================
INSERT INTO Intranet (Tipo,CampoA,ChaveA,ChaveB,CampoB,CampoC)
    SELECT 20, 
           (SELECT TOP 1 n.Descricao FROM Agentes.dbo.Nomes n WHERE n.idAgente = a.idAgente) as Proponente,
           (SELECT Top 1 c.Regiao    FROM Agentes.dbo.EnderecoNacional b
                                     INNER JOIN Agentes.dbo.uf c on (b.UF = c.idUF)
                                     WHERE b.idAgente = a.idAgente) as Regiao,
           (SELECT Top 1 c.Descricao FROM Agentes.dbo.EnderecoNacional b
                                     INNER JOIN Agentes.dbo.uf c on (b.UF = c.idUF)
                                     WHERE b.idAgente = a.idAgente) as UF,
           (SELECT Top 1 m.Descricao FROM Agentes.dbo.EnderecoNacional b
                                     INNER JOIN Agentes.dbo.Municipios m on (b.UF = m.idUFIBGE and b.Cidade = idMunicipioIBGE)
                                     WHERE b.idAgente = a.idAgente) as Cidade,
           a.Descricao as Email
           FROM  Agentes.dbo.Internet a
           INNER JOIN Agentes.dbo.Visao v on (a.idAgente = v.idAgente)
           WHERE TipoInternet in (28,29) and v.Visao = 144 

-- =========================================================================================
-- PROJETOS EM VIGÊNCIA SEM PEDIDO DE PRORROGAÇÃO PARA O EXERCÍCIO SEGUINTE (TIPO 21)
-- =========================================================================================
INSERT INTO Intranet (Tipo,
                      QtdeA,
                      ChaveA,
                      ChaveB,
                      CampoA,
                      ChaveC,
                      ChaveD,
                      ChaveE,
                      CampoB,
                      ValorA,
                      ValorB,
                      ValorC)
    SELECT 21,
           d.idPronac,
           d.AnoProjeto+d.Sequencial,
           d.Area,
           d.NomeProjeto,
           CONVERT(CHAR(10),d.dtInicioExecucao,103) ,
           CONVERT(CHAR(10),d.dtFimExecucao,103),
           CONVERT(CHAR(10),sac.dbo.fnInicioCaptacao(d.AnoProjeto,d.Sequencial),103),
           CONVERT(CHAR(10),sac.dbo.fnFimCaptacao(d.AnoProjeto,d.Sequencial),103),
           sac.dbo.fnTotalAprovadoProjeto(d.AnoProjeto,d.Sequencial) as vlAprovado,
           sac.dbo.fnTotalCaptadoProjeto(d.AnoProjeto,d.Sequencial) as vlCaptado,
           sac.dbo.fnPercentualCaptado(d.AnoProjeto,d.Sequencial) as PercentualCaptacao
           FROM Projetos d 
           WHERE d.Mecanismo = 1
                 AND d.Situacao  in ('B11','B14','C08','C10','C13','C16','C21','C22','C26','C30','D02','D03','D16','D17','D20','D22','D23',
	                                 'D28','D29','D32','D33','D34','D35','E10','E11','E12','E13','E15','E16','E50','E59','E60','E61','E71')
	             AND CONVERT(CHAR(8), GETDATE(),112) <= CONVERT(CHAR(8), dbo.fnFimCaptacao(d.AnoProjeto,d.Sequencial),112)                
                 AND NOT EXISTS(SELECT TOP 1 * FROM Prorrogacao c 
                                         WHERE d.AnoProjeto = c.AnoProjeto and d.Sequencial = c.Sequencial AND  
                                               CONVERT(CHAR(8),Dtinicio,112) = CONVERT(CHAR(4),(YEAR(GETDATE()) + 1)) + '0101')
           ORDER BY d.NomeProjeto
-- =========================================================================================
--PROJETOS EM VIGÊNCIA SEM CAPTAÇÃO DE RECURSOS (TIPO 22)
-- =========================================================================================
INSERT INTO Intranet 
      (Tipo,
       QtdeA,
       ChaveA,
       CampoA,
       ChaveB,
       ChaveC,
       ChaveD,
       ChaveE,
       CampoC,
       CampoD,
       CampoE)
SELECT 22,
       a.IdPRONAC,
       a.AnoProjeto+a.Sequencial,
       a.NomeProjeto,
       c.Regiao,
       c.Descricao,
       d.Descricao,
       a.Situacao,
       CONVERT(CHAR(10),a.dtInicioExecucao,103),
       CONVERT(CHAR(10),a.DtFimExecucao,103),
       CONVERT(CHAR(10),sac.dbo.fnFimCaptacao(a.AnoProjeto,a.Sequencial),103)
 FROM Projetos a
INNER JOIN sac.dbo.Uf c on (a.UfProjeto = c.Uf)
INNER JOIN sac.dbo.Area d on (a.Area = d.codigo)
WHERE --CONVERT(CHAR(8), GETDATE(),112) > CONVERT(CHAR(8), sac.dbo.fnFimCaptacao(a.AnoProjeto,a.Sequencial),112)
      a.Mecanismo = '1'
      AND a.Situacao  in ('B11','B14','C08','C10','C13','C16','C21','C22','C26','C30','D02','D03','D16','D17','D20','D22','D23',
	                      'D28','D29','D32','D33','D34','D35','E10','E11','E12','E13','E15','E50','E59','E60','E61','E71')
      AND sac.dbo.fnPercentualCaptado(a.AnoProjeto,a.Sequencial) = 0
      AND a.Orgao NOT IN (167,270)
	  ORDER BY a.NomeProjeto	  
-- =========================================================================================
-- VISÕES DAS PESSOAS  (TIPO 23)
-- =========================================================================================
INSERT INTO Intranet 
      (Tipo,
       CampoA,
       CampoB,
       ChaveA,
       ChaveB,
       CampoC)
    SELECT 23, d.Descricao,
           (SELECT TOP 1 n.Descricao FROM Agentes.dbo.Nomes n WHERE n.idAgente = v.idAgente) as Nome,
           (SELECT Top 1 c.Regiao    FROM Agentes.dbo.EnderecoNacional b
                                     INNER JOIN Agentes.dbo.uf c on (b.UF = c.idUF)
                                     WHERE b.idAgente = v.idAgente) as Regiao,
           (SELECT Top 1 c.Descricao FROM Agentes.dbo.EnderecoNacional b
                                     INNER JOIN Agentes.dbo.uf c on (b.UF = c.idUF)
                                     WHERE b.idAgente = v.idAgente) as UF,
           (SELECT Top 1 m.Descricao FROM Agentes.dbo.EnderecoNacional b
                                     INNER JOIN Agentes.dbo.Municipios m on (b.UF = m.idUFIBGE and b.Cidade = idMunicipioIBGE)
                                     WHERE b.idAgente = v.idAgente) as Cidade
           FROM  Agentes.dbo.Visao v 
		   INNER JOIN agentes.dbo.Verificacao d on (v.Visao = d.idVerificacao)
           WHERE v.Visao in (144,145,197,198,199,209,210,212,217,247,248)
UNION ALL      
	SELECT 23,'Responsável por Proposta',Nome,'','',''
		   FROM controledeacesso.dbo.SgcAcesso a 
		   WHERE NOT EXISTS(SELECT * FROM Agentes.dbo.Agentes b WHERE a.CPF = b.CNPJCPF)
		   ORDER BY 2,3
-- ================================================================================================
-- COMPARATIVO POR ANO ENTRE A CAPTAÇÃO DE RECRUSOS E A RENÚNCIA FISCAL EFETIVA POR ANO (TIPO - 24)
-- ================================================================================================
--DECLARE @Ano int
SET @Ano = 2006
WHILE @Ano <= year(getdate())
  BEGIN
    INSERT INTO Intranet (Tipo,ChaveA,ValorA,ValorB,ValorC,ValorD,ValorE)
         SELECT 24,@Ano,
                dbo.fnTotalCaptadoAnoMec(@Ano),
				CASE
				  WHEN @Ano = 2006
				    THEN 362849884
				  WHEN @Ano = 2007
				    THEN 661259201
				  WHEN @Ano = 2008
				    THEN 857285802
				  WHEN @Ano = 2009
				    THEN 1038067355
				  WHEN @Ano = 2010
				    THEN 1319281822
				  WHEN @Ano = 2011
				    THEN 1328587944
				  WHEN @Ano = 2012
				    THEN 1642593297
				  WHEN @Ano = 2013
				    THEN 1241345372
				  WHEN @Ano = 2014
				    THEN 1419224443
				  WHEN @Ano = 2015
				    THEN 1323390560
			    END,
				CASE
				  WHEN @Ano = 2006
				    THEN 767076230
				  WHEN @Ano = 2007
				    THEN 951443422
				  WHEN @Ano = 2008
				    THEN 833998030
				  WHEN @Ano = 2009
				    THEN 621701305
				  WHEN @Ano = 2010
				    THEN 954102784
				  WHEN @Ano = 2011
				    THEN 1117608261
				  WHEN @Ano = 2012
				    THEN 1033205545
				  WHEN @Ano = 2013
				    THEN 1079201477
				  WHEN @Ano = 2014
				    THEN 1149414264
				  WHEN @Ano = 2015
				    THEN 0
			    END,
				CASE
				  WHEN @Ano = 2006
				    THEN (767076230 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2007
				    THEN (951443422 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2008
				    THEN (833998030 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2009
				    THEN (621701305 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2010
				    THEN (954102784 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2011
				    THEN (1117608261 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2012
				    THEN (1033205545 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2013
				    THEN (1079201477 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2014
				    THEN (1149414264 / dbo.fnTotalCaptadoAnoMec(@Ano)) * 100
				  WHEN @Ano = 2015
				    THEN 0
			    END,
				CASE
				  WHEN @Ano = 2006
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 767076230
				  WHEN @Ano = 2007
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 951443422
				  WHEN @Ano = 2008
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 833998030
				  WHEN @Ano = 2009
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 621701305 
				  WHEN @Ano = 2010
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 954102784 
				  WHEN @Ano = 2011
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 1117608261
				  WHEN @Ano = 2012
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 1033205545
				  WHEN @Ano = 2013
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 1079201477 
				  WHEN @Ano = 2014
				    THEN dbo.fnTotalCaptadoAnoMec(@Ano) - 1149414264 
				  WHEN @Ano = 2015
				    THEN 0
			    END
    SET @Ano = @Ano + 1
  END 
-- ===============================================================================================================
-- COMPARATIVO POR ANO E TIPO DE PESSOA ENTRE A CAPTAÇÃO DE RECRUSOS E A RENÚNCIA FISCAL EFETIVA POR ANO (TIPO 25)
-- ===============================================================================================================
--  DECLARE @Ano int
SET @Ano = 2006
WHILE @Ano <= year(getdate())
  BEGIN
    INSERT INTO Intranet (Tipo,ChaveA,ChaveB,ValorA,ValorB)
         SELECT 25,@Ano,'Pessoa Física',
                (SELECT SUM(CaptacaoReal)     
                        FROM sac.dbo.Captacao a
                        INNER JOIN sac.dbo.Interessado b on (a.CgcCpfMecena = b.CgcCpf)
                        WHERE YEAR(DtRecibo) = @Ano and b.TipoPessoa = 1
                        GROUP BY YEAR(DtRecibo),b.TipoPessoa),
				CASE
				  WHEN @Ano = 2006
				    THEN 2765728
				  WHEN @Ano = 2007
				    THEN 14400189
				  WHEN @Ano = 2008
				    THEN 12931868
				  WHEN @Ano = 2009
				    THEN 11795809
				  WHEN @Ano = 2010
				    THEN 13391170
				  WHEN @Ano = 2011
				    THEN 15134266
				 WHEN @Ano = 2012
				    THEN 18146508
				  WHEN @Ano = 2013
				    THEN 20113064
				  WHEN @Ano = 2014
				    THEN 22124039
				  WHEN @Ano = 2015
				    THEN 0
			    END
		UNION ALL
		SELECT 25,@Ano,'Pessoa Jurídica',
                (SELECT SUM(CaptacaoReal)     
                        FROM sac.dbo.Captacao a
                        INNER JOIN sac.dbo.Interessado b on (a.CgcCpfMecena = b.CgcCpf)
                        WHERE YEAR(DtRecibo) = @Ano and b.TipoPessoa = 2
                        GROUP BY YEAR(DtRecibo),b.TipoPessoa),
				CASE
				  WHEN @Ano = 2006
				    THEN 764310502
				  WHEN @Ano = 2007
				    THEN 937043233
				  WHEN @Ano = 2008
				    THEN 821066162
				  WHEN @Ano = 2009
				    THEN 609905496
				  WHEN @Ano = 2010
				    THEN 940711614
				  WHEN @Ano = 2011
				    THEN 1102473995
				  WHEN @Ano = 2012
				    THEN 1015059037
				  WHEN @Ano = 2013
				    THEN 1059088413
				  WHEN @Ano = 2014
				    THEN 1127290225
				  WHEN @Ano = 2015
				    THEN 0
			    END
    SET @Ano = @Ano + 1
  END
-- =========================================================================================
--VALUE CULTURA - QUANTIDADE DE TRABALHADORES CADASTRADO NO PROGRAMA (TIPO 26)
-- =========================================================================================
INSERT INTO sac.dbo.Intranet 
      (Tipo,
       ChaveA,
       QtdeA)
   SELECT 26, 
          CASE
		    WHEN B.SG_SEXO = 'F         ' 
			  THEN 'FEMININO'          
		    WHEN B.SG_SEXO = 'M         ' 
			  THEN 'MASCULINO'          
			  ELSE 'NÃO INFORMADO'
		    END,
	      COUNT(*) 
     FROM DBMINC.VALE_CULTURA.S_TRABALHADOR  A
     INNER JOIN DBMINC.CORPORATIVO.S_PESSOA_FISICA B ON (A.ID_TRABALHADOR   = B.ID_PESSOA_FISICA)
     GROUP BY B.SG_SEXO   
-- =========================================================================================
--VALE CULTURA -  COMPARATIVO ENTRE O VALOR CARREGADO NO CARTÃO E O VALOR UTILIZADO POR ANO (TIPO 27)
-- =========================================================================================
INSERT INTO sac.dbo.Intranet 
      (Tipo,
       ChaveA,
       ChaveB,
	   ValorA)
  SELECT 27, 'Vl.Carregado' as Descricao,YEAR(TR.DT_CARREGAMENTO) AS NR_ANO_REFERENCIA,SUM(VL_CARREGAMENTO) AS VLCARREGAMENTO
    FROM DBMINC.VALE_CULTURA.S_CARGA_CARTAO_TRABALHADOR AS TR 
    GROUP BY YEAR(TR.DT_CARREGAMENTO)
    UNION ALL
  SELECT 27,'Vl.Utilizado' as Descricao,YEAR(CO.DT_CONSUMO) AS NR_ANO_REFERENCIA, 
         SUM(CO.VL_CONSUMO) AS VL_CONSUMIDO
    FROM DBMINC.CORPORATIVO.S_PESSOA_JURIDICA             AS P1
    INNER JOIN DBMINC.VALE_CULTURA.S_CONSUMO_TRABALHADOR  AS CO ON P1.ID_PESSOA_JURIDICA = CO.ID_RECEBEDORA
    GROUP BY YEAR(CO.DT_CONSUMO) 
-- =========================================================================================
-- VALE CULTURA - COMPARATIVO ENTRE PROJETOS APROVADOS X CARTÃO CARREGADO E UTLIZADO (TIPO 28)
-- =========================================================================================
--DECLARE @Ano int
--DECLARE @UF char(2)
DECLARE TheCursor CURSOR FOR
  SELECT UF FROM sac.dbo.UF 
  OPEN TheCursor        
  WHILE @@FETCH_STATUS = @@FETCH_STATUS
    BEGIN
      FETCH NEXT FROM TheCursor into @UF               
      IF @@FETCH_STATUS = -2
         CONTINUE
      IF @@FETCH_STATUS = -1
         BREAK
      INSERT INTO sac.dbo.Intranet 
                  (Tipo,ChaveA,QtdeA,ValorA,ValorB)
                   SELECT 28,
				           @UF,
                         (SELECT COUNT(a.UfProjeto)
                                 FROM sac.dbo.Projetos a
                                 INNER JOIN sac.dbo.Uf c on (a.UfProjeto = c.Uf)
                                 WHERE a.Mecanismo = '1'
                                       AND a.Situacao  in ('B11','B14','C08','C10','C13','C16','C21','C22','C26','C30','D02','D03','D16','D17','D20','D22','D23',
	                                                       'D28','D29','D32','D33','D34','D35','E10','E11','E12','E13','E15','E50','E59','E60','E61','E71')
                                       AND sac.dbo.fnPercentualCaptado(a.AnoProjeto,a.Sequencial) = 0
                                       AND a.Orgao NOT IN (167,270)
	                                   AND UfProjeto = @UF
                                       GROUP BY a.UfProjeto),
                         (SELECT SUM(VL_CARREGAMENTO)
                                 FROM DBMINC.VALE_CULTURA.S_CARGA_CARTAO_TRABALHADOR   AS CT 
                                 INNER JOIN DBMINC.CORPORATIVO.S_PESSOA_FISICA         AS PF ON CT.ID_TRABALHADOR = PF.ID_PESSOA_FISICA 
                                 INNER JOIN DBMINC.CORPORATIVO.S_ENDERECO              AS EN ON PF.ID_PESSOA_FISICA = EN.ID_PESSOA AND EN.CD_TIPO_ENDERECO = '11' 
                                 INNER JOIN DBMINC.CORPORATIVO.S_LOGRADOURO            AS LO ON EN.ID_LOGRADOURO = LO.ID_LOGRADOURO 
                                 INNER JOIN DBMINC.CORPORATIVO.S_MUNICIPIO             AS MU ON LO.ID_MUNICIPIO = MU.ID_MUNICIPIO 
                                 INNER JOIN DBMINC.CORPORATIVO.S_UF                    AS UF ON  MU.SG_UF = UF.SG_UF
                                 INNER JOIN DBMINC.CORPORATIVO.S_REGIAO                AS RE ON  UF.SG_REGIAO = RE.SG_REGIAO
								 WHERE  UF.SG_UF = @UF  
								 GROUP BY UF.NM_UF),      
					     (SELECT SUM(CO.VL_CONSUMO)
                                 FROM DBMINC.CORPORATIVO.S_PESSOA_JURIDICA             AS P1
                                 INNER JOIN DBMINC.CORPORATIVO.S_ENDERECO              AS EN ON P1.ID_PESSOA_JURIDICA = EN.ID_PESSOA AND EN.CD_TIPO_ENDERECO = '12' 
                                 INNER JOIN DBMINC.CORPORATIVO.S_LOGRADOURO            AS LO ON EN.ID_LOGRADOURO = LO.ID_LOGRADOURO 
                                 INNER JOIN DBMINC.CORPORATIVO.S_MUNICIPIO             AS MU ON LO.ID_MUNICIPIO = MU.ID_MUNICIPIO 
                                 INNER JOIN DBMINC.CORPORATIVO.S_UF                    AS UF ON  MU.SG_UF = UF.SG_UF
                                 INNER JOIN DBMINC.CORPORATIVO.S_REGIAO                AS RE ON  UF.SG_REGIAO = RE.SG_REGIAO
                                 INNER JOIN DBMINC.VALE_CULTURA.S_CONSUMO_TRABALHADOR  AS CO ON P1.ID_PESSOA_JURIDICA = CO.ID_RECEBEDORA
								 WHERE  UF.SG_UF = @UF
                                 GROUP BY UF.NM_UF)                      
    END             
   CLOSE TheCursor
   DEALLOCATE TheCursor 
-- =========================================================================================
--PROJETOS AVALIADOS NA CNIC POR REUNIÃO, ENQUADRAMENTO, RESULTADO DA AVALIAÇÃO (TIPO 29)
-- =========================================================================================
INSERT INTO Intranet (Tipo,QtdeA,ChaveA,ChaveB,ChaveC,ChaveD,CampoA,CampoB,ValorA)
	SELECT 29,
	       a.idNrReuniao,
		   e.Enquadramento,
		   CASE
		     WHEN a.StAnalise IN('AC','AS')
			   THEN 'Aprovado'
			   ELSE 'Indeferido'
			 END,
           a.StAnalise, 
		   c.AnoProjeto+c.Sequencial, 
		   c.NomeProjeto, 
		   d.Descricao,
	       sac.dbo.fnTotalAprovadoProjeto(c.AnoProjeto,c.Sequencial)
FROM BDCORPORATIVO.scSAC.tbPauta                                a
INNER JOIN BDCORPORATIVO.scSAC.tbDistribuicaoProjetoComissao AS b ON (a.IdPRONAC = b.idPRONAC)
INNER JOIN SAC.dbo.Projetos                                  AS c ON (a.idPronac = c.idPronac)
INNER JOIN SAC.dbo.Area                                      AS d ON (c.Area     = d.Codigo)
INNER JOIN SAC.dbo.Enquadramento                             AS e ON (a.idPronac = e.idPronac)
WHERE b.stDistribuicao = 'A'
-- =========================================================================================
-- COMPARATIVO POR FAIXA DE CAPTAÇÃO DE PROJETOS COM PRESTAÇÃO DE CONTAS (TIPO 30)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,ChaveC,QtdeA)
	SELECT 30,'ATÉ 10%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) < = 10
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 11 A 20%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 10 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 20 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 21 A 30%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 20 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 30 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 31 A 40%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 30 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 40 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 41 A 50%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 40 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 50 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 51 A 60%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 50 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 60 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 61 A 70%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 60 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 70 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 71 A 80%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 70 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 80 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 81 A 90%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 80 
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) <= 90 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	UNION ALL
	SELECT 30,'DE 90 A 100%',YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)) as Ano,
		   CASE
			 WHEN Situacao in ('E22','E67','L06','G47')
			   THEN 'REPROVADO'
			   ELSE 'APROVADO'
			 END as Estado,
		   COUNT(*) 
	FROM sac.dbo.Projetos
	WHERE Situacao in ('E19','L01','L02','L03','L04','L07','L08','G19','E22','E67','L06','G47')
		  AND Mecanismo = '1'
		  AND sac.dbo.fnPercentualCaptado(AnoProjeto,Sequencial) > 90 
	GROUP BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial)),Situacao
	ORDER BY YEAR(sac.dbo.fnDtAprovacao(AnoProjeto,Sequencial))
-- =========================================================================================
-- EDITAIS (TIPO 31)
-- =========================================================================================
INSERT INTO Intranet (Tipo,ChaveA,CampoA,CampoB,CampoC,ChaveB,ChaveC,CampoD)
     SELECT 31,a.AnoProjeto+a.Sequencial,a.NomeProjeto,c.Descricao,d.Descricao,
           (SELECT top 1 u1.Regiao FROM Agentes..EnderecoNacional a1 
                                   INNER JOIN Agentes..UF u1 on (u1.idUF = a1.UF)
                                   WHERE b.idAgente = a1.idAgente) as Regiao,
           (SELECT top 1 u2.Descricao FROM Agentes..EnderecoNacional a2 
                                      INNER JOIN Agentes..UF u2 on (u2.idUF = a2.UF)
                                      WHERE b.idAgente = a2.idAgente) as UF,
           (SELECT top 1 m.Descricao FROM Agentes..EnderecoNacional a3 
                                     INNER JOIN Agentes..Municipios m on (m.idMunicipioIBGE = a3.Cidade and m.idUFIBGE = a3.UF)
                                     WHERE b.idAgente = a3.idAgente) as Municipio
           FROM sac.dbo.Projetos a
           INNER JOIN sac.dbo.PreProjeto b on (a.idProjeto = b.idPreProjeto)
           INNER JOIN agentes.dbo.Nomes c  on (b.idAgente = c.idAgente)
           INNER JOIN sac.dbo.Situacao d on (a.Situacao = d.Codigo)
           WHERE a.Situacao in ('G13','G51','G52','G53','G54','G55','G56')
-- =========================================================================================
-- EDITAIS (TIPO 32)
-- =========================================================================================
INSERT INTO Intranet (Tipo,CampoA,ChaveA,CampoB,ChaveB,CampoC,ChaveC,ChaveD,CampoD)
     SELECT 32,Tabelas.dbo.fnEstruturaOrgao(idOrgao,0),year(DtEdital),c.nmFormDocumento,b.idPreProjeto,b.NomeProjeto,
           (SELECT top 1 u1.Regiao FROM Agentes..EnderecoNacional a1 
                                   INNER JOIN Agentes..UF u1 on (u1.idUF = a1.UF)
                                   WHERE b.idAgente = a1.idAgente) as Regiao,
           (SELECT top 1 u2.Descricao FROM Agentes..EnderecoNacional a2 
                                      INNER JOIN Agentes..UF u2 on (u2.idUF = a2.UF)
                                      WHERE b.idAgente = a2.idAgente) as UF,
           (SELECT top 1 m.Descricao FROM Agentes..EnderecoNacional a3 
                                    INNER JOIN Agentes..Municipios m on (m.idMunicipioIBGE = a3.Cidade and m.idUFIBGE = a3.UF)
                                    WHERE b.idAgente = a3.idAgente) as Municipio
           FROM Edital a
           INNER JOIN PreProjeto b on (a.idEdital = b.idEdital)
           INNER JOIN BDCORPORATIVO.scQuiz.tbFormDocumento c on (b.idEdital = c.idEdital)
           INNER JOIN sac.dbo.tbMovimentacao               d on (b.idPreProjeto = d.idProjeto)
           WHERE c.stModalidadeDocumento is not null 
                 AND idOrgao not in (1000)
                 AND d.Movimentacao not in (95) 
                 AND d.stEstado = 0
-- =========================================================================================
-- PROJETOS APROVADOS POR FAIXA DE VALOR E ENQUADRAMENTO (TIPO 33)
-- =========================================================================================
INSERT INTO Intranet (Tipo,CampoA,ChaveA,ChaveB,ChaveC,CampoB,ValorA,ValorB)
	SELECT 33,'1. ATÉ R$ 50.000,00',YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)) as Ano,b.Enquadramento,
	       a.AnoProjeto+a.Sequencial as Pronac,a.NomeProjeto,sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial),
		   (SELECT ISNULL(SUM(c.CaptacaoReal),0) FROM Captacao c 
		                             WHERE a.AnoProjeto = c.AnoProjeto and a.Sequencial = c.Sequencial 
									       and YEAR(c.DtRecibo) = YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)))
	       FROM sac.dbo.Projetos a
	       INNER JOIN sac.dbo.Enquadramento b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
	       WHERE Mecanismo = '1'
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) < = 50000
    UNION ALL
	SELECT 33,'2. DE R$ 50.000,00 A R$ 100.000,00',YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)) as Ano,b.Enquadramento,
	       a.AnoProjeto+a.Sequencial as Pronac,a.NomeProjeto,sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial),
		   (SELECT ISNULL(SUM(c.CaptacaoReal),0) FROM Captacao c 
		                             WHERE a.AnoProjeto = c.AnoProjeto and a.Sequencial = c.Sequencial 
									       and YEAR(c.DtRecibo) = YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)))
	       FROM sac.dbo.Projetos a
	       INNER JOIN sac.dbo.Enquadramento b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
	       WHERE Mecanismo = '1'
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) >   50000
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) < = 100000
    UNION ALL
	SELECT 33,'3. DE R$ 100.000,00 A R$ 250.000,00',YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)) as Ano,b.Enquadramento,
	       a.AnoProjeto+a.Sequencial as Pronac,a.NomeProjeto,sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial),
		   (SELECT ISNULL(SUM(c.CaptacaoReal),0) FROM Captacao c 
		                             WHERE a.AnoProjeto = c.AnoProjeto and a.Sequencial = c.Sequencial 
									       and YEAR(c.DtRecibo) = YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial))) 
	       FROM sac.dbo.Projetos a
	       INNER JOIN sac.dbo.Enquadramento b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
	       WHERE Mecanismo = '1'
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) >   100000
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) < = 250000
    UNION ALL
	SELECT 33,'4. DE R$ 250.000,00 A R$ 500.000,00',YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)) as Ano,b.Enquadramento,
	       a.AnoProjeto+a.Sequencial as Pronac,a.NomeProjeto,sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial),
		   (SELECT ISNULL(SUM(c.CaptacaoReal),0) FROM Captacao c 
		                             WHERE a.AnoProjeto = c.AnoProjeto and a.Sequencial = c.Sequencial 
									       and YEAR(c.DtRecibo) = YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)))
	       FROM sac.dbo.Projetos a
	       INNER JOIN sac.dbo.Enquadramento b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
	       WHERE Mecanismo = '1'
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) >   250000
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) < = 500000
    UNION ALL
	SELECT 33,'5. DE R$ 500.000,00 A R$ 1.000.000,00',YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)) as Ano,b.Enquadramento,
	       a.AnoProjeto+a.Sequencial as Pronac,a.NomeProjeto,sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial),
		   (SELECT ISNULL(SUM(c.CaptacaoReal),0) FROM Captacao c 
		                             WHERE a.AnoProjeto = c.AnoProjeto and a.Sequencial = c.Sequencial 
									       and YEAR(c.DtRecibo) = YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial))) 
	       FROM sac.dbo.Projetos a
	       INNER JOIN sac.dbo.Enquadramento b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
	       WHERE Mecanismo = '1'
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) >   500000
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) < = 1000000
    UNION ALL
	SELECT 33,'6. ACIMA DE R$ 1.000.000,00',YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial)) as Ano,b.Enquadramento,
	       a.AnoProjeto+a.Sequencial as Pronac,a.NomeProjeto,sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial),
		   (SELECT ISNULL(SUM(c.CaptacaoReal),0) FROM Captacao c 
		                             WHERE a.AnoProjeto = c.AnoProjeto and a.Sequencial = c.Sequencial 
									       and YEAR(c.DtRecibo) = YEAR(sac.dbo.fnDtAprovacao(a.AnoProjeto,a.Sequencial))) 
	       FROM sac.dbo.Projetos a
	       INNER JOIN sac.dbo.Enquadramento b on (a.AnoProjeto = b.AnoProjeto and a.Sequencial = b.Sequencial)
	       WHERE Mecanismo = '1'
		         AND sac.dbo.fnTotalAprovadoProjeto(a.AnoProjeto,a.Sequencial) >   1000000
		   ORDER BY 2,3,4,6
-- =========================================================================================
-- APROVAÇÃO POR ANO E REGIÃO (TIPO 34)
-- ========================================================================================= 
INSERT INTO Intranet (Tipo,ChaveA,ChaveB,ChaveC,QtdeA,ValorA)
   SELECT 34,YEAR(c.DtAprovacao) as Ano,b.Regiao,a.UfProjeto,COUNT(*) as Qtde, SUM(AprovadoReal) as vlAprovado
          FROM sac.dbo.Projetos a
          INNER JOIN sac.dbo.UF b on (a.UFProjeto = b.UF)
          INNER JOIn sac.dbo.Aprovacao c on (a.AnoProjeto = c.AnoProjeto AND a.Sequencial = c.Sequencial AND c.TipoAprovacao = '1')
          GROUP BY YEAR(c.DtAprovacao),b.Regiao,a.UfProjeto
          ORDER BY YEAR(c.DtAprovacao),b.Regiao,a.UfProjeto 
-- =========================================================================================
-- CONCILIAÇÃO BANCÁRIA (TIPO 35)
-- =========================================================================================
INSERT INTO Intranet (Tipo,QtdeA,ChaveA,CampoA,CampoB,ChaveB,CampoC,QtdeB,ChaveC,DataA,ValorA,ValorB,
                      CampoD,DataB,ValorC,ValorD)
     SELECT 35,a.idPronac,a.AnoProjeto+a.Sequencial,a.NomeProjeto,f.Descricao,g.CNPJCPF,h.Descricao,
            d.idComprovantePagamento,d.nrDocumentoDePagamento,d.dtPagamento,d.vlComprovacao,
	        sac.dbo.fnVlComprovadoDocumento(a.idPronac,d.nrDocumentoDePagamento),
            ISNULL(e.dsLancamento,'SEM INFORMAÇÕES BANCÁRIAS'),e.dtLancamento,e.vlLancamento,
	        sac.dbo.fnVlComprovadoDocumento(a.idPronac,d.nrDocumentoDePagamento)- e.vlLancamento
FROM       sac.dbo.Projetos                                             a
INNER JOIN sac.dbo.tbPlanilhaAprovacao                                  b on (a.idPronac                  = b.idPronac)
INNER JOIN BDCORPORATIVO.scSAC.tbComprovantePagamentoxPlanilhaAprovacao c on (b.idPlanilhaAprovacao       = c.idPlanilhaAprovacao)
INNER JOIN BDCORPORATIVO.scSAC.tbComprovantePagamento                   d on (c.idComprovantePagamento    = d.idComprovantePagamento) 
LEFT  JOIN Sac.dbo.tbLancamentoBancario                                 e on (a.IdPRONAC = e.idPronac 
                                                                              and '0000'+d.nrDocumentoDePagamento = e.nrLancamento)
INNER JOIN sac.dbo.tbPlanilhaItens                                      f on (b.idPlanilhaItem = f.idPlanilhaItens)
INNER JOIN Agentes.dbo.Agentes                                          g on (d.idFornecedor = g.idAgente)
INNER JOIN Agentes.dbo.Nomes                                            h on (g.idAgente = h.idAgente)
  
-- =========================================================================================
--
-- =========================================================================================
GO
SET QUOTED_IDENTIFIER OFF 
GO
SET ANSI_NULLS ON 
GO
SET NOCOUNT ON
GO
-- =========================================================================================
-- PERMISSÕES
-- =========================================================================================
GRANT  EXECUTE  ON dbo.spSalicnew  TO MinC_Intranet
GO

GRANT EXECUTE ON dbo.spSalicnew TO Administrador
GO

GRANT EXECUTE ON dbo.spSalicnew TO usuarios_internet
GO

