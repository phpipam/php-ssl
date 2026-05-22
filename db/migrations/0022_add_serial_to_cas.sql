ALTER TABLE `cas`
  ADD COLUMN IF NOT EXISTS `serial` varchar(255) DEFAULT NULL;

ALTER TABLE `cas`
  ADD KEY IF NOT EXISTS `cas_serial_tid` (`serial`, `t_id`);
