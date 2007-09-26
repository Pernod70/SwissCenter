-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Create a new table "tv" to contain details on TV recordings.
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s ADD ( band TEXT default null);

CREATE TABLE tv (
  file_id             int(10) unsigned not null auto_increment,
  dirname             text,
  filename            text,
  title               text,
  programme           text,
  episode             int(11),
  series              int(11),
  size                int(11) default null,
  length              int(11) default null,
  lengthstring        text,
  verified            char(1),
  discovered          datetime default null,
  location_id         int unsigned,
  certificate         int unsigned null
  ,
  PRIMARY KEY  (file_id),
  FOREIGN KEY  (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE,
  FOREIGN KEY  (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL,
  KEY title    (title(50))
) TYPE=MyISAM;

CREATE UNIQUE INDEX tv_fsp_u1 ON movies (dirname(250),filename(250));

-- -------------------------------------------------------------------------------------------------
-- Add the "TV Series" media type to the media_types table
-- -------------------------------------------------------------------------------------------------

INSERT INTO media_types (media_id, media_name, media_table) VALUES (6, 'TV Series', 'tv');

-- -------------------------------------------------------------------------------------------------
-- Create a table to contain regular expressions for pulling metadata from a TV series media file
-- by means of it's path and filename. Populate it with the initial common expressions.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE tv_expressions (
  pos                 int(11),
  expression          text
  ,
  PRIMARY KEY (pos)
) TYPE=MyISAM;

INSERT INTO tv_expressions (pos, expression) VALUES ( 1,'{p}\\W+s{s}e{e}\\W+{t}');
INSERT INTO tv_expressions (pos, expression) VALUES ( 2,'{p}\\W+{s}x{e}\\W+{t}');
INSERT INTO tv_expressions (pos, expression) VALUES ( 3,'{p}/series {s}/{e}\\W+{t}');
INSERT INTO tv_expressions (pos, expression) VALUES ( 4,'{p}/season {s}/{e}\\W+{t}');
INSERT INTO tv_expressions (pos, expression) VALUES ( 5,'{p}/{s}/{e}\\W*{t}');
INSERT INTO tv_expressions (pos, expression) VALUES ( 6,'{p}/{e}\\W+{t}');
INSERT INTO tv_expressions (pos, expression) VALUES ( 7,'{p}/{t}\\W+\\(?s{s}e{e}\\)?');
INSERT INTO tv_expressions (pos, expression) VALUES ( 8,'{p}/{t}\\W+\\(?{s}x{e}\\)?');
INSERT INTO tv_expressions (pos, expression) VALUES ( 9,'{p}\\W+s{s}e{e}');
INSERT INTO tv_expressions (pos, expression) VALUES (10,'{p}\\W+{s}x{e}');

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

