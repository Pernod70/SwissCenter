-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.07' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Certificates table
-- -------------------------------------------------------------------------------------------------
CREATE TABLE Certificates
  (
    cert_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name varchar(5) NOT NULL,
    rank INT NOT NULL,
    description varchar(200) NULL
  ) Type='MyISAM';

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    'Uc',
    1,
    'Suitable for pre-school'
  );

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    'U',
    2,
    'Minimum age 4 years'
  );

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    'PG',
    3,
    'Parental guidance recommended'
  );

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    '12',
    4,
    'Minimum age 12 years'
  );

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    '15',
    5,
    'Minimum age 15 years'
  );

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    '18',
    6,
    'Minimum age 18 years'
  );

INSERT INTO Certificates (name, rank, description)
  VALUES
  (
    'R18',
    7,
    'Restricted distribution, minimum age 18 years'
  );


-- -------------------------------------------------------------------------------------------------
-- Users table
-- -------------------------------------------------------------------------------------------------
ALTER TABLE Users ADD MaxCert INT UNSIGNED DEFAULT 1;

ALTER TABLE Users ADD CONSTRAINT FOREIGN KEY(MaxCert) REFERENCES certificates(cert_id) ON DELETE SET NULL;


-- -------------------------------------------------------------------------------------------------
-- New preferences
-- -------------------------------------------------------------------------------------------------
INSERT INTO system_prefs SET name='Unrated', value='R18';

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
