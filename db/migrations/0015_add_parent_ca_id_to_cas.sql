ALTER TABLE `cas`
  ADD COLUMN `parent_ca_id` int(11) DEFAULT NULL AFTER `pkey_id`,
  ADD KEY `parent_ca_id` (`parent_ca_id`),
  ADD CONSTRAINT `cas_parent_fk` FOREIGN KEY (`parent_ca_id`) REFERENCES `cas` (`id`) ON DELETE SET NULL;
