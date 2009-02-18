-- -------------------------------------------------------------------------------------------------
-- Create a new table "toma_channels" to contain channel data from TOMA.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE toma_channels (
  id            int(10) unsigned primary key not null auto_increment,
  name          varchar(50),
  description   text,
  homepage      text,
  stream_url    text,
  country       varchar(50),
  category      varchar(50),
  media_type    char(2),
  bitrate       int(10),
  rating        int(10),
  votes         int(10),
  works_fine    int(10),
  not_working   int(10),
  wrong_info    int(10),
  duplicate     int(10),
  spam          int(10)
) ENGINE=MyISAM;