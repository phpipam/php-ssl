-- tenants.id is int(11) unsigned; cas.t_id must match for the FK to form
ALTER TABLE `cas` MODIFY COLUMN `t_id` int(11) unsigned NOT NULL;

ALTER TABLE `cas` ADD CONSTRAINT `cas_tenant_fk` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
