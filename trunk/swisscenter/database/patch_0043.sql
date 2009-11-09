-- -------------------------------------------------------------------------------------------------
-- Add the composer column to the mp3 table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s ADD COLUMN composer TEXT default null;

-- -------------------------------------------------------------------------------------------------
-- Add some indexes to improve query performance
-- -------------------------------------------------------------------------------------------------

ALTER TABLE categories ADD KEY (parent_id);

ALTER TABLE media_locations ADD KEY (media_type);

ALTER TABLE mp3s ADD KEY (location_id);
ALTER TABLE mp3s ADD KEY composer (composer(50))
ALTER TABLE mp3s ADD KEY band (band(50))

ALTER TABLE movies ADD KEY (location_id);

ALTER TABLE photos ADD KEY (location_id);
ALTER TABLE photos ADD KEY iptc_byline (iptc_byline(50));
ALTER TABLE photos ADD KEY iptc_location (iptc_location(50));
ALTER TABLE photos ADD KEY iptc_city (iptc_city(50));
ALTER TABLE photos ADD KEY iptc_province_state (iptc_province_state(50));
ALTER TABLE photos ADD KEY iptc_country (iptc_country(50));
ALTER TABLE photo_albums ADD KEY (location_id);

ALTER TABLE tv ADD KEY (location_id);
ALTER TABLE tv ADD KEY programme (programme(50));

ALTER TABLE rss_items ADD KEY (subscription_id);

ALTER TABLE themes ADD KEY (media_type);