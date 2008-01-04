-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.15' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Add new fields to the "clients" table. This records details on all the platforms that the
-- SwissCenter has seen so far (IP, agent string, player type, screen resolution, etc)
-- -------------------------------------------------------------------------------------------------

ALTER TABLE clients ADD 
( last_seen     DATETIME     default null
, screen_type   TEXT         default null
, screen_x_res  INT UNSIGNED default null
, screen_y_res  INT UNSIGNED default null
, aspect        TEXT         default null
);


-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************