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
-- Table structure for table `bancos_dados`
--

DROP TABLE IF EXISTS `bancos_dados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bancos_dados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('materia_prima','insumos','componentes','recursos','ferramental','materiais_ferramental','embalagem','normas','mao_obra','custos_indiretos','classificacao_fiscal') NOT NULL,
  `dados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`dados`)),
  `versao` int(11) DEFAULT 1,
  `criado_por` varchar(36) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_versao` (`versao`),
  CONSTRAINT `bancos_dados_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bancos_dados`
--

LOCK TABLES `bancos_dados` WRITE;
/*!40000 ALTER TABLE `bancos_dados` DISABLE KEYS */;
INSERT INTO `bancos_dados` VALUES (1,'classificacao_fiscal','[{\"ncm\": \"7003.00.00\", \"descricao\": \"Vidro vazado ou laminado\", \"ipi\": \"10\", \"icms\": \"18\", \"pis\": \"1.65\", \"cofins\": \"7.6\"}, {\"ncm\": \"7004.00.00\", \"descricao\": \"Vidro estirado ou soprado\", \"ipi\": \"10\", \"icms\": \"18\", \"pis\": \"1.65\", \"cofins\": \"7.6\"}, {\"ncm\": \"7005.00.00\", \"descricao\": \"Vidro flotado\", \"ipi\": \"10\", \"icms\": \"18\", \"pis\": \"1.65\", \"cofins\": \"7.6\"}]',1,NULL,'2026-03-09 03:12:15','2026-03-09 03:12:15'),(2,'recursos','[{\"processo\": \"Corte\", \"recurso\": \"Mesa de Corte CNC\", \"potencia\": \"15\", \"kwh\": \"0.85\", \"agua\": \"0\", \"preco_agua\": \"0\", \"rendimento\": \"95\", \"producao_hora\": \"20\", \"setup\": \"10\", \"depreciacao\": \"1500\", \"outros\": \"50\"}, {\"processo\": \"Têmpera\", \"recurso\": \"Forno de Têmpera\", \"potencia\": \"150\", \"kwh\": \"0.85\", \"agua\": \"2\", \"preco_agua\": \"8.50\", \"rendimento\": \"98\", \"producao_hora\": \"15\", \"setup\": \"30\", \"depreciacao\": \"5000\", \"outros\": \"200\"}, {\"processo\": \"Laminação\", \"recurso\": \"Prensa de Laminação\", \"potencia\": \"80\", \"kwh\": \"0.85\", \"agua\": \"1\", \"preco_agua\": \"8.50\", \"rendimento\": \"96\", \"producao_hora\": \"10\", \"setup\": \"45\", \"depreciacao\": \"3500\", \"outros\": \"150\"}]',1,NULL,'2026-03-09 03:12:15','2026-03-09 03:12:15'),(3,'materia_prima','[{\"tipo\": \"Vidro Float\", \"codigo\": \"VF-04\", \"descricao\": \"Vidro Float 4mm\", \"ncm\": \"7005.00.00\", \"unidade\": \"m²\", \"valor\": \"45.00\", \"ipi\": \"10\", \"icms\": \"18\"}, {\"tipo\": \"Vidro Float\", \"codigo\": \"VF-05\", \"descricao\": \"Vidro Float 5mm\", \"ncm\": \"7005.00.00\", \"unidade\": \"m²\", \"valor\": \"55.00\", \"ipi\": \"10\", \"icms\": \"18\"}, {\"tipo\": \"Vidro Float\", \"codigo\": \"VF-06\", \"descricao\": \"Vidro Float 6mm\", \"ncm\": \"7005.00.00\", \"unidade\": \"m²\", \"valor\": \"65.00\", \"ipi\": \"10\", \"icms\": \"18\"}]',1,NULL,'2026-03-09 03:12:15','2026-03-09 03:12:15'),(4,'ferramental','[{\"descricao\": \"Molde Curvo\", \"vida_util\": \"50000\", \"valor\": \"25000.00\"}, {\"descricao\": \"Gabarito de Corte\", \"vida_util\": \"100000\", \"valor\": \"8500.00\"}, {\"descricao\": \"Matriz Serigrafia\", \"vida_util\": \"75000\", \"valor\": \"12000.00\"}]',1,NULL,'2026-03-09 03:12:15','2026-03-09 03:12:15'),(5,'mao_obra','[{\"funcao\": \"Operador de Corte\", \"setor\": \"Produção\", \"centro_custo\": \"CC-001\", \"salario_hora\": \"18.50\"}, {\"funcao\": \"Operador de Têmpera\", \"setor\": \"Produção\", \"centro_custo\": \"CC-002\", \"salario_hora\": \"22.00\"}, {\"funcao\": \"Inspetor de Qualidade\", \"setor\": \"Qualidade\", \"centro_custo\": \"CC-003\", \"salario_hora\": \"20.00\"}]',1,NULL,'2026-03-09 03:12:15','2026-03-09 03:12:15');
/*!40000 ALTER TABLE `bancos_dados` ENABLE KEYS */;
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
