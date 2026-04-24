ALTER TABLE `csrs`
  ADD COLUMN `source` ENUM('internal','external') NOT NULL DEFAULT 'internal' AFTER `status`;

-- Backfill: CSRs without a stored private key are external
UPDATE `csrs` c
  LEFT JOIN `pkey` pk ON c.pkey_id = pk.id
  SET c.source = 'external'
  WHERE c.pkey_id IS NULL
     OR pk.id IS NULL
     OR pk.private_key_enc IS NULL
     OR pk.private_key_enc = '';
