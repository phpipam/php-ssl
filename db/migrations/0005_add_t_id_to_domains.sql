-- Add t_id column to domains table for per-tenant login configuration.
ALTER TABLE `domains` ADD COLUMN IF NOT EXISTS `t_id` int(11) unsigned NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_t_id` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
