ALTER TABLE `users`
    ADD COLUMN `layout` TEXT DEFAULT NULL AFTER `lang_id`;
