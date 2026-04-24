CREATE TABLE `cas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `certificate` text NOT NULL,
  `pkey_id` int(11) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `t_id` (`t_id`),
  KEY `pkey_id` (`pkey_id`),
  CONSTRAINT `cas_pkey_fk` FOREIGN KEY (`pkey_id`) REFERENCES `pkey` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
