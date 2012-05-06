-- -------------------------------------------------------------------------------------------------
-- Add the "DVD Video" media type to the media_types table
-- -------------------------------------------------------------------------------------------------

INSERT INTO media_types (media_id, media_name, media_table) VALUES (7, 'DVD Video', 'movies');

-- -------------------------------------------------------------------------------------------------
-- Add a field onto the media_locations table to record the network share
-- -------------------------------------------------------------------------------------------------

ALTER TABLE media_locations ADD (network_share text);