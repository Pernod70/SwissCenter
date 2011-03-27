-- -------------------------------------------------------------------------------------------------
-- Table structure for table `languages`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE languages (
  language_id  int(10) unsigned  NOT NULL auto_increment,
  language     text
  ,
  PRIMARY KEY  (language_id),
  UNIQUE (language(100))
) ENGINE=MyISAM;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `languages_of_movie`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE languages_of_movie (
  movie_id     int(10) unsigned,
  language_id  int(10) unsigned
  ,
  PRIMARY KEY (movie_id, language_id),
  FOREIGN KEY  (language_id) REFERENCES languages (language_id) ON DELETE CASCADE
) ENGINE=MyISAM;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `languages_of_tv`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE languages_of_tv (
  tv_id        int(10) unsigned,
  language_id  int(10) unsigned
  ,
  PRIMARY KEY (tv_id, language_id),
  FOREIGN KEY  (language_id) REFERENCES languages (language_id) ON DELETE CASCADE
) ENGINE=MyISAM;



