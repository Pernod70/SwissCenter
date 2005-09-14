-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.13' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Create table to hold photo "albums" information. 
-- -------------------------------------------------------------------------------------------------

CREATE TABLE photo_albums (
  file_id int(10) unsigned NOT NULL auto_increment,
  dirname text,
  title text,
  verified char(1),
  discovered DATETIME DEFAULT null,
  location_id INT UNSIGNED,
  certificate INT UNSIGNED NULL
  ,
  PRIMARY KEY  (file_id),
  KEY title (title(50))
) TYPE=MyISAM;

ALTER TABLE movies ADD CONSTRAINT FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL;
ALTER TABLE movies ADD FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE;

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
