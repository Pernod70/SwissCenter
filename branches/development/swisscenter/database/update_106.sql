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
    'Uncategorised'
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
-- Update the media tables (mp3s, photos, movies) to reference the locations they were found in
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s ADD COLUMN (location_id INT UNSIGNED);
ALTER TABLE mp3s ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;

ALTER TABLE photos ADD COLUMN (location_id INT UNSIGNED);
ALTER TABLE photos ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;

ALTER TABLE movies ADD COLUMN (location_id INT UNSIGNED);
ALTER TABLE movies ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;


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


-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
