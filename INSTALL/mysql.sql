-- MySQL dump 10.16  Distrib 10.1.44-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: cerebrate
-- ------------------------------------------------------
-- Server version	10.1.44-MariaDB-0ubuntu0.18.04.1

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
-- Table structure for table `alignment_tags`
--

DROP TABLE IF EXISTS `alignment_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alignment_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alignment_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alignment_id` (`alignment_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `alignment_tags_ibfk_1` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`),
  CONSTRAINT `alignment_tags_ibfk_10` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `alignment_tags_ibfk_11` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`),
  CONSTRAINT `alignment_tags_ibfk_12` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `alignment_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `alignment_tags_ibfk_3` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`),
  CONSTRAINT `alignment_tags_ibfk_4` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `alignment_tags_ibfk_5` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`),
  CONSTRAINT `alignment_tags_ibfk_6` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `alignment_tags_ibfk_7` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`),
  CONSTRAINT `alignment_tags_ibfk_8` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `alignment_tags_ibfk_9` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alignments`
--

DROP TABLE IF EXISTS `alignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `individual_id` int(10) unsigned NOT NULL,
  `organisation_id` int(10) unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  PRIMARY KEY (`id`),
  KEY `individual_id` (`individual_id`),
  KEY `organisation_id` (`organisation_id`),
  CONSTRAINT `alignments_ibfk_1` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`),
  CONSTRAINT `alignments_ibfk_2` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_keys`
--

DROP TABLE IF EXISTS `auth_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authkey` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `created` int(10) unsigned NOT NULL,
  `valid_until` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `authkey` (`authkey`),
  KEY `created` (`created`),
  KEY `valid_until` (`valid_until`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `auth_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `broods`
--

DROP TABLE IF EXISTS `broods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `broods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `organisation_id` int(10) unsigned NOT NULL,
  `alignment_id` int(10) unsigned NOT NULL,
  `trusted` tinyint(1) DEFAULT NULL,
  `pull` tinyint(1) DEFAULT NULL,
  `authkey` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uuid` (`uuid`),
  KEY `name` (`name`),
  KEY `url` (`url`),
  KEY `authkey` (`authkey`),
  KEY `alignment_id` (`alignment_id`),
  CONSTRAINT `broods_ibfk_1` FOREIGN KEY (`alignment_id`) REFERENCES `alignments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `encryption_keys`
--

DROP TABLE IF EXISTS `encryption_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `encryption_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `encryption_key` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) DEFAULT NULL,
  `expires` int(10) unsigned DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `owner_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uuid` (`uuid`),
  KEY `type` (`type`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `individual_encryption_keys`
--

DROP TABLE IF EXISTS `individual_encryption_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `individual_encryption_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `individual_id` int(10) unsigned NOT NULL,
  `encryption_key_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `individual_id` (`individual_id`),
  KEY `encryption_key_id` (`encryption_key_id`),
  CONSTRAINT `individual_encryption_keys_ibfk_1` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`),
  CONSTRAINT `individual_encryption_keys_ibfk_2` FOREIGN KEY (`encryption_key_id`) REFERENCES `encryption_keys` (`id`),
  CONSTRAINT `individual_encryption_keys_ibfk_3` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`),
  CONSTRAINT `individual_encryption_keys_ibfk_4` FOREIGN KEY (`encryption_key_id`) REFERENCES `encryption_keys` (`id`),
  CONSTRAINT `individual_encryption_keys_ibfk_5` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`),
  CONSTRAINT `individual_encryption_keys_ibfk_6` FOREIGN KEY (`encryption_key_id`) REFERENCES `encryption_keys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `individuals`
--

DROP TABLE IF EXISTS `individuals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `individuals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `uuid` (`uuid`),
  KEY `email` (`email`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organisation_encryption_keys`
--

DROP TABLE IF EXISTS `organisation_encryption_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organisation_encryption_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(10) unsigned NOT NULL,
  `encryption_key_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `organisation_id` (`organisation_id`),
  KEY `encryption_key_id` (`encryption_key_id`),
  CONSTRAINT `organisation_encryption_keys_ibfk_1` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`),
  CONSTRAINT `organisation_encryption_keys_ibfk_2` FOREIGN KEY (`encryption_key_id`) REFERENCES `encryption_keys` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organisations`
--

DROP TABLE IF EXISTS `organisations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organisations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sector` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacts` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `uuid` (`uuid`),
  KEY `name` (`name`),
  KEY `url` (`url`),
  KEY `nationality` (`nationality`),
  KEY `sector` (`sector`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT NULL,
  `perm_admin` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `colour` varchar(6) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(40) CHARACTER SET ascii DEFAULT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` int(11) unsigned NOT NULL,
  `individual_id` int(11) unsigned NOT NULL,
  `disabled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uuid` (`uuid`),
  KEY `role_id` (`role_id`),
  KEY `individual_id` (`individual_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`individual_id`) REFERENCES `individuals` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-06-22 14:30:02
