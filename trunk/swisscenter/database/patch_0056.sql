-- -------------------------------------------------------------------------------------------------
-- Table structure for table `user_permissions`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE user_permissions (
  user_id                   int(10) unsigned NOT NULL,
  location_id               int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (user_id,location_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE
) ENGINE=MyISAM;