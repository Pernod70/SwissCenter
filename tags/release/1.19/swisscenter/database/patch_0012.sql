-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Additional tv show details.
-- -------------------------------------------------------------------------------------------------

ALTER TABLE tv ADD year VARCHAR(4);
ALTER TABLE tv ADD details_available VARCHAR(1);
ALTER TABLE tv ADD synopsis TEXT;

CREATE TABLE actors_in_tv (
  tv_id        int(10) unsigned,
  actor_id     int(10) unsigned
  ,
  PRIMARY KEY (tv_id, actor_id),
  FOREIGN KEY  (actor_id) REFERENCES actors (actor_id) ON DELETE CASCADE
);

CREATE TABLE directors_of_tv (
  tv_id        int(10) unsigned,
  director_id  int(10) unsigned
  ,
  PRIMARY KEY (tv_id, director_id),
  FOREIGN KEY  (director_id) REFERENCES directors (director_id) ON DELETE CASCADE
);

CREATE TABLE genres_of_tv (
  tv_id        int(10) unsigned,
  genre_id     int(10) unsigned
  ,
  PRIMARY KEY (tv_id, genre_id),
  FOREIGN KEY  (genre_id) REFERENCES genres (genre_id) ON DELETE CASCADE
);

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************