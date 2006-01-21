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

ALTER TABLE certificates ADD (scheme text);

INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 1, 'G',      10, 'MPAA','General Audiences');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 2, 'Uc',     10, 'BBFC','Suitable for pre-school');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 3, 'U',      20, 'BBFC','Minimum age 4 years');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 4, 'PG',     30, 'BBFC','Parental guidance recommended');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 5, '12',     40, 'BBFC','Minimum age 12 years');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 6, 'PG-13',  50, 'MPAA','Parents Strongly Cautioned');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 7, '15',     60, 'BBFC','Minimum age 15 years');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 8, 'R',      70, 'MPAA','Restricted');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES ( 9, 'NC-17',  80, 'MPAA','Not suitable for viewers under 17');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES (10, '18',     90, 'BBFC','Minimum age 18 years');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES (11, 'XXX',   100, 'MPAA','Adult');
INSERT INTO certificates (cert_id, name, rank, scheme, description) VALUES (12, 'R18',   100, 'BBFC','Restricted distribution, minimum age 18 years');

-- -------------------------------------------------------------------------------------------------
-- Flag all movie details for download, as we've changed the certificates table.
-- -------------------------------------------------------------------------------------------------

UPDATE movies set details_available = null;

-- -------------------------------------------------------------------------------------------------
-- Flag all photos as out-of-date so that the new information (albums, exif, etc) get populated on
-- the next media search.
-- -------------------------------------------------------------------------------------------------

update photos set discovered='1900/01/02 00:00:00';

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
