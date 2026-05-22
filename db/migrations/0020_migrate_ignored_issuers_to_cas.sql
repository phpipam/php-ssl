-- Add notification flags to cas
ALTER TABLE `cas`
  ADD COLUMN IF NOT EXISTS `ignore_updates` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `ignore_expiry`  tinyint(1) NOT NULL DEFAULT 0;

-- Allow certificate to be NULL (for CAs migrated from ignored_issuers without a stored cert)
ALTER TABLE `cas`
  MODIFY COLUMN `certificate` text DEFAULT NULL;

-- Copy flags from ignored_issuers to matching cas rows
UPDATE `cas` c
  INNER JOIN `ignored_issuers` i ON i.ski = c.ski AND i.t_id = c.t_id
  SET c.ignore_updates = i.`update`,
      c.ignore_expiry  = i.expired;

-- Insert minimal cas rows for ignored issuers with no matching cas entry
INSERT INTO `cas` (t_id, name, ski, certificate, source, ignore_updates, ignore_expiry)
  SELECT i.t_id, i.name, i.ski, NULL, 'manual', i.`update`, i.expired
  FROM `ignored_issuers` i
  WHERE NOT EXISTS (
    SELECT 1 FROM `cas` c WHERE c.ski = i.ski AND c.t_id = i.t_id
  );

-- Drop ignored_issuers table
DROP TABLE IF EXISTS `ignored_issuers`;
