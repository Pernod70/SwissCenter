-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Table structures for internet radio search parameters
-- -------------------------------------------------------------------------------------------------

CREATE TABLE iradio_stations (
  id         int unsigned auto_increment primary key not null,
  station    text NOT NULL,
  image      text
) TYPE=MyISAM;

INSERT INTO iradio_stations (station,image) VALUES ('.977','977_logo.gif');
INSERT INTO iradio_stations (station,image) VALUES ('Absolute Classic Rock','virgin_logo.gif');
INSERT INTO iradio_stations (station,image) VALUES ('Absolute Radio','virgin_logo.gif');
INSERT INTO iradio_stations (station,image) VALUES ('Avro','avro_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('Disney Tunes Radio Network','disney_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('Sky.FM','sky_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('.Kink','kink_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('Big R Radio','bigr_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('D i g i t a l l y - I m p o r t e d','di_logo.gif');

CREATE TABLE iradio_countries (
  id         int unsigned auto_increment primary key not null,
  country    text NOT NULL
) TYPE=MyISAM;

INSERT INTO iradio_countries (country) VALUES ('germany');
INSERT INTO iradio_countries (country) VALUES ('russia');
INSERT INTO iradio_countries (country) VALUES ('italy');
INSERT INTO iradio_countries (country) VALUES ('spain');
INSERT INTO iradio_countries (country) VALUES ('france');
INSERT INTO iradio_countries (country) VALUES ('usa');
INSERT INTO iradio_countries (country) VALUES ('england');
INSERT INTO iradio_countries (country) VALUES ('united kingdom');
INSERT INTO iradio_countries (country) VALUES ('brazil');
INSERT INTO iradio_countries (country) VALUES ('denmark');
INSERT INTO iradio_countries (country) VALUES ('poland');
INSERT INTO iradio_countries (country) VALUES ('netherlands');
INSERT INTO iradio_countries (country) VALUES ('sweden');
INSERT INTO iradio_countries (country) VALUES ('norway');
INSERT INTO iradio_countries (country) VALUES ('greece');

CREATE TABLE iradio_genres (
  id         int unsigned auto_increment primary key not null,
  genre      text NOT NULL,
  subgenre   text NOT NULL
) TYPE=MyISAM;

INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','hardrock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','punk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classic','classic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classic','symphonic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classic','opera');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','bluegrass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','western');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','swing');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','big band');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','smooth jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','acid jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','celtic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','70s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','80s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','90s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','50s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','top40');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','oldies');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','world');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','middle eastern');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','asian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','african');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','latin');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('world','russian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','electronic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','drum and bass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','ambient');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','trance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','techno');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('hip hop','hip hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('hip hop','rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('hip hop','turntablism');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('hip hop','old school');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('hip hop','new school');

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************