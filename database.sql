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


-- Dumping database structure for box101
DROP DATABASE IF EXISTS `box101`;
CREATE DATABASE IF NOT EXISTS `box101` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `box101`;

-- Dumping structure for table box101.service_item_types
DROP TABLE IF EXISTS `service_item_types`;
CREATE TABLE IF NOT EXISTS `service_item_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT '0',
  `description` text DEFAULT NULL,
  `added_by` int(10) unsigned NOT NULL COMMENT 'Who this was added by',
  `min_time` tinyint(4) DEFAULT NULL COMMENT 'Minimum amount of time it will take. In minutes.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.service_item_types: ~0 rows (approximately)

-- Dumping structure for table box101.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(254) DEFAULT NULL,
  `first_name` tinytext NOT NULL,
  `last_name` tinytext DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.users: ~2 rows (approximately)
REPLACE INTO `users` (`id`, `email`, `first_name`, `last_name`, `nif`, `address`, `phone`, `pin`, `level`, `notes`, `active`) VALUES
	(0, 'flavioaspereira@gmail.com', 'Flavio', 'Pereira', NULL, NULL, '0000000000', 0000, 'ADMIN', NULL, 1),
	(1, 'leandro_leu96@hotmail.com', 'Leandro', 'Silva', 0, '', '0000000000', 0000, 'ADMIN', 'gay do caralho', 1);

-- Dumping structure for table box101.vehicles
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `matricula` varchar(9) NOT NULL DEFAULT '',
  `odometer` mediumint(9) NOT NULL,
  `year` year(4) DEFAULT NULL,
  `month` tinyint(2) unsigned DEFAULT NULL,
  `brand` varchar(50) NOT NULL DEFAULT '',
  `model` varchar(50) NOT NULL DEFAULT '',
  `colour` varchar(50) DEFAULT NULL,
  `trim` varchar(50) DEFAULT NULL COMMENT 'Versao',
  `notes` varchar(255) DEFAULT NULL,
  `registered_by` int(11) NOT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.vehicles: ~18 rows (approximately)
REPLACE INTO `vehicles` (`matricula`, `odometer`, `year`, `month`, `brand`, `model`, `colour`, `trim`, `notes`, `registered_by`, `registration_date`) VALUES
	('0432SC', 0, '1995', NULL, 'Honda', 'Civic', 'Preto', 'EJ2', 'Do Valério', 0, '2024-08-27 13:00:01'),
	('0496EV', 0, '1995', 2, 'Honda', 'Civic', 'Preto', 'EJ2', NULL, 0, '2024-08-27 13:00:01'),
	('1109FP', 0, '1995', 8, 'Vw', 'Golf 3', 'Pleto', 'Variant', NULL, 0, '2024-08-27 13:00:01'),
	('3308VL', 0, '2003', 8, 'Renault', 'Kangoo', 'Branca', '1.5 DCI', 'Já levou três motores', 0, '2024-08-27 13:00:01'),
	('4022AM', 0, NULL, NULL, 'Honda', 'Civic', 'Vermelho', 'EG6', 'Mito com Tecto de Abrir', 0, '2024-08-27 13:00:01'),
	('4120IL', 0, '1997', NULL, 'Honda', 'Civic', 'Cinza', 'MA8', NULL, 0, '2024-08-27 13:00:01'),
	('48AZ56', 0, '2005', 12, 'Honda', 'Civic', 'Preto', 'EP1', 'Sérgio', 0, '2024-08-27 13:00:01'),
	('5008SA', 0, '2001', 7, 'Renault', 'Clio', 'Azul', 'Fase 2', '"Farois grandes"', 0, '2024-08-27 13:00:01'),
	('6582OP', 0, '1999', 12, 'Hyundai', 'H1', 'Branco', '2500TD', 'Futuro swap PD', 0, '2024-08-27 13:00:01'),
	('6698XC', 0, '2004', 2, 'Opel', 'Combo', 'Branco', '1.7 CDTI', NULL, 0, '2024-08-27 13:00:01'),
	('6804MT', 0, '1999', NULL, 'Honda', 'Civic', 'Cinza', 'EK3', 'Swap K20', 0, '2024-08-27 13:00:01'),
	('7515MG', 0, '1998', NULL, 'Seat', 'Ibiza', 'Vermelho', '6K 1.9PD', NULL, 0, '2024-08-27 13:00:01'),
	('78MF30', 0, '2011', 0, 'Audi', 'A5', 'Preto', '2.0 TDi', NULL, 0, '2024-08-27 13:00:01'),
	('7975TC', 0, '2002', 2, 'Seat', 'Ibiza', 'Cinza Rato', '6L PD100', 'Daniel', 0, '2024-08-27 13:00:01'),
	('9165MF', 0, '1998', 11, 'Honda', 'Cr-v', 'Azul', 'B20', NULL, 0, '2024-08-27 13:00:01'),
	('9529FS', 0, '1995', NULL, 'Honda', 'Civic', 'Preto', 'EJ2', 'D15 com 160 cavalos', 0, '2024-08-27 13:00:01'),
	('9896OP', 0, '1990', 7, 'Honda', 'Crx', 'Preto', '16i16', 'Swap B16A1', 0, '2024-08-27 13:00:01'),
	('MQ6198', 0, '1990', 7, 'Honda', 'Civic', 'Azul ', 'EC9', '(Casa) Swap K20', 0, '2024-08-27 13:00:01');

-- Dumping structure for table box101.vehicle_services
DROP TABLE IF EXISTS `vehicle_services`;
CREATE TABLE IF NOT EXISTS `vehicle_services` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `matricula` varchar(9) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `starting_date` datetime DEFAULT NULL COMMENT 'When the service is supposed to start',
  `created_by` int(10) unsigned NOT NULL,
  `state` enum('PENDING','PROPOSAL','AWAITING_APPROVAL','APPROVED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Deleted?',
  `client_id` int(11) DEFAULT NULL,
  `starting_odometer` int(11) DEFAULT NULL,
  `finished_odometer` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `matricula` (`matricula`),
  CONSTRAINT `fk_matricula` FOREIGN KEY (`matricula`) REFERENCES `vehicles` (`matricula`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.vehicle_services: ~2 rows (approximately)
REPLACE INTO `vehicle_services` (`id`, `matricula`, `created_at`, `starting_date`, `created_by`, `state`, `active`, `client_id`, `starting_odometer`, `finished_odometer`) VALUES
	(1, '0432SC', '2024-12-19 23:22:40', '2024-12-19 00:00:00', 0, 'PENDING', 1, 2, NULL, NULL),
	(5, '0432SC', '2024-12-23 11:12:23', '2024-12-23 00:00:00', 0, 'PENDING', 1, 1, NULL, NULL);

-- Dumping structure for table box101.vehicle_service_items
DROP TABLE IF EXISTS `vehicle_service_items`;
CREATE TABLE IF NOT EXISTS `vehicle_service_items` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(11) unsigned NOT NULL,
  `description` text NOT NULL,
  `created_by` int(11) unsigned NOT NULL,
  `status` enum('NOT_STARTED','STARTED','PAUSED','FAILED','SUCCESS') NOT NULL DEFAULT 'NOT_STARTED',
  `start_date` datetime DEFAULT NULL,
  `start_notes` text DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `end_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `price` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `service_id` FOREIGN KEY (`service_id`) REFERENCES `vehicle_services` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.vehicle_service_items: ~2 rows (approximately)
REPLACE INTO `vehicle_service_items` (`id`, `service_id`, `description`, `created_by`, `status`, `start_date`, `start_notes`, `end_date`, `end_notes`, `created_at`, `price`) VALUES
	(1, 5, 'muda de oleo', 0, 'PAUSED', '2024-12-23 11:12:23', NULL, NULL, NULL, '2024-12-23 11:12:23', 10),
	(2, 5, 'troar filtro', 0, 'STARTED', '2024-12-23 11:12:23', NULL, NULL, NULL, '2024-12-23 11:12:23', 10);

-- Dumping structure for table box101.vehicle_service_item_tracking
DROP TABLE IF EXISTS `vehicle_service_item_tracking`;
CREATE TABLE IF NOT EXISTS `vehicle_service_item_tracking` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `service_item_id` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `service_item_id` (`service_item_id`),
  CONSTRAINT `service_item_id` FOREIGN KEY (`service_item_id`) REFERENCES `vehicle_service_items` (`id`) ON UPDATE NO ACTION,
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.vehicle_service_item_tracking: ~0 rows (approximately)

-- Dumping structure for table box101.vehicle_service_parts
DROP TABLE IF EXISTS `vehicle_service_parts`;
CREATE TABLE IF NOT EXISTS `vehicle_service_parts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the part',
  `service_id` int(11) unsigned NOT NULL COMMENT 'Reference to the associated service',
  `added_by` int(11) unsigned NOT NULL COMMENT 'User who added the part',
  `description` text NOT NULL COMMENT 'Description of the part',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When this part was added to the service',
  `customer_price` decimal(10,2) NOT NULL COMMENT 'Price charged to the customer',
  `supplier_paid` tinyint(1) DEFAULT 0 COMMENT 'Indicates if the supplier has been paid',
  `supplier_price` decimal(10,2) DEFAULT NULL COMMENT 'Price paid to the supplier',
  `supplier_discount` int(11) DEFAULT NULL COMMENT 'Discount applied by the supplier',
  `origin` varchar(255) DEFAULT NULL COMMENT 'Origin of the part',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Quantity of the part being added',
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `fk_parts_service_id` FOREIGN KEY (`service_id`) REFERENCES `vehicle_services` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_parts_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table box101.vehicle_service_parts: ~0 rows (approximately)

-- Dumping structure for trigger box101.set_default_pin
DROP TRIGGER IF EXISTS `set_default_pin`;
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER set_default_pin
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF NEW.pin IS NULL THEN
        SET NEW.pin = FLOOR(RAND() * 10000); 
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
