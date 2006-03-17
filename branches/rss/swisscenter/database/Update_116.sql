-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.16' WHERE name='DATABASE_VERSION';

CREATE TABLE rss_subscriptions
(
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(1024) NOT NULL,
    title VARCHAR(50) NOT NULL,
    update_frequency INT NOT NULL DEFAULT 60,
    last_update DATETIME NOT NULL
);

CREATE TABLE rss_channels
(
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    subscription_id INT NOT NULL,
    channel_title VARCHAR(100) NOT NULL,
    display_title VARCHAR(100) NULL,
    channel_url VARCHAR(1024) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(1024) NULL,
    items_to_keep INT NOT NULL DEFAULT 50,
    max_age INT NOT NULL DEFAULT 30,
    published_date DATETIME NULL,
    modified_date DATETIME NULL,
    certificate_id INT NOT NULL
);

CREATE TABLE rss_items
(
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    channel_id INT,
    title VARCHAR(1024) NOT NULL,
    url VARCHAR(1024) NOT NULL,
    description TEXT NOT NULL,
    enclosure_url VARCHAR(1024) NULL,
    enclosure_size INT NULL,
    enclosure_type VARCHAR(100) NULL,
    published_date DATETIME NOT NULL,
    guid VARCHAR(1024) NULL
);



-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
