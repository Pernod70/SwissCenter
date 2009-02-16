-- -------------------------------------------------------------------------------------------------
-- Update the internet bookmarks to be assigned categories and have certificates
-- -------------------------------------------------------------------------------------------------

ALTER TABLE internet_urls ADD cat_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE internet_urls ADD certificate INT UNSIGNED NULL;
ALTER TABLE internet_urls ADD CONSTRAINT FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL;