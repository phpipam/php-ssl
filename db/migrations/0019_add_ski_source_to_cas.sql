ALTER TABLE `cas`
  ADD COLUMN IF NOT EXISTS `ski`    varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `source` enum('manual','auto') DEFAULT 'manual';

ALTER TABLE `cas`
  ADD KEY IF NOT EXISTS `cas_ski_tid` (`ski`, `t_id`);
