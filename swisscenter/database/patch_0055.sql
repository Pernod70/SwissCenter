-- -------------------------------------------------------------------------------------------------
-- Table structures for language translations
-- -------------------------------------------------------------------------------------------------

CREATE TABLE translate_languages (
  lang_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  ietf_tag                  varchar(10) NOT NULL,
  name                      text NOT NULL
  ,
  PRIMARY KEY (lang_id)
) ENGINE=MyISAM;

CREATE TABLE translate_keys (
  key_id                    int(10) unsigned NOT NULL AUTO_INCREMENT,
  text_id                   text NOT NULL,
  verified                  char(1) DEFAULT NULL
  ,
  PRIMARY KEY (key_id),
  UNIQUE KEY text_id (text_id(100))
) ENGINE=MyISAM;

CREATE TABLE translate_text (
  key_id                    int(10) unsigned NOT NULL,
  lang_id					int(10) unsigned NOT NULL,
  text                      text NOT NULL,
  version                   varchar(10)	
  ,
  PRIMARY KEY (key_id, lang_id),
  FOREIGN KEY (key_id) REFERENCES translate_keys (key_id) ON DELETE CASCADE,
  FOREIGN KEY (lang_id) REFERENCES translate_languages (lang_id) ON DELETE CASCADE
) ENGINE=MyISAM;
