-- -------------------------------------------------------------------------------------------------
-- Add publisher field to the mp3s table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s ADD ( publisher TEXT DEFAULT NULL );