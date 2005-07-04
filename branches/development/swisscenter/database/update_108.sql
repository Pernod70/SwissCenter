-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.09' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Certificates table
-- -------------------------------------------------------------------------------------------------
CREATE TABLE certificates
  (
    cert_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name varchar(7) COLLATE utf8_general_ci NOT NULL,
    rank INT NOT NULL,
    description varchar(200) COLLATE utf8_general_ci NULL
  ) Type='MyISAM';

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    'Uc',
    1,
    'Suitable for pre-school'
  );

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    'U',
    2,
    'Minimum age 4 years'
  );

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    'PG',
    3,
    'Parental guidance recommended'
  );

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    '12',
    4,
    'Minimum age 12 years'
  );

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    '15',
    5,
    'Minimum age 15 years'
  );

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    '18',
    6,
    'Minimum age 18 years'
  );

INSERT INTO certificates (name, rank, description)
  VALUES
  (
    'R18',
    7,
    'Restricted distribution, minimum age 18 years'
  );


-- -------------------------------------------------------------------------------------------------
-- Users table
-- -------------------------------------------------------------------------------------------------
ALTER TABLE users ADD maxcert INT UNSIGNED DEFAULT 1;
ALTER TABLE users ADD pin VARCHAR(10) DEFAULT NULL;

ALTER TABLE users ADD CONSTRAINT FOREIGN KEY(maxcert) REFERENCES certificates(cert_id) ON DELETE SET NULL;


-- -------------------------------------------------------------------------------------------------
-- Default value for unrated media per media location
-- -------------------------------------------------------------------------------------------------
ALTER TABLE media_locations ADD unrated INT UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE media_locations ADD CONSTRAINT FOREIGN KEY (unrated) REFERENCES certificates(cert_id) ON DELETE SET DEFAULT;


-- -------------------------------------------------------------------------------------------------
-- Drop the old ratings table
-- -------------------------------------------------------------------------------------------------
DROP TABLE ratings;

-- -------------------------------------------------------------------------------------------------
-- Change the media in media tables to have certificates
-- -------------------------------------------------------------------------------------------------
ALTER TABLE movies ADD certificate INT UNSIGNED NULL;
ALTER TABLE movies ADD CONSTRAINT FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL;

UPDATE movies m,certificates c SET m.certificate=c.cert_id WHERE m.rating=c.name;
ALTER TABLE movies DROP rating;


ALTER TABLE mp3s ADD certificate INT UNSIGNED NULL;
ALTER TABLE mp3s ADD CONSTRAINT FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL;

ALTER TABLE photos ADD certificate INT UNSIGNED NULL;
ALTER TABLE photos ADD CONSTRAINT FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL;


-- -------------------------------------------------------------------------------------------------
-- Media types table
-- -------------------------------------------------------------------------------------------------
ALTER TABLE media_types ADD media_table nvarchar(20) NULL;

UPDATE media_types SET media_table='mp3s' WHERE media_id=1;
UPDATE media_types SET media_table='photos' WHERE media_id=2;
UPDATE media_types SET media_table='movies' WHERE media_id=3;
UPDATE media_types SET media_table='radio_stations' WHERE media_id=4;

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
