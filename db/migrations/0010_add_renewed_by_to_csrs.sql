ALTER TABLE `csrs`
    ADD COLUMN `renewed_by` INT(11) UNSIGNED DEFAULT NULL AFTER `cert_id`,
    ADD KEY `csr_renewed_by` (`renewed_by`),
    ADD CONSTRAINT `csr_renewed_by` FOREIGN KEY (`renewed_by`) REFERENCES `csrs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
