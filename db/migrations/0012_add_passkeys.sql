-- Migration 0012: Add passkeys table and force_passkey column to users

ALTER TABLE `users` ADD COLUMN `force_passkey` tinyint(1) NOT NULL DEFAULT 0 AFTER `disabled`;

CREATE TABLE `passkeys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `credential_id` varchar(512) NOT NULL,
  `public_key` text NOT NULL,
  `sign_count` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id` (`credential_id`(255)),
  KEY `passkeys_user_id` (`user_id`),
  CONSTRAINT `passkeys_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
