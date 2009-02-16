-- -------------------------------------------------------------------------------------------------
-- Adds the timestamp of media files discovered to the media tables
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s         ADD COLUMN (timestamp DATETIME DEFAULT null );
ALTER TABLE movies       ADD COLUMN (timestamp DATETIME DEFAULT null );
ALTER TABLE photos       ADD COLUMN (timestamp DATETIME DEFAULT null );
ALTER TABLE photo_albums ADD COLUMN (timestamp DATETIME DEFAULT null );
ALTER TABLE tv           ADD COLUMN (timestamp DATETIME DEFAULT null );

UPDATE mp3s         SET timestamp = discovered;
UPDATE movies       SET timestamp = discovered;
UPDATE photos       SET timestamp = discovered;
UPDATE photo_albums SET timestamp = discovered;
UPDATE tv           SET timestamp = discovered;