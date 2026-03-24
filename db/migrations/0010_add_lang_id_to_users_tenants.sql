-- Add lang_id (language preference) to users and tenants tables.
-- NULL means "no preference" — falls back to tenant default, then English.
ALTER TABLE `users`   ADD COLUMN IF NOT EXISTS `lang_id` int(11) unsigned DEFAULT NULL AFTER `disabled`;
ALTER TABLE `tenants` ADD COLUMN IF NOT EXISTS `lang_id` int(11) unsigned DEFAULT NULL AFTER `log_retention`;

-- Foreign key constraints (added only if the column was just created; skip if already present)
-- Note: MariaDB does not support ADD CONSTRAINT IF NOT EXISTS.
-- Run manually if needed: ALTER TABLE users ADD CONSTRAINT users_lang_id FOREIGN KEY (lang_id) REFERENCES translations (id) ON DELETE SET NULL ON UPDATE CASCADE;
