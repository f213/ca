-- MySQL dump 10.13  Distrib 5.1.61, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: ca_master
-- ------------------------------------------------------
-- Server version	5.1.61-0+squeeze1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `CA_ADM_Edits`
--

DROP TABLE IF EXISTS `CA_ADM_Edits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_ADM_Edits` (
  `request_id` int(11) NOT NULL,
  `author` varchar(32) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `request_id` (`request_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_ADM_Edits`
--

LOCK TABLES `CA_ADM_Edits` WRITE;
/*!40000 ALTER TABLE `CA_ADM_Edits` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_ADM_Edits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_BadNumbers`
--

DROP TABLE IF EXISTS `CA_BadNumbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_BadNumbers` (
  `comp_id` int(11) NOT NULL,
  `start_number` int(11) NOT NULL,
  UNIQUE KEY `comp_id` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_BadNumbers`
--

LOCK TABLES `CA_BadNumbers` WRITE;
/*!40000 ALTER TABLE `CA_BadNumbers` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_BadNumbers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompBonusPoints`
--

DROP TABLE IF EXISTS `CA_CompBonusPoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompBonusPoints` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `points` int(10) unsigned NOT NULL,
  `reason` text NOT NULL,
  `author` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompBonusPoints`
--

LOCK TABLES `CA_CompBonusPoints` WRITE;
/*!40000 ALTER TABLE `CA_CompBonusPoints` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompBonusPoints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompBonusTime`
--

DROP TABLE IF EXISTS `CA_CompBonusTime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompBonusTime` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `reason` text NOT NULL,
  `author` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comp_id` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompBonusTime`
--

LOCK TABLES `CA_CompBonusTime` WRITE;
/*!40000 ALTER TABLE `CA_CompBonusTime` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompBonusTime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompCatTypes`
--

DROP TABLE IF EXISTS `CA_CompCatTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompCatTypes` (
  `comp_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `type` enum('legend','gps','gr-gps') NOT NULL,
  UNIQUE KEY `index` (`comp_id`,`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompCatTypes`
--

LOCK TABLES `CA_CompCatTypes` WRITE;
/*!40000 ALTER TABLE `CA_CompCatTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompCatTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompCatVar`
--

DROP TABLE IF EXISTS `CA_CompCatVar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompCatVar` (
  `comp_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `type` enum('unset','legend','gps','gr-gps','lap') NOT NULL DEFAULT 'unset',
  `max_time` bigint(20) NOT NULL,
  `max_kp` int(11) NOT NULL,
  `need_tk` enum('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`comp_id`,`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompCatVar`
--

LOCK TABLES `CA_CompCatVar` WRITE;
/*!40000 ALTER TABLE `CA_CompCatVar` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompCatVar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompDisq`
--

DROP TABLE IF EXISTS `CA_CompDisq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompDisq` (
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `type` enum('current','next','full') NOT NULL,
  `comment` tinytext NOT NULL,
  `author` tinytext NOT NULL,
  UNIQUE KEY `comp_id` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompDisq`
--

LOCK TABLES `CA_CompDisq` WRITE;
/*!40000 ALTER TABLE `CA_CompDisq` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompDisq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompGPS`
--

DROP TABLE IF EXISTS `CA_CompGPS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompGPS` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `cat_id` int(10) unsigned NOT NULL,
  `name` int(11) NOT NULL,
  `cost` int(10) unsigned NOT NULL,
  `comment` tinytext NOT NULL,
  `required` enum('yes','no') NOT NULL DEFAULT 'no',
  `active` enum('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`),
  UNIQUE KEY `point` (`comp_id`,`cat_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompGPS`
--

LOCK TABLES `CA_CompGPS` WRITE;
/*!40000 ALTER TABLE `CA_CompGPS` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompGPS` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompGPS_Points`
--

DROP TABLE IF EXISTS `CA_CompGPS_Points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompGPS_Points` (
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `point_id` int(11) NOT NULL,
  `author` tinytext NOT NULL,
  UNIQUE KEY `main` (`start_number`,`point_id`,`comp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompGPS_Points`
--

LOCK TABLES `CA_CompGPS_Points` WRITE;
/*!40000 ALTER TABLE `CA_CompGPS_Points` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompGPS_Points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompGPS_Time`
--

DROP TABLE IF EXISTS `CA_CompGPS_Time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompGPS_Time` (
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `start_time` bigint(20) unsigned NOT NULL,
  `finish_time` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `main` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompGPS_Time`
--

LOCK TABLES `CA_CompGPS_Time` WRITE;
/*!40000 ALTER TABLE `CA_CompGPS_Time` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompGPS_Time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompLegend_Results`
--

DROP TABLE IF EXISTS `CA_CompLegend_Results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompLegend_Results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `cat_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `start_time` bigint(20) unsigned NOT NULL,
  `finish_time` bigint(20) unsigned NOT NULL,
  `kps` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompLegend_Results`
--

LOCK TABLES `CA_CompLegend_Results` WRITE;
/*!40000 ALTER TABLE `CA_CompLegend_Results` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompLegend_Results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompPenalize`
--

DROP TABLE IF EXISTS `CA_CompPenalize`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompPenalize` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `reason` text NOT NULL,
  `author` text NOT NULL,
  `source` enum('normal','tk') NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompPenalize`
--

LOCK TABLES `CA_CompPenalize` WRITE;
/*!40000 ALTER TABLE `CA_CompPenalize` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompPenalize` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompRequests`
--

DROP TABLE IF EXISTS `CA_CompRequests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompRequests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `category` int(11) NOT NULL,
  `request_cabine_number` int(11) NOT NULL,
  `payd` enum('yes','no') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `payed_author` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `PilotName` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `PilotNik` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `PilotSize` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `PilotPhone` varchar(256) NOT NULL,
  `PilotCity` varchar(256) NOT NULL,
  `NavigatorName` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `NavigatorNik` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `NavigatorSize` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `NavigatorPhone` varchar(256) NOT NULL,
  `NavigatorCity` varchar(256) NOT NULL,
  `AutoBrand` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `AutoNumber` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `phone` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `city` tinytext NOT NULL,
  `club` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `WheelSize` int(11) NOT NULL,
  `RegisterDate` bigint(20) NOT NULL,
  `ip` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `source` enum('online','site','admin','forum','import') NOT NULL,
  `author` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `ApprovedBy` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `comments` text NOT NULL,
  `approved` enum('yes','no') NOT NULL DEFAULT 'no',
  `registered` enum('yes','no') NOT NULL DEFAULT 'no',
  `registered_author` tinytext NOT NULL,
  `ext_attr_enabled` enum('no','yes') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompRequests`
--

LOCK TABLES `CA_CompRequests` WRITE;
/*!40000 ALTER TABLE `CA_CompRequests` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompRequests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_CompResults`
--

DROP TABLE IF EXISTS `CA_CompResults`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_CompResults` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `cat_id` int(10) unsigned NOT NULL,
  `request_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `comment` tinytext NOT NULL,
  `portal` enum('yes','no') NOT NULL DEFAULT 'no',
  `winch` enum('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`),
  UNIQUE KEY `start_num` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_CompResults`
--

LOCK TABLES `CA_CompResults` WRITE;
/*!40000 ALTER TABLE `CA_CompResults` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_CompResults` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_Competitions`
--

DROP TABLE IF EXISTS `CA_Competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_Competitions` (
  `ID` mediumint(9) NOT NULL AUTO_INCREMENT,
  `Name` text,
  `LastRegisterDate` int(11) DEFAULT NULL,
  `Active` tinyint(1) DEFAULT NULL,
  `current` enum('yes','no') NOT NULL DEFAULT 'no',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_Competitions`
--

LOCK TABLES `CA_Competitions` WRITE;
/*!40000 ALTER TABLE `CA_Competitions` DISABLE KEYS */;
INSERT INTO `CA_Competitions` VALUES (1,'Золотая Лихорадка ОСЕНЬ 2011',1291150799,NULL,'no'),(23,'Золотая Лихорадка 2011 СУ2',NULL,NULL,'yes');
/*!40000 ALTER TABLE `CA_Competitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_GRDSU`
--

DROP TABLE IF EXISTS `CA_GRDSU`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_GRDSU` (
  `start_number` int(11) NOT NULL,
  `comp_id` int(11) NOT NULL,
  `points` bigint(20) NOT NULL,
  UNIQUE KEY `start_number` (`start_number`,`comp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_GRDSU`
--

LOCK TABLES `CA_GRDSU` WRITE;
/*!40000 ALTER TABLE `CA_GRDSU` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_GRDSU` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_Legend_Details`
--

DROP TABLE IF EXISTS `CA_Legend_Details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_Legend_Details` (
  `comp_id` int(11) NOT NULL,
  `start_number` int(11) NOT NULL,
  `point_name` int(11) NOT NULL,
  `author` tinytext NOT NULL,
  UNIQUE KEY `main` (`comp_id`,`start_number`,`point_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_Legend_Details`
--

LOCK TABLES `CA_Legend_Details` WRITE;
/*!40000 ALTER TABLE `CA_Legend_Details` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_Legend_Details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_Legend_Points`
--

DROP TABLE IF EXISTS `CA_Legend_Points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_Legend_Points` (
  `comp_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `name` int(11) NOT NULL,
  `comment` tinytext NOT NULL,
  `active` enum('yes','no') NOT NULL,
  UNIQUE KEY `main` (`comp_id`,`cat_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_Legend_Points`
--

LOCK TABLES `CA_Legend_Points` WRITE;
/*!40000 ALTER TABLE `CA_Legend_Points` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_Legend_Points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_PP`
--

DROP TABLE IF EXISTS `CA_PP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_PP` (
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `wait_time` int(10) unsigned NOT NULL DEFAULT '0',
  `pass_time` int(10) unsigned NOT NULL DEFAULT '0',
  `result` enum('yes','no') NOT NULL DEFAULT 'no',
  `when` bigint(20) NOT NULL,
  `author` tinytext NOT NULL,
  PRIMARY KEY (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_PP`
--

LOCK TABLES `CA_PP` WRITE;
/*!40000 ALTER TABLE `CA_PP` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_PP` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_Requests_ExtAttr`
--

DROP TABLE IF EXISTS `CA_Requests_ExtAttr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_Requests_ExtAttr` (
  `comp_id` int(10) unsigned NOT NULL,
  `request_id` int(10) unsigned NOT NULL,
  `attr_name` tinytext NOT NULL,
  `attr_val` tinytext NOT NULL,
  UNIQUE KEY `main` (`comp_id`,`request_id`,`attr_name`(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_Requests_ExtAttr`
--

LOCK TABLES `CA_Requests_ExtAttr` WRITE;
/*!40000 ALTER TABLE `CA_Requests_ExtAttr` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_Requests_ExtAttr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_ResultsFixed`
--

DROP TABLE IF EXISTS `CA_ResultsFixed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_ResultsFixed` (
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `place` int(10) unsigned NOT NULL,
  `active` enum('yes','no') NOT NULL,
  UNIQUE KEY `main` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_ResultsFixed`
--

LOCK TABLES `CA_ResultsFixed` WRITE;
/*!40000 ALTER TABLE `CA_ResultsFixed` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_ResultsFixed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_TK`
--

DROP TABLE IF EXISTS `CA_TK`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_TK` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `relative` enum('yes','no') NOT NULL DEFAULT 'no',
  `relative_reason` tinytext NOT NULL,
  `date` bigint(20) unsigned NOT NULL,
  `author` tinytext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `main` (`comp_id`,`start_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_TK`
--

LOCK TABLES `CA_TK` WRITE;
/*!40000 ALTER TABLE `CA_TK` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_TK` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CA_TK_Reasons`
--

DROP TABLE IF EXISTS `CA_TK_Reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CA_TK_Reasons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(10) unsigned NOT NULL,
  `min` int(10) unsigned NOT NULL,
  `reason` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CA_TK_Reasons`
--

LOCK TABLES `CA_TK_Reasons` WRITE;
/*!40000 ALTER TABLE `CA_TK_Reasons` DISABLE KEYS */;
/*!40000 ALTER TABLE `CA_TK_Reasons` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-04-07  0:25:02
