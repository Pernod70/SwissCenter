-- -------------------------------------------------------------------------------------------------
-- Remove DVD Video media type and re-allocate existing media to Video media type
-- -------------------------------------------------------------------------------------------------

UPDATE media_locations SET media_type=3 WHERE media_type=7;
UPDATE viewings        SET media_type=3 WHERE media_type=7;
DELETE FROM media_types WHERE media_id=7;

-- -------------------------------------------------------------------------------------------------
-- Repair the clients table
-- -------------------------------------------------------------------------------------------------

REPAIR TABLE clients;