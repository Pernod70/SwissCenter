-- -------------------------------------------------------------------------------------------------
-- Add a movie rating field to the movies table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE movies ADD ( external_rating_pc integer );
