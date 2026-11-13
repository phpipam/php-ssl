-- Older installs may not have the ignored_issuers table yet, so create it first
CREATE TABLE IF NOT EXISTS `ignored_issuers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `ski` varchar(255) NOT NULL DEFAULT '',
  `update` tinyint(1) NOT NULL DEFAULT 0,
  `expired` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ignored_issuers_tenant` (`t_id`),
  CONSTRAINT `ignored_issuers_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ignored_issuers`
  ADD COLUMN IF NOT EXISTS `update` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `expired` tinyint(1) NOT NULL DEFAULT 0;

-- Preserve existing behavior: all existing ignored issuers continue to suppress change notifications
UPDATE `ignored_issuers` SET `update` = 1 WHERE `update` = 0;
