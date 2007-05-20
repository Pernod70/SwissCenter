-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.17' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Ticket #47. BBFC classification "12A" missing from the database
-- -------------------------------------------------------------------------------------------------

INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 0, '12A',     35, 'BBFC','Minimum age 12 years (unless accompanied)');

-- -------------------------------------------------------------------------------------------------
-- Recording the HTTP port of the client as a workaround for a bug in the hardware player's browser
-- -------------------------------------------------------------------------------------------------

ALTER TABLE clients ADD (port integer);

-- -------------------------------------------------------------------------------------------------
-- Add a field onto the media_locations table to record the percentage scanned (for media searches)
-- -------------------------------------------------------------------------------------------------

ALTER TABLE media_locations ADD (percent_scanned integer);

-- -------------------------------------------------------------------------------------------------
-- RSS subscriptions and items
-- -------------------------------------------------------------------------------------------------

CREATE TABLE rss_subscriptions
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    type INT NOT NULL,
    url TEXT NOT NULL,
    title VARCHAR(50) NOT NULL,
    update_frequency INT NOT NULL DEFAULT 60,
    last_update DATETIME NOT NULL
);


CREATE TABLE rss_items
(
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    subscription_id INT NOT NULL,
    title TEXT NOT NULL,
    url TEXT NULL,
    description TEXT NOT NULL,
    published_date DATETIME NOT NULL,
    timestamp INT NOT NULL,
    guid TEXT NULL,
    linked_file TEXT NULL
);


-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
