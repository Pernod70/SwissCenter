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
    'Music'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Internet Radio'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Films'
  );

INSERT INTO categories (cat_name)
  VALUES (
    'Photos'
  );

-- -------------------------------------------------------------------------------------------------
-- Update the media tables (mp3s, photos, movies) to have categories
-- -------------------------------------------------------------------------------------------------
ALTER TABLE media_locations ADD COLUMN (cat_id INT UNSIGNED NOT NULL DEFAULT 1);

ALTER TABLE media_locations ADD FOREIGN KEY (cat_id) REFERENCES categories (cat_id) ON DELETE SET DEFAULT;

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
