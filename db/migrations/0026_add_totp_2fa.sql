ALTER TABLE `users`
    ADD COLUMN `totp_secret`  VARCHAR(255) DEFAULT NULL          AFTER `force_passkey`,
    ADD COLUMN `totp_enabled` TINYINT(1)   NOT NULL DEFAULT 0    AFTER `totp_secret`;
