ALTER TABLE `users`
  ADD COLUMN `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `test`,
  ADD COLUMN `last_active` datetime DEFAULT NULL AFTER `create_date`;
