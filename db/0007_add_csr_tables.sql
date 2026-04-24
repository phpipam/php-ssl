-- Add CSR templates and CSR requests tables

CREATE TABLE IF NOT EXISTS `csr_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `key_algo` enum('RSA','EC') NOT NULL DEFAULT 'RSA',
  `key_size` int(5) NOT NULL DEFAULT 2048,
  `country` varchar(2) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `locality` varchar(128) DEFAULT NULL,
  `org` varchar(256) DEFAULT NULL,
  `ou` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `csr_tpl_tenant` (`t_id`),
  CONSTRAINT `csr_tpl_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `csrs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `t_id` int(11) unsigned NOT NULL,
  `cn` varchar(255) NOT NULL DEFAULT '',
  `sans` text DEFAULT NULL,
  `key_algo` enum('RSA','EC') NOT NULL DEFAULT 'RSA',
  `key_size` int(5) NOT NULL DEFAULT 2048,
  `country` varchar(2) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `locality` varchar(128) DEFAULT NULL,
  `org` varchar(256) DEFAULT NULL,
  `ou` varchar(256) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('pending','submitted','signed') NOT NULL DEFAULT 'pending',
  `csr_pem` text DEFAULT NULL,
  `pkey_id` int(11) unsigned DEFAULT NULL,
  `cert_id` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `csrs_tenant` (`t_id`),
  KEY `csrs_pkey` (`pkey_id`),
  KEY `csrs_cert` (`cert_id`),
  CONSTRAINT `csrs_tenant` FOREIGN KEY (`t_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `csrs_pkey` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `csrs_cert` FOREIGN KEY (`cert_id`) REFERENCES `certificates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
