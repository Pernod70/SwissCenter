-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.15' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Create a new table to track the number of viewings per media type for each user of the system.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE viewings
( user_id          int(10) unsigned NOT NULL 
, media_type       int(10) unsigned NOT NULL 
, media_id         int(10) unsigned NOT NULL 
, last_viewed      datetime NOT NULL
, total_viewings   int(10) unsigned default 0
);

CREATE INDEX viewings_n1 ON viewings (media_id);

-- -------------------------------------------------------------------------------------------------
-- Add new fields to the "clients" table. This records details on all the platforms that the
-- SwissCenter has seen so far (IP, agent string, player type, screen resolution, etc)
-- -------------------------------------------------------------------------------------------------

ALTER TABLE clients ADD ( last_seen     DATETIME     default null);
ALTER TABLE clients ADD ( screen_type   TEXT         default null);
ALTER TABLE clients ADD ( screen_x_res  INT UNSIGNED default null);
ALTER TABLE clients ADD ( screen_y_res  INT UNSIGNED default null);
ALTER TABLE clients ADD ( browser_x_res INT UNSIGNED default null);
ALTER TABLE clients ADD ( browser_y_res INT UNSIGNED default null);
ALTER TABLE clients ADD ( aspect        TEXT         default null);


-- -------------------------------------------------------------------------------------------------
-- Adds missing MPAA ratings for the certificates table
-- -------------------------------------------------------------------------------------------------

INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 13, 'PG', 30, 'MPAA','Parental guidance recommended');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 14, 'UR',  5, 'MPAA','Un-Rated Family content');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 15, 'NR', 90, 'MPAA','Not Rated Adult content');

-- -------------------------------------------------------------------------------------------------
-- New column for the categories table to indicate if details should be downloaded
-- -------------------------------------------------------------------------------------------------

ALTER TABLE categories ADD  ( download_info VARCHAR(1) DEFAULT 'N' );
UPDATE categories set download_info='Y' WHERE cat_name='Films' ;

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
