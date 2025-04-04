-- SQL to add character_type column to pirate_borg_sheets table
ALTER TABLE pirate_borg_sheets 
ADD COLUMN character_type VARCHAR(100) DEFAULT NULL AFTER sheet_id; 