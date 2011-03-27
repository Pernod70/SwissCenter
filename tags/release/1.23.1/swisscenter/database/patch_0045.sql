-- -------------------------------------------------------------------------------------------------
-- Add a radio type field to the iradio_stations table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE iradio_stations ADD ( iradio_type INT UNSIGNED NOT NULL DEFAULT 1);