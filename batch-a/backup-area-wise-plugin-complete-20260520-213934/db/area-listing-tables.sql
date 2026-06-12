/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.10-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: sql_admin_homes
-- ------------------------------------------------------
-- Server version	10.11.10-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `area_listing_states`
--

DROP TABLE IF EXISTS `area_listing_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `country` varchar(191) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_listing_states_country_slug_unique` (`country`,`slug`),
  KEY `area_listing_states_slug_index` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_states`
--

LOCK TABLES `area_listing_states` WRITE;
/*!40000 ALTER TABLE `area_listing_states` DISABLE KEYS */;
INSERT INTO `area_listing_states` VALUES
(1,'Rajasthan','rajasthan','India',0,1,'2026-05-14 20:18:10','2026-05-14 20:18:10'),
(2,'Test State 6A0De0F383551','test-state-6a0de0f383551','India',0,1,'2026-05-20 16:27:32','2026-05-20 16:27:32'),
(3,'Test State 6A0De1F097Ce0','test-state-6a0de1f097ce0','India',0,1,'2026-05-20 16:31:45','2026-05-20 16:31:45'),
(6,'Gujarat','gujarat','India',0,1,'2026-05-20 16:52:51','2026-05-20 16:52:51'),
(7,'Delhi','delhi','India',0,1,'2026-05-20 16:52:51','2026-05-20 16:52:51');
/*!40000 ALTER TABLE `area_listing_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_cities`
--

DROP TABLE IF EXISTS `area_listing_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `normalized_name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `state` varchar(191) NOT NULL DEFAULT '',
  `country` varchar(191) NOT NULL DEFAULT 'India',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_listing_cities_normalized_state_country_unique` (`normalized_name`,`state`,`country`),
  UNIQUE KEY `area_listing_cities_state_id_slug_unique` (`state_id`,`slug`),
  KEY `area_listing_cities_state_id_index` (`state_id`),
  KEY `area_listing_cities_slug_index` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_cities`
--

LOCK TABLES `area_listing_cities` WRITE;
/*!40000 ALTER TABLE `area_listing_cities` DISABLE KEYS */;
INSERT INTO `area_listing_cities` VALUES
(1,1,'Barmer','barmer','barmer','Rajasthan','India',0,1,'2026-05-14 20:18:10','2026-05-14 20:18:10'),
(7,6,'Bhuj','bhuj','bhuj','Gujarat','India',0,1,'2026-05-20 16:52:51','2026-05-20 16:52:51'),
(8,6,'ઢોંસા','ઢોંસા','','Gujarat','India',0,1,'2026-05-20 16:52:51','2026-05-20 16:52:51'),
(9,7,'New Delhi','new delhi','new-delhi','Delhi','India',0,1,'2026-05-20 16:52:51','2026-05-20 16:52:51'),
(10,1,'Udaipur','udaipur','udaipur','Rajasthan','India',0,1,'2026-05-20 21:15:23','2026-05-20 21:15:23'),
(11,1,'Jaipur','jaipur','jaipur','Rajasthan','India',0,1,'2026-05-20 21:29:36','2026-05-20 21:29:36');
/*!40000 ALTER TABLE `area_listing_cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_areas`
--

DROP TABLE IF EXISTS `area_listing_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_areas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `city_name` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `normalized_name` varchar(191) DEFAULT NULL,
  `slug` varchar(191) NOT NULL,
  `center_lat` decimal(10,7) DEFAULT NULL,
  `center_lng` decimal(10,7) DEFAULT NULL,
  `radius_meters` int(10) unsigned DEFAULT NULL,
  `polygon_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`polygon_json`)),
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `workflow_status` varchar(20) NOT NULL DEFAULT 'active',
  `archived_at` timestamp NULL DEFAULT NULL,
  `seo_title` varchar(191) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_listing_areas_city_state_name_unique` (`city`,`state`,`name`),
  UNIQUE KEY `area_listing_areas_city_state_country_normalized_unique` (`city`,`state`,`country`,`normalized_name`),
  KEY `area_listing_areas_city_index` (`city`),
  KEY `area_listing_areas_state_index` (`state`),
  KEY `area_listing_areas_country_index` (`country`),
  KEY `area_listing_areas_name_index` (`name`),
  KEY `area_listing_areas_slug_index` (`slug`),
  KEY `area_listing_areas_status_index` (`status`),
  KEY `area_listing_areas_state_id_index` (`state_id`),
  KEY `area_listing_areas_city_id_index` (`city_id`),
  KEY `area_listing_areas_workflow_status_index` (`workflow_status`),
  KEY `area_listing_areas_city_name_index` (`city_name`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_areas`
--

LOCK TABLES `area_listing_areas` WRITE;
/*!40000 ALTER TABLE `area_listing_areas` DISABLE KEYS */;
INSERT INTO `area_listing_areas` VALUES
(2,1,1,'Barmer','Barmer','Rajasthan','India','Rai Colony Road','rai colony road','rai-colony-road',NULL,NULL,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-14 08:36:27','2026-05-14 20:18:10'),
(3,1,1,'Barmer','Barmer','Rajasthan','India','Mahaveer Nagar','mahaveer nagar','mahaveer-nagar',NULL,NULL,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-14 18:22:52','2026-05-14 20:18:10'),
(42,1,1,'Barmer','Barmer','Rajasthan','India','Baldev Nagar','baldev nagar','baldev-nagar',25.7521467,71.3966865,NULL,NULL,0,1,'active','2026-05-20 20:50:21',NULL,NULL,'2026-05-20 19:51:29','2026-05-20 20:51:11'),
(43,NULL,NULL,'Udaipur','Udaipur','Rajasthan','India','Maharana Udai Singh Market','maharana udai singh market','maharana-udai-singh-market',NULL,NULL,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-20 21:14:29','2026-05-20 21:14:29'),
(44,1,11,'Jaipur','Jaipur','Rajasthan','India','Govind Nagar','govind nagar','govind-nagar',26.9124336,75.7872709,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-20 21:29:36','2026-05-20 21:29:36');
/*!40000 ALTER TABLE `area_listing_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_sub_areas`
--

DROP TABLE IF EXISTS `area_listing_sub_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_sub_areas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `area_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `normalized_name` varchar(191) DEFAULT NULL,
  `slug` varchar(191) NOT NULL,
  `center_lat` decimal(10,7) DEFAULT NULL,
  `center_lng` decimal(10,7) DEFAULT NULL,
  `radius_meters` int(10) unsigned DEFAULT NULL,
  `polygon_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`polygon_json`)),
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `workflow_status` varchar(20) NOT NULL DEFAULT 'active',
  `archived_at` timestamp NULL DEFAULT NULL,
  `seo_title` varchar(191) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_listing_sub_areas_area_slug_unique` (`area_id`,`slug`),
  UNIQUE KEY `area_listing_sub_areas_area_normalized_unique` (`area_id`,`normalized_name`),
  KEY `area_listing_sub_areas_name_index` (`name`),
  KEY `area_listing_sub_areas_slug_index` (`slug`),
  KEY `area_listing_sub_areas_status_index` (`status`),
  KEY `area_listing_sub_areas_workflow_status_index` (`workflow_status`),
  CONSTRAINT `area_listing_sub_areas_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `area_listing_areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_sub_areas`
--

LOCK TABLES `area_listing_sub_areas` WRITE;
/*!40000 ALTER TABLE `area_listing_sub_areas` DISABLE KEYS */;
INSERT INTO `area_listing_sub_areas` VALUES
(2,2,'Bariyon Ka Vas','bariyon ka vas','bariyon-ka-vas',NULL,NULL,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-14 08:36:27','2026-05-17 12:14:11'),
(27,42,'Nichla Was','nichla was','nichla-was',25.7521467,71.3966865,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-20 20:53:49','2026-05-20 20:53:49'),
(28,44,'Chungi','chungi','chungi',26.9124336,75.7872709,NULL,NULL,0,1,'active',NULL,NULL,NULL,'2026-05-20 21:29:45','2026-05-20 21:29:45');
/*!40000 ALTER TABLE `area_listing_sub_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_property_locations`
--

DROP TABLE IF EXISTS `area_listing_property_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_property_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `area_id` bigint(20) unsigned DEFAULT NULL,
  `sub_area_id` bigint(20) unsigned DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `area_name` varchar(191) DEFAULT NULL,
  `sub_area_name` varchar(191) DEFAULT NULL,
  `detected_area_name` varchar(191) DEFAULT NULL,
  `detected_sub_area_name` varchar(191) DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `manual_address` text DEFAULT NULL,
  `display_address` varchar(191) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_source` varchar(191) NOT NULL DEFAULT 'manual',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(191) NOT NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_listing_property_locations_property_id_unique` (`property_id`),
  KEY `area_listing_property_locations_sub_area_id_foreign` (`sub_area_id`),
  KEY `area_listing_property_locations_area_id_sub_area_id_index` (`area_id`,`sub_area_id`),
  KEY `area_listing_property_locations_state_id_index` (`state_id`),
  KEY `area_listing_property_locations_city_id_index` (`city_id`),
  CONSTRAINT `area_listing_property_locations_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `area_listing_areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `area_listing_property_locations_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `propertys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_listing_property_locations_sub_area_id_foreign` FOREIGN KEY (`sub_area_id`) REFERENCES `area_listing_sub_areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_property_locations`
--

LOCK TABLES `area_listing_property_locations` WRITE;
/*!40000 ALTER TABLE `area_listing_property_locations` DISABLE KEYS */;
INSERT INTO `area_listing_property_locations` VALUES
(1,11,1,10,43,NULL,'Rajasthan','Udaipur','Maharana Udai Singh Market',NULL,'Maharana Udai Singh Market',NULL,'HPG2+86W, Maharana Udai Singh Market, Ganesh Ghati, Udaipur, Rajasthan 313001, India','Opp lalit shop gaur chatrawas','Maharana Udai Singh Market, Udaipur, Rajasthan',24.5761000,73.7005000,'google',0,NULL,15,'google','2026-05-20 21:15:23','2026-05-20 21:20:12'),
(2,12,1,1,2,2,'Rajasthan','Barmer','Rai Colony Road','Bariyon Ka Vas','Rai Colony Road','Bariyon Ka Vas','new Barmer, Rajasthan 344001, India','rai colony krishna nagar barmer','Bariyon Ka Vas, Rai Colony Road, Barmer, Rajasthan',25.7521467,71.3966865,'google',0,NULL,1,'google','2026-05-20 11:45:55','2026-05-20 11:45:55'),
(8,13,1,1,42,27,'Rajasthan','Barmer','Baldev Nagar','Nichla Was','Baldev Nagar','Nichla Was','krishna Nagar, Barmer, Rajasthan 344001, India','opp lalit store gaur chatravas','Nichla Was, Baldev Nagar, Barmer, Rajasthan',25.7514820,71.3924730,'google',0,NULL,15,'google','2026-05-20 21:07:03','2026-05-20 21:07:03'),
(19,1,6,7,NULL,NULL,'Gujarat','Bhuj',NULL,NULL,NULL,NULL,NULL,NULL,'Bhuj, Gujarat',23.1831890,69.6840750,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(20,2,6,7,NULL,NULL,'Gujarat','Bhuj',NULL,NULL,NULL,NULL,NULL,NULL,'Bhuj, Gujarat',23.1930230,69.7351100,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(21,3,6,8,NULL,NULL,'Gujarat','ઢોંસા',NULL,NULL,NULL,NULL,NULL,NULL,'ઢોંસા, Gujarat',23.3169170,69.6198280,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(22,4,1,1,NULL,NULL,'Rajasthan','Barmer',NULL,NULL,NULL,NULL,NULL,NULL,'Barmer, Rajasthan',25.7522057,71.3865196,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(23,5,6,8,NULL,NULL,'Gujarat','કુરબાઈ',NULL,NULL,NULL,NULL,NULL,NULL,'કુરબાઈ, Gujarat',23.1973620,69.6090700,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(24,6,1,1,42,27,'Rajasthan','Barmer','Baldev Nagar','Nichla Was','Baldev Nagar','Nichla Was','Barmer, Rajasthan 344001, India','Barmer Rajasthan','Nichla Was, Baldev Nagar, Barmer, Rajasthan',25.7521467,71.3966865,'manual',0,NULL,1,'manual','2026-05-20 20:53:54','2026-05-20 20:53:54'),
(25,7,6,8,NULL,NULL,'Gujarat','પાલારા',NULL,NULL,NULL,NULL,NULL,NULL,'પાલારા, Gujarat',23.2989810,69.6604120,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(26,8,1,1,NULL,NULL,'Rajasthan','Barmer',NULL,NULL,NULL,NULL,NULL,NULL,'Barmer, Rajasthan',25.7461698,71.3971774,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(27,9,7,9,NULL,NULL,'Delhi','New Delhi',NULL,NULL,NULL,NULL,NULL,NULL,'New Delhi, Delhi',28.7577147,77.1952590,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51'),
(28,10,7,9,NULL,NULL,'Delhi','New Delhi',NULL,NULL,NULL,NULL,NULL,NULL,'New Delhi, Delhi',28.7801407,77.1794183,'manual',0,NULL,NULL,'manual','2026-05-20 16:52:51','2026-05-20 16:52:51');
/*!40000 ALTER TABLE `area_listing_property_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_project_locations`
--

DROP TABLE IF EXISTS `area_listing_project_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_project_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `area_id` bigint(20) unsigned DEFAULT NULL,
  `sub_area_id` bigint(20) unsigned DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `area_name` varchar(191) DEFAULT NULL,
  `sub_area_name` varchar(191) DEFAULT NULL,
  `detected_area_name` varchar(191) DEFAULT NULL,
  `detected_sub_area_name` varchar(191) DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `manual_address` text DEFAULT NULL,
  `display_address` varchar(191) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_source` varchar(191) NOT NULL DEFAULT 'manual',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(191) NOT NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_listing_project_locations_project_id_unique` (`project_id`),
  KEY `area_listing_project_locations_sub_area_id_foreign` (`sub_area_id`),
  KEY `area_listing_project_locations_area_id_sub_area_id_index` (`area_id`,`sub_area_id`),
  KEY `area_listing_project_locations_state_id_index` (`state_id`),
  KEY `area_listing_project_locations_city_id_index` (`city_id`),
  CONSTRAINT `area_listing_project_locations_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `area_listing_areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `area_listing_project_locations_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_listing_project_locations_sub_area_id_foreign` FOREIGN KEY (`sub_area_id`) REFERENCES `area_listing_sub_areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_project_locations`
--

LOCK TABLES `area_listing_project_locations` WRITE;
/*!40000 ALTER TABLE `area_listing_project_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `area_listing_project_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_suggestions`
--

DROP TABLE IF EXISTS `area_listing_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `area_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `normalized_name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `suggested_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `area_listing_suggestions_type_status` (`type`,`status`),
  KEY `area_listing_suggestions_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_suggestions`
--

LOCK TABLES `area_listing_suggestions` WRITE;
/*!40000 ALTER TABLE `area_listing_suggestions` DISABLE KEYS */;
INSERT INTO `area_listing_suggestions` VALUES
(29,'sub_area',42,'Nichla Was','nichla was','nichla-was',NULL,NULL,'India',15,'approved',NULL,1,'2026-05-20 19:52:38','2026-05-20 19:52:21','2026-05-20 19:52:38'),
(30,'sub_area',42,'Taliyo Ka Was','taliyo ka was','taliyo-ka-was',NULL,NULL,'India',15,'merged',NULL,1,'2026-05-20 20:58:58','2026-05-20 20:58:30','2026-05-20 20:58:58'),
(31,'area',NULL,'Maharana Udai Singh Market','maharana udai singh market','maharana-udai-singh-market','Udaipur','Rajasthan','India',15,'approved',NULL,1,'2026-05-20 21:14:29','2026-05-20 21:14:13','2026-05-20 21:14:29');
/*!40000 ALTER TABLE `area_listing_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `area_listing_merge_audits`
--

DROP TABLE IF EXISTS `area_listing_merge_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `area_listing_merge_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(20) NOT NULL,
  `old_id` bigint(20) unsigned NOT NULL,
  `new_id` bigint(20) unsigned NOT NULL,
  `affected_properties` int(10) unsigned NOT NULL DEFAULT 0,
  `affected_projects` int(10) unsigned NOT NULL DEFAULT 0,
  `admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `area_listing_merge_audit_lookup` (`entity_type`,`old_id`,`new_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_listing_merge_audits`
--

LOCK TABLES `area_listing_merge_audits` WRITE;
/*!40000 ALTER TABLE `area_listing_merge_audits` DISABLE KEYS */;
INSERT INTO `area_listing_merge_audits` VALUES
(5,'sub_area',30,27,0,0,1,'{\"source\":\"suggestion_merge\",\"suggested_name\":\"Taliyo Ka Was\",\"review_note\":null,\"merged_at\":\"2026-05-20 20:58:58\"}','2026-05-20 20:58:58','2026-05-20 20:58:58');
/*!40000 ALTER TABLE `area_listing_merge_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `propertys`
--

DROP TABLE IF EXISTS `propertys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `propertys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` varchar(191) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` longtext NOT NULL,
  `address` varchar(191) NOT NULL,
  `client_address` varchar(191) NOT NULL,
  `propery_type` tinyint(4) NOT NULL COMMENT '0:Sell 1:Rent',
  `price` bigint(20) NOT NULL,
  `post_type` varchar(191) DEFAULT NULL COMMENT '0 :admin 1:customer',
  `city` varchar(191) DEFAULT 'Kutch',
  `country` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `title_image` text NOT NULL,
  `three_d_image` varchar(191) NOT NULL,
  `video_link` varchar(191) DEFAULT NULL,
  `video_type` tinyint(4) DEFAULT NULL COMMENT '0=custom,1=youtube,2=vimeo',
  `latitude` varchar(191) NOT NULL,
  `longitude` varchar(191) NOT NULL,
  `added_by` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT ' 0: Deactive 1: Active',
  `request_status` enum('approved','rejected','pending','draft') NOT NULL DEFAULT 'pending',
  `expiry_date` timestamp NULL DEFAULT NULL,
  `edit_reason` text DEFAULT NULL,
  `total_click` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rentduration` varchar(191) DEFAULT NULL,
  `slug_id` varchar(191) NOT NULL,
  `meta_title` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `meta_image` varchar(191) DEFAULT NULL,
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `role_context` enum('user','agent','general') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `propertys_slug_id_unique` (`slug_id`),
  KEY `propertys_role_context_index` (`role_context`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `propertys`
--

LOCK TABLES `propertys` WRITE;
/*!40000 ALTER TABLE `propertys` DISABLE KEYS */;
INSERT INTO `propertys` VALUES
(1,'1',NULL,'Luxury Sunset Villa','Experience luxury living in this stunning villa with breathtaking sunset views. Features modern architecture, spacious rooms, and premium finishes throughout.','Jadura Road, Bhuj, Bhuj Taluka, Kutch, Gujarat, 370001, India','Barmer Rajasthan',0,2500000,'0','Bhuj','India','Gujarat','1775153852-luxury-sunset-villa.png','','',NULL,'23.183189','69.684075',0,1,'approved',NULL,NULL,4,'2026-04-02 18:17:32','2026-05-20 05:01:59',NULL,'luxury-sunset-villa','Luxury Sunset Villa - Premium Property','Experience luxury living in this stunning villa with breathtaking sunset views.','villa, luxury, sunset, modern, premium',NULL,0,1,'agent'),
(2,'2',NULL,'Modern Family House','Perfect family home with spacious rooms, beautiful garden, and located in a peaceful neighborhood. Close to schools and shopping centers.','Bhuj, Bhuj Taluka, Kutch, Gujarat, 370001, India','Barmer Rajasthan',0,850000,'0','Bhuj','India','Gujarat','1775153853-modern-family-house.png','','',NULL,'23.193023','69.73511',0,1,'approved',NULL,NULL,5,'2026-04-02 18:17:33','2026-05-20 04:26:14',NULL,'modern-family-house','Modern Family House - Comfortable Living','Perfect family home with spacious rooms and beautiful garden.','house, family, modern, garden, suburban',NULL,0,1,'agent'),
(3,'3',NULL,'Downtown Luxury Apartment','Stylish apartment in the heart of downtown with stunning city views. Walking distance to restaurants, shops, and entertainment.','Makanpar- Ratiya, ઢોંસા, Bhuj Taluka, Kutch, Gujarat, 370030, India','Barmer Rajasthan',1,3500,'0','ઢોંસા','India','Gujarat','1775153853-downtown-luxury-apartment.png','','',NULL,'23.316917','69.619828',0,1,'approved',NULL,NULL,0,'2026-04-02 18:17:34','2026-04-02 18:17:34','Monthly','downtown-luxury-apartment','Downtown Luxury Apartment - City Living','Stylish apartment in the heart of downtown with stunning city views.','apartment, downtown, luxury, city view, modern',NULL,1,1,'agent'),
(4,'4',NULL,'Prime Office Space','Modern office space in premium business district. Perfect for startups and established businesses. High-speed internet and modern amenities included.','Ray Colony, Barmer, Rajasthan 344001, India','Barmer Rajasthan',1,8000,'0','Barmer','India','Rajasthan','1775153854-prime-office-space.png','',NULL,NULL,'25.7522057','71.3865196',0,1,'approved',NULL,NULL,10,'2026-04-02 18:17:35','2026-05-20 14:19:59','Monthly','prime-office-space','Prime Office Space - Business District','Modern office space in premium business district.','commercial, office, business, modern, premium',NULL,0,1,'agent'),
(5,'5',NULL,'Residential Plot - Prime Location','Excellent investment opportunity! Prime residential plot in developing area with all utilities available. Perfect for building your dream home.','Mandvi Road, Shajanand Nagar, કુરબાઈ, Bhuj Taluka, Kutch, Gujarat, 370001, India','Barmer Rajasthan',0,450000,'0','કુરબાઈ','India','Gujarat','1775153855-residential-plot-prime-location.png','','',NULL,'23.197362','69.60907',0,1,'approved',NULL,NULL,5,'2026-04-02 18:17:35','2026-05-20 04:57:22',NULL,'residential-plot-prime-location','Residential Plot - Prime Location','Prime residential plot in developing area with all utilities available.','plot, land, residential, investment, development',NULL,0,1,'agent'),
(6,'1',NULL,'Beachfront Paradise Villa','Wake up to ocean views every day! This magnificent beachfront villa offers direct beach access, infinity pool, and luxurious amenities.','Barmer, Rajasthan 344001, India','Barmer Rajasthan',0,3200000,'0','Barmer','India','Rajasthan','1775153856-beachfront-paradise-villa.jpg','',NULL,NULL,'25.7521467','71.3966865',0,1,'approved',NULL,NULL,0,'2026-04-02 18:17:36','2026-05-20 20:45:57',NULL,'beachfront-paradise-villa','Beachfront Paradise Villa - Ocean Views','Magnificent beachfront villa with direct beach access and infinity pool.','villa, beachfront, ocean, luxury, paradise',NULL,1,1,'agent'),
(7,'2',NULL,'Cozy Cottage House','Charming cottage-style house perfect for small families or couples. Features a beautiful garden, fireplace, and peaceful surroundings.','પાલારા, Bhuj Taluka, Kutch, Gujarat, 370001, India','Barmer Rajasthan',1,2200,'0','પાલારા','India','Gujarat','1775153856-cozy-cottage-house.png','','',NULL,'23.298981','69.660412',0,1,'approved',NULL,NULL,3,'2026-04-02 18:17:36','2026-05-18 06:07:16','Yearly','cozy-cottage-house','Cozy Cottage House - Peaceful Living','Charming cottage-style house with beautiful garden and fireplace.','house, cottage, cozy, garden, peaceful',NULL,0,1,'agent'),
(8,'3',NULL,'Modern Studio Apartment','Efficient and stylish studio apartment perfect for young professionals. Fully furnished with modern appliances and great location.','Nehru Nagar, Barmer, Rajasthan 344001, India','Barmer Rajasthan',1,1800,'0','Barmer','India','Rajasthan','1775153857-modern-studio-apartment.png','',NULL,NULL,'25.7461698','71.39717739999999',0,1,'approved',NULL,NULL,4,'2026-04-02 18:17:37','2026-05-20 15:19:22','Monthly','modern-studio-apartment','Modern Studio Apartment - Urban Living','Efficient and stylish studio apartment for young professionals.','apartment, studio, modern, urban, furnished',NULL,0,1,'agent'),
(9,'2',0,'Modern Studio Apartment','Efficient and stylish studio apartment perfect for young professionals. Fully furnished with modern appliances and great location.','Q55W+333, Conductor Colony, Burari Garhi, Burari, New Delhi, Delhi, 110084, India','New Delhi',1,2000,'0','New Delhi','India','Delhi','1777095412-1775153857-modern-studio-apartment.png','',NULL,NULL,'28.757714746679035','77.19525898311815',0,1,'approved',NULL,NULL,2,'2026-04-25 05:36:53','2026-05-18 06:10:29','Monthly','modern-studio-apartment-1','Modern Studio Apartment - Urban Living','Efficient and stylish studio apartment for young professionals.','apartment, studio, modern, urban, furnished',NULL,0,0,'agent'),
(10,'5',0,'plot','## Prime Plot for Sale in [Desirable Neighborhood Name], [City Name]\r\n\r\n**Discover your dream opportunity with this exceptional plot of land, perfectly situated in the highly sought-after [Desirable Neighborhood Name] of [City Name].** This is more than just a piece of land; it\'s a canvas for your future, offering an unparalleled chance to build your ideal home or investment property in a location that truly has it all.\r\n\r\nBoasting a generous [mention approximate size, e.g., 500 sqm] of cleared and ready-to-build space, this plot presents incredible potential. Imagine designing and constructing a residence that perfectly reflects your lifestyle, from a modern family home to a luxurious villa. The [mention any specific features of the plot itself, e.g., flat topography, mature trees, pre-approved plans if applicable] makes for seamless development.\r\n\r\nThe location is a significant draw. Nestled within [Desirable Neighborhood Name], you\'ll enjoy the perfect blend of tranquility and convenience. This vibrant community offers easy access to a wealth of amenities, including [mention 2-3 key amenities, e.g., top-rated schools, bustling shopping districts, beautiful parks, excellent public transport links]. Commuting is a breeze with [mention proximity to major roads or transport hubs]. Furthermore, the area is renowned for its [mention positive neighborhood characteristics, e.g., family-friendly atmosphere, growing infrastructure, natural beauty].\r\n\r\nDon\'t miss this rare chance to secure a prime parcel of land in one of [City Name]\'s most desirable areas. Whether you\'re a discerning homeowner looking to build your forever home or a savvy investor seeking a valuable addition to your portfolio, this plot is an opportunity not to be overlooked.\r\n\r\n**Contact us today to learn more and schedule a viewing!**','64, Keshav Nagar, Kadipur, New Delhi, Delhi, 110084, India','Barmer Rajasthan',1,100,'0','New Delhi','India','Delhi','1777095593-1775153857-modern-studio-apartment.png','',NULL,NULL,'28.78014066438572','77.17941825667292',0,1,'approved',NULL,NULL,3,'2026-04-25 05:39:54','2026-05-18 05:12:54','Daily','plot','plot','','',NULL,0,0,'agent'),
(11,'5',NULL,'plot in barmer','test','HPG2+86W, Maharana Udai Singh Market, Ganesh Ghati, Udaipur, Rajasthan 313001, India','Opp lalit shop gaur chatrawas',0,123,'1','Udaipur','India','Rajasthan','1778747787-capture.png','',NULL,NULL,'24.5761','73.7005',15,1,'approved','2026-06-13 08:36:49','test',4,'2026-05-14 08:32:52','2026-05-20 21:15:37',NULL,'plot-in-barmer',NULL,NULL,NULL,NULL,0,0,'user'),
(12,'2',NULL,'test','tesy','new Barmer, Rajasthan 344001, India','rai colony krishna nagar barmer',1,22,'1','Barmer','India','Rajasthan','1778763041-nl.png','',NULL,NULL,'25.7521467','71.3966865',15,1,'approved','2026-06-13 12:51:43','new',2,'2026-05-14 12:50:41','2026-05-20 16:19:24','Daily','test','','','',NULL,0,0,'user'),
(13,'2',NULL,'Mahaveer Nagar','2bhk fully furnish','krishna Nagar, Barmer, Rajasthan 344001, India','opp lalit store gaur chatravas',1,8000,'1','Barmer','India','Rajasthan','1779290002-sc.jpg','',NULL,NULL,'25.751482','71.392473',15,1,'approved','2026-06-19 15:13:37',NULL,2,'2026-05-20 15:13:22','2026-05-20 21:07:19','Monthly','mahaveer-nagar',NULL,NULL,NULL,NULL,0,0,'agent');
/*!40000 ALTER TABLE `propertys` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-20 21:39:34
