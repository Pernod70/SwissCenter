alter table movies add (year varchar(4) );
alter table movies add (details_available varchar(1) );
alter table movies add (match_pc int(10) );

CREATE TABLE actors (
  actor_id     int(10) unsigned  NOT NULL auto_increment,
  actor_name   text
  ,
  PRIMARY KEY  (actor_id),
  UNIQUE (actor_name(100))
) TYPE=MyISAM;

CREATE TABLE directors (
  director_id     int(10) unsigned  NOT NULL auto_increment,
  director_name   text
  ,
  PRIMARY KEY  (director_id),
  UNIQUE (director_name(100))
) TYPE=MyISAM;

CREATE TABLE genres (
  genre_id     int(10) unsigned  NOT NULL auto_increment,
  genre_name   text
  ,
  PRIMARY KEY  (genre_id),
  UNIQUE (genre_name(100))
) TYPE=MyISAM;

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
