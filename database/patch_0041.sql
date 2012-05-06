-- -------------------------------------------------------------------------------------------------
-- Table structure for table `lastfm_scrobble_tracks`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE lastfm_scrobble_tracks (
  scrobble_id INT(10)     UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT(10)     NOT NULL,
  player_id   VARCHAR(17) NOT NULL,
  artist      TEXT        NOT NULL,
  title       TEXT        NOT NULL,
  album       TEXT,
  length      INT(10)     NOT NULL,
  track       INT(10),
  play_start  INT         UNSIGNED NOT NULL,
  play_end    INT         UNSIGNED
  ,
  PRIMARY KEY (scrobble_id)
) ENGINE=MyISAM;