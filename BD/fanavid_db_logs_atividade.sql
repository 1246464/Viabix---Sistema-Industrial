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
-- Table structure for table `logs_atividade`
--

DROP TABLE IF EXISTS `logs_atividade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_atividade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` varchar(36) DEFAULT NULL,
  `acao` varchar(50) NOT NULL,
  `detalhes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_data` (`data_hora`),
  KEY `idx_logs_data` (`data_hora`),
  CONSTRAINT `logs_atividade_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_atividade`
--

LOCK TABLES `logs_atividade` WRITE;
/*!40000 ALTER TABLE `logs_atividade` DISABLE KEYS */;
INSERT INTO `logs_atividade` VALUES (1,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:18:37'),(2,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:26:40'),(3,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:26:48'),(4,'admin-001','salvar_anvi','Salvou ANVI: 2026 Rev. 00 (versão 1)','::1',NULL,'2026-03-12 00:31:43'),(5,'admin-001','salvar_anvi','Salvou ANVI: 2026 Rev. 00 (versão 2)','::1',NULL,'2026-03-12 00:35:12'),(6,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:35:38'),(7,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:35:39'),(8,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:35:49'),(9,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:35:50'),(10,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:36:32'),(11,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:36:43'),(12,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:36:44'),(13,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:38:27'),(14,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 00:38:31'),(15,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:14:31'),(16,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:14:38'),(17,'admin-001','salvar_anvi','Salvou ANVI: 2026 Rev. 00 (versão 3)','::1',NULL,'2026-03-12 01:15:41'),(18,'admin-001','salvar_anvi','Salvou ANVI: TESTE-001 Rev. 01 (versão 1)','::1',NULL,'2026-03-12 01:16:28'),(19,'admin-001','salvar_anvi','Salvou ANVI: TESTE-001 Rev. 01 (versão 2)','::1',NULL,'2026-03-12 01:20:14'),(20,'admin-001','salvar_anvi','Salvou ANVI: TESTE-001 Rev. 01 (versão 3)','::1',NULL,'2026-03-12 01:20:36'),(21,'admin-001','login','Login realizado com sucesso','192.168.3.16','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3 Mobile/15E148 Safari/604.1','2026-03-12 01:21:51'),(22,'admin-001','salvar_anvi','Salvou ANVI: ANVI-2026-TESTE-005 Rev. 01 (versão 1)','::1',NULL,'2026-03-12 01:29:09'),(23,'admin-001','salvar_anvi','Salvou ANVI: ANVI-2026-TESTE-005 Rev. 00 (versão 1)','192.168.3.16',NULL,'2026-03-12 01:29:22'),(24,'admin-001','salvar_anvi','Salvou ANVI: ANVI-2026-TESTE-005 Rev. 00 (versão 2)','192.168.3.16',NULL,'2026-03-12 01:30:19'),(25,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:31:19'),(26,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:32:25'),(27,'admin-001','criar_usuario','Criou usuário: cventura','::1',NULL,'2026-03-12 01:33:12'),(28,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:33:21'),(29,'03435c63-a7fb-4c75-8357-85e400daa149','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:33:31'),(30,'03435c63-a7fb-4c75-8357-85e400daa149','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:34:00'),(31,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:34:08'),(32,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:34:21'),(33,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:37:13'),(34,'admin-001','logout','Logout realizado','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:37:15'),(35,'admin-001','login','Login realizado com sucesso','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:40:27');
/*!40000 ALTER TABLE `logs_atividade` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-11 22:49:39
