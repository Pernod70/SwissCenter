-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

UPDATE system_prefs SET value='1.14' WHERE name='DATABASE_VERSION';

-- -------------------------------------------------------------------------------------------------
-- Re-seed certificates table... this time with a much larger ranking so that we can squeeze in 
-- rating systems from other countries (like we have with the US MPAA ratings below).
-- -------------------------------------------------------------------------------------------------

DELETE FROM certificates;

INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 1, 'G',      10, 'General Audiences')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 2, 'Uc',     10, 'Suitable for pre-school')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 3, 'U',      20, 'Minimum age 4 years')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 4, 'PG',     30, 'Parental guidance recommended')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 5, '12',     40, 'Minimum age 12 years')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 6, 'PG-13',  50, 'Parents Strongly Cautioned')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 7, '15',     60, 'Minimum age 15 years')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 8, 'R',      70, 'Restricted')
INSERT INTO certificates (cert_id, name, rank, description) VALUES ( 9, 'NC-17',  80, 'Not suitable for viewers under 17');
INSERT INTO certificates (cert_id, name, rank, description) VALUES (10, '18',     90, 'Minimum age 18 years')
INSERT INTO certificates (cert_id, name, rank, description) VALUES (11, 'XXX',   100, 'Adult');
INSERT INTO certificates (cert_id, name, rank, description) VALUES (12, 'R18',   100, 'Restricted distribution, minimum age 18 years')

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
