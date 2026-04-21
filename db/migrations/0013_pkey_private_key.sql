-- Rename pkey.key to pkey.private_key_enc
-- The column previously held public key data (never populated); repurposed for encrypted private key storage.
ALTER TABLE `pkey` CHANGE `key` `private_key_enc` TEXT DEFAULT NULL;
