-- -------------------------------------------------------------------------------------------------
-- Remove the flickr_cache table and create a generic replacement
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS flickr_cache;

CREATE TABLE cache_api_request (
  request    CHAR(35)   NOT NULL,
  service    TEXT       NOT NULL,
  response   MEDIUMTEXT NOT NULL,
  expiration DATETIME   NOT NULL
  ,
  PRIMARY KEY (request)
) ENGINE=MyISAM;