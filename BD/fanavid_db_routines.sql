-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: fanavid_db
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary view structure for view `vw_conflitos_pendentes`
--

DROP TABLE IF EXISTS `vw_conflitos_pendentes`;
/*!50001 DROP VIEW IF EXISTS `vw_conflitos_pendentes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_conflitos_pendentes` AS SELECT 
 1 AS `id`,
 1 AS `anvi_id`,
 1 AS `usuario_id`,
 1 AS `versao_usuario`,
 1 AS `versao_banco`,
 1 AS `dados_usuario`,
 1 AS `dados_banco`,
 1 AS `resolvido`,
 1 AS `data_conflito`,
 1 AS `data_resolucao`,
 1 AS `anvi_numero`,
 1 AS `anvi_revisao`,
 1 AS `usuario_nome`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_top_usuarios`
--

DROP TABLE IF EXISTS `vw_top_usuarios`;
/*!50001 DROP VIEW IF EXISTS `vw_top_usuarios`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_top_usuarios` AS SELECT 
 1 AS `nome`,
 1 AS `login`,
 1 AS `nivel`,
 1 AS `total_atividades`,
 1 AS `ultima_atividade`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_resumo_anvis_status`
--

DROP TABLE IF EXISTS `vw_resumo_anvis_status`;
/*!50001 DROP VIEW IF EXISTS `vw_resumo_anvis_status`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_resumo_anvis_status` AS SELECT 
 1 AS `status`,
 1 AS `total`,
 1 AS `primeira_anvi`,
 1 AS `ultima_anvi`,
 1 AS `volume_medio`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `vw_atividades_recentes`
--

DROP TABLE IF EXISTS `vw_atividades_recentes`;
/*!50001 DROP VIEW IF EXISTS `vw_atividades_recentes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_atividades_recentes` AS SELECT 
 1 AS `data_hora`,
 1 AS `usuario`,
 1 AS `login`,
 1 AS `acao`,
 1 AS `detalhes`,
 1 AS `ip_address`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `vw_conflitos_pendentes`
--

/*!50001 DROP VIEW IF EXISTS `vw_conflitos_pendentes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_conflitos_pendentes` AS select `c`.`id` AS `id`,`c`.`anvi_id` AS `anvi_id`,`c`.`usuario_id` AS `usuario_id`,`c`.`versao_usuario` AS `versao_usuario`,`c`.`versao_banco` AS `versao_banco`,`c`.`dados_usuario` AS `dados_usuario`,`c`.`dados_banco` AS `dados_banco`,`c`.`resolvido` AS `resolvido`,`c`.`data_conflito` AS `data_conflito`,`c`.`data_resolucao` AS `data_resolucao`,`a`.`numero` AS `anvi_numero`,`a`.`revisao` AS `anvi_revisao`,`u`.`nome` AS `usuario_nome` from ((`conflitos_edicao` `c` join `anvis` `a` on(`c`.`anvi_id` = `a`.`id`)) join `usuarios` `u` on(`c`.`usuario_id` = `u`.`id`)) where `c`.`resolvido` = 0 order by `c`.`data_conflito` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_top_usuarios`
--

/*!50001 DROP VIEW IF EXISTS `vw_top_usuarios`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_top_usuarios` AS select `u`.`nome` AS `nome`,`u`.`login` AS `login`,`u`.`nivel` AS `nivel`,count(`l`.`id`) AS `total_atividades`,max(`l`.`data_hora`) AS `ultima_atividade` from (`usuarios` `u` left join `logs_atividade` `l` on(`u`.`id` = `l`.`usuario_id`)) group by `u`.`id` order by count(`l`.`id`) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_resumo_anvis_status`
--

/*!50001 DROP VIEW IF EXISTS `vw_resumo_anvis_status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_resumo_anvis_status` AS select `anvis`.`status` AS `status`,count(0) AS `total`,min(`anvis`.`data_criacao`) AS `primeira_anvi`,max(`anvis`.`data_criacao`) AS `ultima_anvi`,avg(`anvis`.`volume_mensal`) AS `volume_medio` from `anvis` group by `anvis`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_atividades_recentes`
--

/*!50001 DROP VIEW IF EXISTS `vw_atividades_recentes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_atividades_recentes` AS select `l`.`data_hora` AS `data_hora`,`u`.`nome` AS `usuario`,`u`.`login` AS `login`,`l`.`acao` AS `acao`,`l`.`detalhes` AS `detalhes`,`l`.`ip_address` AS `ip_address` from (`logs_atividade` `l` left join `usuarios` `u` on(`l`.`usuario_id` = `u`.`id`)) order by `l`.`data_hora` desc limit 100 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-11 22:49:40
