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
-- Table structure for table `conflitos_edicao`
--

DROP TABLE IF EXISTS `conflitos_edicao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conflitos_edicao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anvi_id` varchar(50) NOT NULL,
  `usuario_id` varchar(36) NOT NULL,
  `versao_usuario` int(11) NOT NULL,
  `versao_banco` int(11) NOT NULL,
  `dados_usuario` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_usuario`)),
  `dados_banco` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_banco`)),
  `resolvido` tinyint(1) DEFAULT 0,
  `data_conflito` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_resolucao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_anvi` (`anvi_id`),
  KEY `idx_resolvido` (`resolvido`),
  KEY `idx_data` (`data_conflito`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_conflitos_data` (`data_conflito`),
  CONSTRAINT `conflitos_edicao_ibfk_1` FOREIGN KEY (`anvi_id`) REFERENCES `anvis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conflitos_edicao_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conflitos_edicao`
--

LOCK TABLES `conflitos_edicao` WRITE;
/*!40000 ALTER TABLE `conflitos_edicao` DISABLE KEYS */;
/*!40000 ALTER TABLE `conflitos_edicao` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-11 22:49:40
