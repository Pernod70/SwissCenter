-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.06' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Category table
-- -------------------------------------------------------------------------------------------------

CREATE TABLE categories (
    cat_id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
    cat_name varchar(100) NOT NULL UNIQUE
  ) TYPE=MyISAM;

INSERT INTO categories (cat_id, cat_name)
  VALUES (
    1,
    'General'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Music Videos'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Audio Books'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Films'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Language Learning'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'TV Series'
  );

-- -------------------------------------------------------------------------------------------------
-- Update the media locations to have categories
-- -------------------------------------------------------------------------------------------------

ALTER TABLE media_locations ADD COLUMN (cat_id INT UNSIGNED NOT NULL DEFAULT 1);
ALTER TABLE media_locations ADD FOREIGN KEY (cat_id) REFERENCES categories (cat_id) ON DELETE SET DEFAULT;

-- -------------------------------------------------------------------------------------------------
-- Adds the date than new media was discovered to the media tables
--   NOTE: I had to set the default to NULL, because you can't use now() as the default
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s   ADD COLUMN (discovered DATETIME DEFAULT null );
ALTER TABLE movies ADD COLUMN (discovered DATETIME DEFAULT null );
ALTER TABLE photos ADD COLUMN (discovered DATETIME DEFAULT null );

-- -------------------------------------------------------------------------------------------------
-- Update the media tables (mp3s, photos, movies) to reference the locations they were found in
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s ADD COLUMN (location_id INT UNSIGNED);
ALTER TABLE mp3s ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;

ALTER TABLE photos ADD COLUMN (location_id INT UNSIGNED);
ALTER TABLE photos ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;

ALTER TABLE movies ADD COLUMN (location_id INT UNSIGNED);
ALTER TABLE movies ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;

UPDATE mp3s m, media_locations l
   SET m.location_id = l.location_id
 WHERE l.name = left(m.dirname,length(l.name)) AND l.media_type = 1;

UPDATE photos p, media_locations l
   SET p.location_id = l.location_id
 WHERE l.name = left(p.dirname,length(l.name)) AND l.media_type = 2;

UPDATE movies m, media_locations l
   SET m.location_id = l.location_id
 WHERE l.name = left(m.dirname,length(l.name)) AND l.media_type = 3;

-- -------------------------------------------------------------------------------------------------
-- Remove duplicate entries from the media tables and then enforce DIRNAME and FILENAME to be unique
-- -------------------------------------------------------------------------------------------------

-- Create temporary tables that identify duplicate rows that are to be deleted.

CREATE TEMPORARY TABLE mp3s_del AS 
    SELECT max(file_id) file_id 
      FROM mp3s 
  GROUP BY dirname,filename 
    HAVING count(*)>1;

CREATE TEMPORARY TABLE movies_del AS 
    SELECT max(file_id) file_id 
      FROM movies
  GROUP BY dirname,filename 
    HAVING count(*)>1;

CREATE TEMPORARY TABLE photos_del AS 
    SELECT max(file_id) file_id 
      FROM photos
  GROUP BY dirname,filename 
    HAVING count(*)>1;

-- Delete the rows identified as duplicates

DELETE FROM mp3s   USING mp3s, mp3s_del     WHERE mp3s.file_id = mp3s_del.file_id;
DELETE FROM movies USING movies, movies_del WHERE movies.file_id = movies_del.file_id;
DELETE FROM photos USING photos, photos_del WHERE photos.file_id = photos_del.file_id;

-- Create Unique indexes

CREATE UNIQUE INDEX mp3s_fsp_u1   ON mp3s (dirname(800),filename(200));
CREATE UNIQUE INDEX movies_fsp_u1 ON movies (dirname(800),filename(200));
CREATE UNIQUE INDEX photos_fsp_u1 ON photos (dirname(800),filename(200));

-- -------------------------------------------------------------------------------------------------
-- Create a table to store AlbumArt which has been extracted from mp3s.
-- 
-- Note: I'm using a seperate table because there are numerous places within the existing code where
--       all columns in the "mp3s" table are retrieved into an array. We don't want the overhead of
--       fetching BLOBs unless they are actually needed.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE mp3_albumart (
  file_id      int unsigned   NOT NULL,
  image        mediumblob     NOT NULL
  ,
  FOREIGN KEY (file_id) references mp3s (file_id)
  ) TYPE=MyISAM;

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
