-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               11.4.2-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.6.0.6765
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table box101.cars
CREATE TABLE IF NOT EXISTS `cars` (
  `matricula` varchar(9) NOT NULL DEFAULT '',
  `year` year(4) DEFAULT NULL,
  `month` tinyint(2) unsigned DEFAULT NULL,
  `brand` varchar(50) NOT NULL DEFAULT '',
  `model` varchar(50) NOT NULL DEFAULT '',
  `colour` varchar(50) DEFAULT NULL,
  `trim` varchar(50) DEFAULT NULL COMMENT 'Versao',
  PRIMARY KEY (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table box101.car_services
CREATE TABLE IF NOT EXISTS `car_services` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `matricula` varchar(9) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL COMMENT 'General description of the service',
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Deleted?',
  `state` enum('STALE','CANCELLED','PENDING INTERNAL APPROVAL','PENDING APPROVAL','ACCEPTED') NOT NULL DEFAULT 'STALE',
  PRIMARY KEY (`id`),
  KEY `matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table box101.car_service_items
CREATE TABLE IF NOT EXISTS `car_service_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(11) unsigned NOT NULL,
  `service_item_type_id` int(11) unsigned NOT NULL,
  `added_by` int(11) unsigned NOT NULL,
  `status` enum('NOT STARTED','STARTED','PAUSED','FAILED','SUCCESS') NOT NULL DEFAULT 'NOT STARTED',
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `start_notes` text DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `end_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `service_id` FOREIGN KEY (`service_id`) REFERENCES `car_services` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table box101.car_service_item_tracking
CREATE TABLE IF NOT EXISTS `car_service_item_tracking` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_item_id` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `service_item_id` (`service_item_id`),
  CONSTRAINT `service_item_id` FOREIGN KEY (`service_item_id`) REFERENCES `car_service_items` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table box101.service_item_types
CREATE TABLE IF NOT EXISTS `service_item_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category` enum('EXTERIOR','MECHANICAL') NOT NULL,
  `title` varchar(50) NOT NULL DEFAULT '0',
  `description` text DEFAULT NULL,
  `added_by` int(10) unsigned DEFAULT NULL COMMENT 'Who this was added by',
  `min_time` tinyint(4) DEFAULT NULL COMMENT 'Minimum amount of time it will take. In minutes.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table box101.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(254) DEFAULT NULL,
  `first_name` tinytext NOT NULL,
  `last_name` tinytext NOT NULL,
  `nif` int(9) unsigned DEFAULT NULL COMMENT 'Número de Identificação Fiscal',
  `address` tinytext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `pin` smallint(4) unsigned zerofill DEFAULT NULL,
  `level` enum('CUSTOMER','HELP','ADMIN') DEFAULT 'CUSTOMER',
  `notes` tinytext DEFAULT NULL,
  `active` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nif` (`nif`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table box101.user_cars
CREATE TABLE IF NOT EXISTS `user_cars` (
  `matricula` varchar(9) NOT NULL DEFAULT '',
  `owner_id` int(11) unsigned NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `registration_odometer` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `notes` tinytext DEFAULT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`matricula`,`owner_id`) USING BTREE,
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `matricula` FOREIGN KEY (`matricula`) REFERENCES `cars` (`matricula`) ON UPDATE NO ACTION,
  CONSTRAINT `owner_id` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for trigger box101.set_default_pin
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER set_default_pin
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF NEW.pin IS NULL THEN
        SET NEW.pin = FLOOR(RAND() * 10000); -- Generates a random number between 0 and 9999
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
