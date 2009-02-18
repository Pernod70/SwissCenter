-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Database Version Recording (set to the current release version)
-- -------------------------------------------------------------------------------------------------

INSERT INTO system_prefs (name, value) VALUES('DATABASE_VERSION','1.05');

-- -------------------------------------------------------------------------------------------------
-- SwissCenter Clients
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS clients;

CREATE TABLE clients (
  ip_address   varchar(100) NOT NULL default '',
  box_id       varchar(100),
  user_id      int(10) unsigned
  ,
  PRIMARY KEY  (ip_address),
  FOREIGN KEY (user_id) references users (user_id)
) ENGINE=MyISAM;


-- -------------------------------------------------------------------------------------------------
-- Messages
-- -------------------------------------------------------------------------------------------------

DELETE FROM messages;
ALTER TABLE messages CHANGE deleted status int;
ALTER TABLE messages ALTER status SET DEFAULT 0;
INSERT INTO messages (message_id, title, added, message_text)
  VALUES
  (
    1
    ,'Welcome to the Swisscenter'
    ,now()
    ,'This is the messages section, where you will be informed of new features and updates to the SwissCenter interface whenever you perform an automatic update.'
  );

-- -------------------------------------------------------------------------------------------------
-- Movie Tables
-- -------------------------------------------------------------------------------------------------

alter table movies add (year varchar(4) );
alter table movies add (details_available varchar(1) );
alter table movies add (match_pc int(10) );

CREATE TABLE actors (
  actor_id     int(10) unsigned  NOT NULL auto_increment,
  actor_name   text
  ,
  PRIMARY KEY  (actor_id),
  UNIQUE (actor_name(100))
) ENGINE=MyISAM;

CREATE TABLE directors (
  director_id     int(10) unsigned  NOT NULL auto_increment,
  director_name   text
  ,
  PRIMARY KEY  (director_id),
  UNIQUE (director_name(100))
) ENGINE=MyISAM;

CREATE TABLE genres (
  genre_id     int(10) unsigned  NOT NULL auto_increment,
  genre_name   text
  ,
  PRIMARY KEY  (genre_id),
  UNIQUE (genre_name(100))
) ENGINE=MyISAM;

CREATE TABLE actors_in_movie (
  movie_id     int(10) unsigned,
  actor_id     int(10) unsigned
  ,
  PRIMARY KEY (movie_id, actor_id),
  FOREIGN KEY  (actor_id) REFERENCES actors (actor_id) ON DELETE CASCADE
);

CREATE TABLE directors_of_movie (
  movie_id     int(10) unsigned,
  director_id  int(10) unsigned
  ,
  PRIMARY KEY (movie_id, director_id),
  FOREIGN KEY  (director_id) REFERENCES directors (director_id) ON DELETE CASCADE
);

CREATE TABLE genres_of_movie (
  movie_id     int(10) unsigned,
  genre_id     int(10) unsigned
  ,
  PRIMARY KEY (movie_id, genre_id),
  FOREIGN KEY  (genre_id) REFERENCES genres (genre_id) ON DELETE CASCADE
);

-- -------------------------------------------------------------------------------------------------
-- Photo Tables
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS photos;

CREATE TABLE photos (
  file_id int(10) unsigned NOT NULL auto_increment,
  dirname text,
  filename text,
  size int(11) default NULL,
  width int(11) default NULL,
  height int(11) default NULL,
  date_modified varchar(50) default NULL,
  date_created int unsigned,
  verified char(1) default NULL,
  PRIMARY KEY  (file_id),
  KEY dirname (dirname(255)),
  KEY filename (filename(255))
) ENGINE=MyISAM;

-- *************************************************************************************************
--   SWISScenter Source                                                              Robert Taylor
-- *************************************************************************************************
