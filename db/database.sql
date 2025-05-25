CREATE DATABASE  IF NOT EXISTS `ca` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `ca`;
-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: ca
-- ------------------------------------------------------
-- Server version	8.0.41

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
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_user` int NOT NULL,
  `customer_id` int NOT NULL,
  `start_date` date DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `payment` double DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_contracts_users1_idx` (`created_by_user`),
  KEY `fk_contracts_customers1_idx` (`customer_id`),
  CONSTRAINT `fk_contracts_customers1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_contracts_users1` FOREIGN KEY (`created_by_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
INSERT INTO `contracts` VALUES (13,2,18,'2025-05-10','2025-05-31',223232,'2fe','2025-05-24 17:13:51','2025-05-24 17:13:51');
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_user` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(45) DEFAULT NULL,
  `address` varchar(45) DEFAULT NULL,
  `phone_number` varchar(45) DEFAULT NULL,
  `free_trial` varchar(45) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_customers_users_idx` (`created_by_user`),
  CONSTRAINT `fk_customers_users` FOREIGN KEY (`created_by_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (3,2,'2025-05-23 21:43:32','Adam Lee','7901 CAMERON RD','7604178859','demo','2025-05-24','11','2025-05-24 10:42:24'),(5,1,'2025-05-24 08:41:57','Muhammad Salah-al-Din Hazim al-Huyyali','QULA RESI','07708138928','NOP','2025-05-24','0','2025-05-24 08:41:57'),(6,1,'2025-05-24 09:45:19','created with customer 1','anywhere','23843972374','free','2025-05-24','0','2025-05-24 09:45:19'),(7,1,'2025-05-24 09:45:42','Salahaldin','353 Nmunayee City, Qularaesee, Sulaymaniyah','07708138928',NULL,'2025-05-24','0','2025-05-24 09:45:42'),(8,1,'2025-05-24 10:00:22','Salahaldin','353 Nmunayee City, Qularaesee, Sulaymaniyah','07708138928','qwwq','2025-05-22','0','2025-05-24 10:00:22'),(9,1,'2025-05-24 10:07:21','CUID2','ADDRESS','22','FREE','2025-05-24','0','2025-05-24 10:07:21'),(11,2,'2025-05-24 10:09:58','Mohammed Hazim221','Sulaimaniya, QULA REISI','0770813892822','33','2025-05-22','31','2025-05-24 10:57:02'),(12,1,'2025-05-24 11:25:39','Ali Abdullah','Baghdad','077099889898','Yes','2025-05-30','0','2025-05-24 11:25:39'),(13,1,'2025-05-24 11:26:08','Ali Abdullah','Baghdad','077099889898','Yes','2025-05-30','0','2025-05-24 11:26:08'),(16,1,'2025-05-24 11:30:29','Aloo bayroot','alooo','192981293198','yawall','2025-05-24','0','2025-05-24 11:30:29'),(17,1,'2025-05-24 11:38:05','wawee','wawee','08889998888','free','2025-05-24',NULL,'2025-05-24 11:38:05'),(18,1,'2025-05-24 11:40:03','new customer','ad;lkfj;','2348','alskdjf','2025-05-24','0','2025-05-24 11:40:03'),(19,1,'2025-05-24 11:40:08','new customerrr','ad;lkfj;','2348324234','alskdjf','2025-05-24',NULL,'2025-05-24 11:52:53'),(21,1,'2025-05-24 12:07:26','wewa','wflkejf','39298','sdlkfj','2025-05-24','0','2025-05-24 12:07:26');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_item`
--

DROP TABLE IF EXISTS `invoice_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `item_name` varchar(45) DEFAULT NULL,
  `price` double DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `subtotal` double DEFAULT NULL,
  `note` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_invoice_item_invoice1_idx` (`invoice_id`),
  CONSTRAINT `fk_invoice_item_invoice1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_item`
--

LOCK TABLES `invoice_item` WRITE;
/*!40000 ALTER TABLE `invoice_item` DISABLE KEYS */;
INSERT INTO `invoice_item` VALUES (37,8,'car',6000,2,12000,'pay go'),(38,8,'invest',2000,2,4000,''),(54,9,'adf',20,1,20,''),(55,9,'vv',3,4,12,''),(56,9,'aa',2,1,2,''),(61,12,'item2',12,5,60,''),(62,12,'item2',33,2,66,'');
/*!40000 ALTER TABLE `invoice_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_by_user` int NOT NULL,
  `customer_id` int NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `inv_date` date DEFAULT NULL,
  `done_undone` varchar(45) DEFAULT NULL,
  KEY `fk_invoice_users1_idx` (`created_by_user`),
  KEY `fk_invoice_customers_idx` (`id`),
  CONSTRAINT `fk_invoice_users1` FOREIGN KEY (`created_by_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (8,1,3,'Adam Lee','2025-05-23','Undone'),(9,2,12,'Ali Abdullah','2025-05-24','Done'),(12,1,21,'wewa','2025-05-24','Done');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'user','user'),(2,'user2','user2');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_actions`
--

DROP TABLE IF EXISTS `users_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_actions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE','VIEW','LOGIN','LOGOUT') NOT NULL,
  `table_name` varchar(50) NOT NULL COMMENT 'customers, contracts, invoices, invoice_details, users',
  `record_id` int DEFAULT NULL COMMENT 'ID of the affected record',
  `action_description` varchar(255) NOT NULL COMMENT 'Brief description of the action',
  `old_values` json DEFAULT NULL COMMENT 'Previous values before update/delete',
  `new_values` json DEFAULT NULL COMMENT 'New values after create/update',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'User IP address (supports IPv6)',
  `user_agent` text COMMENT 'Browser/client information',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'User session identifier',
  `status` enum('SUCCESS','FAILED','PENDING') DEFAULT 'SUCCESS',
  `error_message` text COMMENT 'Error details if action failed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_table_record` (`user_id`,`table_name`,`record_id`),
  CONSTRAINT `users_actions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_actions`
--

LOCK TABLES `users_actions` WRITE;
/*!40000 ALTER TABLE `users_actions` DISABLE KEYS */;
INSERT INTO `users_actions` VALUES (1,1,'CREATE','customers',15,'Created new customer: Aloo ali',NULL,'{\"name\": \"Aloo ali\", \"note\": \"ta3aaal\", \"phone\": \"07701999191\", \"address\": \"Baghdad\", \"created_by\": 1, \"free_trial\": \"freee ya walad\", \"start_date\": \"2025-05-24\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 11:29:48'),(2,1,'CREATE','customers',16,'Created new customer: Aloo bayroot',NULL,'{\"name\": \"Aloo bayroot\", \"note\": \"best\", \"phone\": \"192981293198\", \"address\": \"alooo\", \"created_by\": 1, \"free_trial\": \"yawall\", \"start_date\": \"2025-05-24\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 11:30:29'),(3,1,'CREATE','0',17,'Created new customer: wawee',NULL,'{\"name\": \"wawee\", \"note\": null, \"phone\": \"08889998888\", \"address\": \"wawee\", \"created_by\": 1, \"free_trial\": \"free\", \"start_date\": \"2025-05-24\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 11:38:05'),(4,1,'CREATE','0',20,'Created new customer: new customer',NULL,'{\"name\": \"new customer\", \"note\": \"sdkjf\", \"phone\": \"2348324234\", \"address\": \"ad;lkfj;\", \"created_by\": 1, \"free_trial\": \"alskdjf\", \"start_date\": \"2025-05-24\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 11:41:18'),(5,1,'UPDATE','0',20,'Updated customer: new cccc','{\"id\": 20, \"name\": \"new customer\", \"note\": \"0\", \"phone\": \"2348324234\", \"address\": \"ad;lkfj;\", \"createdBy\": 1, \"freeTrial\": \"alskdjf\", \"startDate\": \"2025-05-24\", \"created_at\": \"2025-05-24 14:41:18\", \"updated_at\": \"2025-05-24 14:41:18\"}','{\"name\": \"new cccc\", \"note\": null, \"phone\": \"23483242343\", \"address\": \"ad;lkfj;\", \"free_trial\": \"alskdjf\", \"start_date\": \"2025-05-24\", \"updated_by\": 1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 11:52:04'),(6,1,'UPDATE','0',19,'Updated customer: new customerrr','{\"id\": 19, \"name\": \"new customer\", \"note\": \"0\", \"phone\": \"2348324234\", \"address\": \"ad;lkfj;\", \"createdBy\": 1, \"freeTrial\": \"alskdjf\", \"startDate\": \"2025-05-24\", \"created_at\": \"2025-05-24 14:40:08\", \"updated_at\": \"2025-05-24 14:40:08\"}','{\"name\": \"new customerrr\", \"note\": null, \"phone\": \"2348324234\", \"address\": \"ad;lkfj;\", \"free_trial\": \"alskdjf\", \"start_date\": \"2025-05-24\", \"updated_by\": 1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 11:52:54'),(7,2,'DELETE','0',20,'Deleted customer: new cccc','{\"id\": 20, \"name\": \"new cccc\", \"note\": null, \"address\": \"ad;lkfj;\", \"created_at\": \"2025-05-24 14:41:18\", \"free_trial\": \"alskdjf\", \"start_date\": \"2025-05-24\", \"updated_at\": \"2025-05-24 14:52:04\", \"phone_number\": \"23483242343\", \"created_by_user\": 1}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 12:06:13'),(8,1,'CREATE','0',21,'Created new customer: wewa',NULL,'{\"name\": \"wewa\", \"note\": \"lfkaj\", \"phone\": \"39298\", \"address\": \"wflkejf\", \"created_by\": 1, \"free_trial\": \"sdlkfj\", \"start_date\": \"2025-05-24\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 12:07:26'),(9,2,'CREATE','0',11,'Created new contract for customer ID: 17',NULL,'{\"note\": \"\", \"payment\": 1000, \"created_by\": 2, \"start_date\": \"2025-06-27\", \"customer_id\": 17, \"expire_date\": \"2025-08-30\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 15:47:51'),(10,2,'UPDATE','0',11,'Updated contract ID: 11','{\"note\": \"\", \"payment\": 1000, \"start_date\": \"2025-06-27\", \"customer_id\": 17, \"expire_date\": \"2025-08-30\", \"created_by_user\": 2}','{\"note\": \"\", \"payment\": 100022, \"start_date\": \"2025-06-27\", \"customer_id\": 17, \"expire_date\": \"2025-08-30\", \"created_by_user\": 2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 16:42:00'),(11,1,'DELETE','0',10,'Deleted contract ID: 10',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 16:58:27'),(12,1,'DELETE','0',11,'Deleted contract ID: 11',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:00:05'),(13,1,'DELETE','0',7,'Deleted contract ID: 7',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:00:23'),(14,2,'CREATE','0',12,'Created new contract for customer ID: 19',NULL,'{\"note\": \"sff\", \"payment\": 2223, \"created_by\": 2, \"start_date\": \"2025-05-08\", \"customer_id\": 19, \"expire_date\": \"2025-06-07\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:03:13'),(15,1,'DELETE','0',12,'Deleted contract ID: 12','{\"note\": \"sff\", \"payment\": 2223, \"start_date\": \"2025-05-08\", \"customer_id\": 19, \"expire_date\": \"2025-06-07\", \"created_by_user\": 2}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:03:23'),(16,2,'CREATE','0',13,'Created new contract for customer ID: 18',NULL,'{\"note\": \"2fe\", \"payment\": 223232, \"created_by\": 2, \"start_date\": \"2025-05-10\", \"customer_id\": 18, \"expire_date\": \"2025-05-31\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:13:51'),(17,2,'CREATE','0',NULL,'Created new invoice for customer: CUID2',NULL,'{\"status\": \"Done\", \"customer_id\": 9, \"items_count\": 2, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"CUID2\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:24:07'),(18,2,'UPDATE','0',10,'Updated invoice for customer: new customer',NULL,'{\"status\": \"Done\", \"customer_id\": 18, \"items_count\": 2, \"invoice_date\": \"2025-05-28\", \"customer_name\": \"new customer\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:28:26'),(19,2,'UPDATE','0',11,'Updated invoice for customer: CUID2',NULL,'{\"status\": \"Done\", \"customer_id\": 9, \"items_count\": 3, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"CUID2\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:28:38'),(20,2,'UPDATE','0',11,'Updated invoice for customer: wewa',NULL,'{\"status\": \"Done\", \"customer_id\": 21, \"items_count\": 4, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"wewa\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:33:20'),(21,2,'UPDATE','0',9,'Updated invoice for customer: Ali Abdullah','{\"items\": [{\"id\": 39, \"note\": \"\", \"price\": 20, \"quantity\": 1, \"subtotal\": 20, \"item_name\": \"adf\", \"invoice_id\": 9}, {\"id\": 40, \"note\": \"\", \"price\": 3, \"quantity\": 4, \"subtotal\": 12, \"item_name\": \"vv\", \"invoice_id\": 9}], \"status\": \"Done\", \"customer_id\": 19, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"new customerrr\"}','{\"status\": \"Done\", \"customer_id\": 12, \"items_count\": 3, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"Ali Abdullah\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:38:32'),(22,2,'UPDATE','0',9,'Updated invoice for customer: Ali Abdullah','{\"items\": [{\"id\": 39, \"note\": \"\", \"price\": 20, \"quantity\": 1, \"subtotal\": 20, \"item_name\": \"adf\", \"invoice_id\": 9}, {\"id\": 40, \"note\": \"\", \"price\": 3, \"quantity\": 4, \"subtotal\": 12, \"item_name\": \"vv\", \"invoice_id\": 9}], \"status\": \"Done\", \"customer_id\": 19, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"new customerrr\"}','{\"status\": \"Done\", \"customer_id\": 12, \"items_count\": 3, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"Ali Abdullah\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:38:32'),(23,2,'UPDATE','0',10,'Updated invoice for customer: new customer','{\"items\": [{\"id\": 45, \"note\": \"\", \"price\": 3, \"quantity\": 1, \"subtotal\": 3, \"item_name\": \"df\", \"invoice_id\": 10}, {\"id\": 46, \"note\": \"\", \"price\": 3, \"quantity\": 3, \"subtotal\": 9, \"item_name\": \"1dff\", \"invoice_id\": 10}], \"status\": \"Done\", \"customer_id\": 18, \"invoice_date\": \"2025-05-28\", \"customer_name\": \"new customer\"}','{\"status\": \"Done\", \"customer_id\": 18, \"items_count\": 2, \"invoice_date\": \"2025-05-28\", \"customer_name\": \"new customer\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:38:57'),(24,2,'UPDATE','0',10,'Updated invoice for customer: new customer','{\"items\": [{\"id\": 45, \"note\": \"\", \"price\": 3, \"quantity\": 1, \"subtotal\": 3, \"item_name\": \"df\", \"invoice_id\": 10}, {\"id\": 46, \"note\": \"\", \"price\": 3, \"quantity\": 3, \"subtotal\": 9, \"item_name\": \"1dff\", \"invoice_id\": 10}], \"status\": \"Done\", \"customer_id\": 18, \"invoice_date\": \"2025-05-28\", \"customer_name\": \"new customer\"}','{\"status\": \"Done\", \"customer_id\": 18, \"items_count\": 2, \"invoice_date\": \"2025-05-28\", \"customer_name\": \"new customer\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:38:57'),(25,2,'DELETE','0',10,'Deleted invoice: new customer','{\"id\": 10, \"inv_date\": \"2025-05-28\", \"customer_id\": 18, \"done_undone\": \"Done\", \"customer_name\": \"new customer\", \"created_by_user\": 2}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:44:33'),(26,2,'DELETE','0',11,'Deleted invoice: wewa','{\"id\": 11, \"inv_date\": \"2025-05-24\", \"customer_id\": 21, \"done_undone\": \"Done\", \"customer_name\": \"wewa\", \"created_by_user\": 2}',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 17:44:39'),(27,1,'CREATE','0',NULL,'Created new invoice for customer: wewa',NULL,'{\"status\": \"Undone\", \"customer_id\": 21, \"items_count\": 2, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"wewa\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 18:43:40'),(28,1,'UPDATE','0',12,'Updated invoice for customer: wewa','{\"items\": [{\"id\": 59, \"note\": \"\", \"price\": 12, \"quantity\": 5, \"subtotal\": 60, \"item_name\": \"item\", \"invoice_id\": 12}, {\"id\": 60, \"note\": \"\", \"price\": 33, \"quantity\": 2, \"subtotal\": 66, \"item_name\": \"item2\", \"invoice_id\": 12}], \"status\": \"Undone\", \"customer_id\": 21, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"wewa\"}','{\"status\": \"Done\", \"customer_id\": 21, \"items_count\": 2, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"wewa\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 18:44:47'),(29,1,'UPDATE','0',12,'Updated invoice for customer: wewa','{\"items\": [{\"id\": 59, \"note\": \"\", \"price\": 12, \"quantity\": 5, \"subtotal\": 60, \"item_name\": \"item\", \"invoice_id\": 12}, {\"id\": 60, \"note\": \"\", \"price\": 33, \"quantity\": 2, \"subtotal\": 66, \"item_name\": \"item2\", \"invoice_id\": 12}], \"status\": \"Undone\", \"customer_id\": 21, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"wewa\"}','{\"status\": \"Done\", \"customer_id\": 21, \"items_count\": 2, \"invoice_date\": \"2025-05-24\", \"customer_name\": \"wewa\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',NULL,'SUCCESS',NULL,'2025-05-24 18:44:47');
/*!40000 ALTER TABLE `users_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ca'
--
/*!50003 DROP PROCEDURE IF EXISTS `insert_invoice_with_items` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `insert_invoice_with_items`(
    IN p_user_id INT,
    IN p_customer_id INT,
    IN p_customer_name VARCHAR(255),
    IN p_inv_date DATE,
    IN p_done_undone VARCHAR(10),
    IN p_items_json JSON
)
BEGIN
    -- ✅ Declare variables first (at the top)
    DECLARE v_invoice_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE items_count INT;

    -- ✅ Set up invoice insertion
    INSERT INTO invoices (created_by_user, customer_id,customer_name, inv_date, done_undone)
    VALUES (p_user_id, p_customer_id,p_customer_name, p_inv_date, p_done_undone);

    -- ✅ Get the last inserted invoice ID
    SET v_invoice_id = LAST_INSERT_ID();

    -- ✅ Calculate number of items
    SET items_count = JSON_LENGTH(p_items_json);

    -- ✅ Loop through each item in the JSON array
    WHILE i < items_count DO
        INSERT INTO invoice_item (
            invoice_id, 
            item_name, 
            price, 
            quantity, 
            subtotal, 
            note
        )
        VALUES (
            v_invoice_id,
            JSON_UNQUOTE(JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].item_name'))),
            JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].price')),
            JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].quantity')),
            JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].subtotal')),
            JSON_UNQUOTE(JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].note')))
        );

        SET i = i + 1;
    END WHILE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-24 21:56:57
