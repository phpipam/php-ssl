-- Create translations table for available UI languages.
-- locale_code must match a locale installed on the server (e.g. sl_SI.UTF-8).
-- English (id=1) is the built-in default; no .mo file is required for it.
CREATE TABLE IF NOT EXISTS `translations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'English name, e.g. Slovenian',
  `native_name` varchar(100) NOT NULL COMMENT 'Native name, e.g. Slovenščina',
  `locale_code` varchar(30) NOT NULL COMMENT 'gettext locale, e.g. sl_SI.UTF-8',
  `lang_code` varchar(5) NOT NULL COMMENT 'ISO 639-1, e.g. sl',
  `flag` varchar(10) DEFAULT NULL COMMENT 'Emoji flag',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale_code` (`locale_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Available UI translation languages';

INSERT IGNORE INTO `translations` (`id`, `name`, `native_name`, `locale_code`, `lang_code`, `flag`) VALUES
  (1, 'English',    'English',        'en_US.UTF-8', 'en', '🇬🇧'),
  (2, 'Slovenian',  'Slovenščina',    'sl_SI.UTF-8', 'sl', '🇸🇮');
