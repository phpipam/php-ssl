ALTER TABLE `phpversions`
    ADD COLUMN `service`        VARCHAR(255) DEFAULT NULL COMMENT 'nmap -sV product/version on this port' AFTER `server`,
    ADD COLUMN `http_title`     VARCHAR(255) DEFAULT NULL AFTER `service`,
    ADD COLUMN `os`             VARCHAR(255) DEFAULT NULL COMMENT 'nmap -O best match, or service OS hint' AFTER `http_title`,
    ADD COLUMN `os_accuracy`    TINYINT(3) UNSIGNED DEFAULT NULL AFTER `os`,
    ADD COLUMN `os_source`      VARCHAR(16)  DEFAULT NULL COMMENT 'nmap-os | service | smb | rdp' AFTER `os_accuracy`,
    ADD COLUMN `device_type`    VARCHAR(64)  DEFAULT NULL AFTER `os_source`,
    ADD COLUMN `os_cpe`         VARCHAR(255) DEFAULT NULL AFTER `device_type`,
    ADD COLUMN `ssh_banner`     VARCHAR(255) DEFAULT NULL AFTER `os_cpe`,
    ADD COLUMN `windows_info`   VARCHAR(255) DEFAULT NULL COMMENT 'smb-os-discovery / rdp-ntlm-info' AFTER `ssh_banner`,
    ADD COLUMN `open_ports`     TEXT         DEFAULT NULL COMMENT 'JSON: all open ports and services on this IP' AFTER `windows_info`,
    ADD COLUMN `last_fingerprint` DATETIME   DEFAULT NULL AFTER `last_change`;
