-- Test migration: add a dummy 'test' column to users table.
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `test` varchar(50) DEFAULT NULL;
