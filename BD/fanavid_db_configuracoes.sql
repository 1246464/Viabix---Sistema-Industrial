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
-- Table structure for table `configuracoes`
--

DROP TABLE IF EXISTS `configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('texto','numero','booleano','json') DEFAULT 'texto',
  `descricao` text DEFAULT NULL,
  `atualizado_por` varchar(36) DEFAULT NULL,
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_chave` (`chave`),
  CONSTRAINT `configuracoes_ibfk_1` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracoes`
--

LOCK TABLES `configuracoes` WRITE;
/*!40000 ALTER TABLE `configuracoes` DISABLE KEYS */;
INSERT INTO `configuracoes` VALUES (1,'versao_sistema','7.1','texto','Versão atual do sistema',NULL,'2026-03-09 03:12:15'),(2,'ultima_atualizacao','2026-03-09 00:12:15','texto','Data da última atualização',NULL,'2026-03-09 03:12:15'),(3,'margem_padrao','20','numero','Margem de lucro padrão (%)',NULL,'2026-03-09 03:12:15'),(4,'encargos_padrao','80','numero','Encargos sociais padrão (%)',NULL,'2026-03-09 03:12:15'),(5,'ipi_padrao','10','numero','Alíquota IPI padrão (%)',NULL,'2026-03-09 03:12:15'),(6,'icms_padrao','18','numero','Alíquota ICMS padrão (%)',NULL,'2026-03-09 03:12:15'),(7,'pis_padrao_lucro_real','1.65','numero','Alíquota PIS - Lucro Real',NULL,'2026-03-09 03:12:15'),(8,'cofins_padrao_lucro_real','7.6','numero','Alíquota COFINS - Lucro Real',NULL,'2026-03-09 03:12:15'),(9,'pis_padrao_outros','0.65','numero','Alíquota PIS - Outros regimes',NULL,'2026-03-09 03:12:15'),(10,'cofins_padrao_outros','3.0','numero','Alíquota COFINS - Outros regimes',NULL,'2026-03-09 03:12:15'),(11,'irpj_padrao','15','numero','Alíquota IRPJ padrão',NULL,'2026-03-09 03:12:15'),(12,'csll_padrao','9','numero','Alíquota CSLL padrão',NULL,'2026-03-09 03:12:15'),(13,'percentual_presumido_padrao','8','numero','Percentual de presunção IRPJ/CSLL',NULL,'2026-03-09 03:12:15'),(14,'horas_trabalhadas_padrao','176','numero','Horas trabalhadas por mês',NULL,'2026-03-09 03:12:15'),(15,'kwh_padrao','0.85','numero','Preço padrão do kWh',NULL,'2026-03-09 03:12:15'),(16,'agua_padrao','8.50','numero','Preço padrão do m³ de água',NULL,'2026-03-09 03:12:15'),(17,'cbs_padrao','12.5','numero','Alíquota CBS - Reforma 2027',NULL,'2026-03-09 03:12:15'),(18,'ibs_padrao','14','numero','Alíquota IBS - Reforma 2027',NULL,'2026-03-09 03:12:15'),(19,'is_padrao','0','numero','Alíquota IS - Reforma 2027',NULL,'2026-03-09 03:12:15'),(20,'manutencao_programada','0','booleano','Sistema em manutenção',NULL,'2026-03-09 03:12:15'),(21,'limite_arquivos','52428800','numero','Limite de upload (bytes)',NULL,'2026-03-09 03:12:15'),(22,'formatos_imagem','[\"jpg\",\"jpeg\",\"png\",\"gif\"]','json','Formatos de imagem aceitos',NULL,'2026-03-09 03:12:15');
/*!40000 ALTER TABLE `configuracoes` ENABLE KEYS */;
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
