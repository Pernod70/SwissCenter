-- -------------------------------------------------------------------------------------------------
-- Table structure for table `themes`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE themes (
  file_id         int(10)     unsigned auto_increment NOT NULL,
  title           text        NOT NULL,
  media_type      int(10)     unsigned NOT NULL,
  thumb_cache     text        NOT NULL,
  original_url    text        NOT NULL,
  resolution      varchar(10) default NULL,
  colors          text        default NULL,
  flip_image      tinyint(1)  NOT NULL default 0,
  greyscale       tinyint(1)  NOT NULL default 0,
  use_series      tinyint(1)  NOT NULL default 0,
  use_synopsis    tinyint(1)  NOT NULL default 0,
  show_banner     tinyint(1)  NOT NULL default 0,
  show_image      tinyint(1)  NOT NULL default 0,
  original_cache  text,
  processed_image text,
  PRIMARY KEY (file_id),
  KEY title (title(50))
) ENGINE=MyISAM;




