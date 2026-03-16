-- Add version column to agents table to track agent software version.
ALTER TABLE `agents` ADD COLUMN IF NOT EXISTS `version` varchar(20) DEFAULT NULL;
