-- -------------------------------------------------------------------------------------------------
-- Add a client_id field to the clients table
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS tmp_clients;
CREATE TEMPORARY TABLE tmp_clients SELECT * FROM clients;
DROP TABLE IF EXISTS clients;
CREATE TABLE clients ( client_id         int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                       ip_address        varchar(100) NOT NULL DEFAULT '',
                       box_id            varchar(100) DEFAULT NULL,
                       user_id           int(10) unsigned DEFAULT NULL,
                       agent_string      text,
                       device_type       text,
                       last_seen         datetime DEFAULT NULL,
                       screen_type       text,
                       aspect            text,
                       screen_x_res      int(10) unsigned DEFAULT NULL,
                       screen_y_res      int(10) unsigned DEFAULT NULL,
                       browser_x_res     int(10) unsigned DEFAULT NULL,
                       browser_y_res     int(10) unsigned DEFAULT NULL,
                       browser_scr_x_res int(10) unsigned DEFAULT NULL,
                       browser_scr_y_res int(10) unsigned DEFAULT NULL,
                       port              int(11) DEFAULT NULL,
                       mac_addr          varchar(17) DEFAULT NULL,
  KEY (ip_address),
  FOREIGN KEY (user_id) references users (user_id)
) ENGINE=MyISAM;

INSERT INTO clients (ip_address, box_id, user_id, agent_string, device_type, last_seen, screen_type, aspect, screen_x_res, screen_y_res, browser_x_res, browser_y_res, browser_scr_x_res, browser_scr_y_res, port, mac_addr) 
SELECT ip_address, box_id, user_id, agent_string, device_type, last_seen, screen_type, aspect, screen_x_res, screen_y_res, browser_x_res, browser_y_res, browser_scr_x_res, browser_scr_y_res, port, mac_addr 
FROM tmp_clients;

-- -------------------------------------------------------------------------------------------------
-- Add mood and lyrics fields to the mp3s table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE mp3s ADD ( mood   TEXT DEFAULT NULL,
                       lyrics TEXT DEFAULT NULL );
ALTER TABLE mp3s ADD KEY mood (mood(50));

-- -------------------------------------------------------------------------------------------------
-- Add reference fields to the movies and tv tables
-- -------------------------------------------------------------------------------------------------

ALTER TABLE movies ADD ( tmdb_id INT(10)     UNSIGNED DEFAULT NULL,
                         imdb_id INT(10)     UNSIGNED DEFAULT NULL,
                         os_hash VARCHAR(40) DEFAULT NULL );

ALTER TABLE tv ADD ( tvdb_id INT(10)     UNSIGNED DEFAULT NULL,
                     imdb_id INT(10)     UNSIGNED DEFAULT NULL,
                     os_hash VARCHAR(40) DEFAULT NULL );
