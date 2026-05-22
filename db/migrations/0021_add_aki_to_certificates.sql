-- Store the issuer's Subject Key Identifier on each certificate row
-- so we can JOIN certificates to cas without parsing PEM at query time.
ALTER TABLE `certificates`
  ADD COLUMN IF NOT EXISTS `aki` varchar(255) DEFAULT NULL;

ALTER TABLE `certificates`
  ADD KEY IF NOT EXISTS `cert_aki` (`aki`);
