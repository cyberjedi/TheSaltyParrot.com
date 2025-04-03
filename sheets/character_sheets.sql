-- Character Sheets Database Schema
-- This uses a multi-table approach where common data is in the main table
-- and system-specific attributes are in separate tables

-- Create the main character_sheets table for common data
CREATE TABLE IF NOT EXISTS `character_sheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(128) NOT NULL COMMENT 'References Firebase UID',
  `system` varchar(50) NOT NULL COMMENT 'Game system identifier',
  `name` varchar(255) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `system_idx` (`system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the pirate_borg_sheets table for Pirate Borg specific attributes
CREATE TABLE IF NOT EXISTS `pirate_borg_sheets` (
  `sheet_id` int(11) NOT NULL COMMENT 'References character_sheets.id',
  `strength` tinyint(4) NOT NULL DEFAULT 0,
  `agility` tinyint(4) NOT NULL DEFAULT 0,
  `presence` tinyint(4) NOT NULL DEFAULT 0,
  `toughness` tinyint(4) NOT NULL DEFAULT 0,
  `spirit` tinyint(4) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`sheet_id`),
  CONSTRAINT `fk_pirate_borg_sheet_id` FOREIGN KEY (`sheet_id`) 
    REFERENCES `character_sheets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example insert statements (commented out)
/*
-- First insert into the main table
INSERT INTO `character_sheets` 
(`user_id`, `system`, `name`, `image_path`) 
VALUES 
('sample_user_id', 'pirate_borg', 'Captain Blackbeard', 'assets/TSP_default_character.jpg');

-- Then insert into the system-specific table
INSERT INTO `pirate_borg_sheets` 
(`sheet_id`, `strength`, `agility`, `presence`, `toughness`, `spirit`, `notes`) 
VALUES 
(LAST_INSERT_ID(), 5, 3, 4, 4, 2, 'A fearsome pirate captain known throughout the seven seas.');
*/ 