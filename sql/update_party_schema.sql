-- Update Party Schema to support Game Master designation
ALTER TABLE parties
ADD COLUMN game_master_id VARCHAR(128) DEFAULT NULL;

-- Add is_active column to character_sheets table if it doesn't exist
ALTER TABLE character_sheets
ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0; 