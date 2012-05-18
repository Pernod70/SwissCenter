-- -------------------------------------------------------------------------------------------------
-- Table structure for table `system_prefs`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE system_prefs (
  name                      varchar(100) NOT NULL,
  value                     text,
  modified                  datetime DEFAULT NULL
  ,
  PRIMARY KEY (name)
) ENGINE=InnoDB;

INSERT INTO system_prefs (name,value) VALUES ('CACHE_MAXSIZE_MB','100');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `certificates`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE certificates (
  cert_id               int(10) unsigned NOT NULL AUTO_INCREMENT,
  name                  varchar(7) NOT NULL DEFAULT '',
  rank                  int(10) unsigned NOT NULL DEFAULT 0,
  description           varchar(200) DEFAULT NULL,
  scheme                text
  ,
  PRIMARY KEY (cert_id),
  KEY scheme (scheme(20)),
  KEY rank (rank)
) ENGINE=InnoDB;

INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'Uc',     10, 'BBFC','Suitable for pre-school'),
                                                                  ( 'U',      20, 'BBFC','Minimum age 4 years'),
                                                                  ( 'PG',     30, 'BBFC','Parental guidance recommended'),
                                                                  ( '12',     40, 'BBFC','Minimum age 12 years'),
                                                                  ( '12A',    35, 'BBFC','Minimum age 12 years (unless accompanied)'),
                                                                  ( '15',     60, 'BBFC','Minimum age 15 years'),
                                                                  ( '18',     90, 'BBFC','Minimum age 18 years'),
                                                                  ( 'R18',   100, 'BBFC','Restricted distribution, minimum age 18 years'),
                                                                  ( 'UR',      5, 'MPAA','Un-Rated Family content'),
                                                                  ( 'G',      10, 'MPAA','General Audiences'),
                                                                  ( 'PG',     30, 'MPAA','Parental guidance recommended'),
                                                                  ( 'PG-13',  50, 'MPAA','Parents Strongly Cautioned'),
                                                                  ( 'R',      70, 'MPAA','Restricted'),
                                                                  ( 'NC-17',  80, 'MPAA','Not suitable for viewers under 17'),
                                                                  ( 'NR',     90, 'MPAA','Not Rated Adult content'),
                                                                  ( 'XXX',   100, 'MPAA','Adult'),
                                                                  ( 'FSK 0',   5, 'FSK','Released without age restriction'),
                                                                  ( 'FSK 6',  25, 'FSK','Released to age 6 or older'),
                                                                  ( 'FSK 12', 40, 'FSK','Released to age 12 or older and to age 6 or older with parental guidance'),
                                                                  ( 'FSK 16', 70, 'FSK','Released to age 16 or older'),
                                                                  ( 'FSK 18', 90, 'FSK','No release to youths (released to age 18 or older)'),
                                                                  ( 'SPIO',  100, 'FSK','Rating denied'),
                                                                  ( 'AL',    5, 'Kijkwijzer','Not harmful / All Ages'),
                                                                  ( '6',    25, 'Kijkwijzer','Watch out with children under 6'),
                                                                  ( 'MG6',  30, 'Kijkwijzer','Watch out with children under 6, parental guidance advised'),
                                                                  ( '9',    35, 'Kijkwijzer','Watch out with children under 9'),
                                                                  ( '12',   40, 'Kijkwijzer','Watch out with children under 12'),
                                                                  ( '16',   70, 'Kijkwijzer','Watch out with children under 16'),
                                                                  ( '18',   90, 'Kijkwijzer','Watch out with children under 18'),
                                                                  ( 'XXX', 100, 'Kijkwijzer','Adult');

CREATE TABLE certificates_of_movie (
  movie_id                  int(10) unsigned NOT NULL,
  cert_id                   int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (cert_id,movie_id),
  FOREIGN KEY (cert_id) REFERENCES certificates (cert_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certificates_of_tv (
  programme_id              int(10) unsigned NOT NULL,
  episode_id                int(10) unsigned DEFAULT NULL,
  cert_id                   int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (programme_id,episode_id,cert_id),
  FOREIGN KEY (cert_id) REFERENCES certificates (cert_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `users`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE users (
  user_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  name                      text,
  maxcert                   int(10) unsigned DEFAULT 1,
  pin                       varchar(10) DEFAULT NULL,
  admin                     int(10) unsigned DEFAULT 0
  ,
  PRIMARY KEY (user_id),
  FOREIGN KEY (maxcert) REFERENCES certificates (cert_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TEMPORARY TABLE tt_cert AS SELECT * FROM certificates ORDER BY rank DESC LIMIT 1;
INSERT INTO users (user_id,name) VALUES (1,'Default');
UPDATE users,tt_cert SET users.maxcert = tt_cert.cert_id WHERE user_id=1;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `user_prefs`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE user_prefs (
  user_id                   int(10) unsigned NOT NULL,
  name                      varchar(100) NOT NULL,
  value                     text,
  modified                  datetime DEFAULT NULL
  ,
  PRIMARY KEY (user_id,name),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO user_prefs (user_id,name,value) VALUES (1,'WEATHER_HOME','UKXX0022','\/q\/zmw:00000.1.03763');
INSERT INTO user_prefs (user_id,name,value) VALUES (1,'WEATHER_UNITS','m');
INSERT INTO user_prefs (user_id,name,value) VALUES (1,'STYLE','KDE');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `user_favourites`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE user_favourites (
  user_id                   int(10) unsigned NOT NULL,
  programme_id              int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (user_id,programme_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `art_files`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE art_files (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  filename                  varchar(100) NOT NULL
  ,
  PRIMARY KEY (file_id)
) ENGINE=InnoDB;

INSERT INTO art_files (filename) VALUES ('folder.jpg'),
                                        ('Folder.jpg'),
                                        ('folder.gif'),
                                        ('folder.png'),
                                        ('Artwork.jpg');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `cache_api_request`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE cache_api_request (
  request               char(35) NOT NULL,
  service               text NOT NULL,
  response              mediumtext NOT NULL,
  expiration            datetime NOT NULL
  ,
  PRIMARY KEY (request),
  KEY service (service(20))
) ENGINE=InnoDB; 

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `categories`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE categories (
  cat_id                int(10) unsigned NOT NULL AUTO_INCREMENT,
  cat_name              varchar(100) NOT NULL DEFAULT '',
  parent_id             int(11) NOT NULL DEFAULT 0
  ,
  PRIMARY KEY (cat_id),
  KEY parent_id (parent_id),
  UNIQUE KEY cat_name (cat_name)
) ENGINE=InnoDB;

INSERT INTO categories (cat_name,parent_id) VALUES ('General', 0           ),
                                                   ('Music Videos', 0      ),
                                                   ('Audio Books', 0       ),
                                                   ('Films', 0             ),
                                                   ('Language Learning', 0 ),
                                                   ('TV Series', 0         );
       
-- -------------------------------------------------------------------------------------------------
-- Table structure for table `cities`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE cities (
  name                  varchar(100) NOT NULL,
  twc_code              varchar(50),
  wu_link               varchar(50)
  ,
  PRIMARY KEY (name)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `client_profiles`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE client_profiles (
  player_id                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  make                      varchar(3) NOT NULL,
  model                     smallint(3) NOT NULL,
  name                      varchar(50) NOT NULL,
  chipset                   varchar(10) NOT NULL,
  resume                    varchar(3) NOT NULL,
  pod_sync                  smallint(2) NOT NULL,
  pod_no_sync               smallint(2) NOT NULL,
  pod_stream                smallint(2) NOT NULL,
  transition                smallint(2) NOT NULL
  ,
  PRIMARY KEY (player_id),
  KEY make (make),
  KEY model (model)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `clients`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE clients (
  client_id                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  ip_address                varchar(100) NOT NULL DEFAULT '',
  box_id                    varchar(100) DEFAULT NULL,
  user_id                   int(10) unsigned DEFAULT NULL,
  agent_string              text,
  device_type               text,
  last_seen                 datetime DEFAULT NULL,
  screen_type               text,
  aspect                    text,
  screen_x_res              int(10) unsigned DEFAULT NULL,
  screen_y_res              int(10) unsigned DEFAULT NULL,
  browser_x_res             int(10) unsigned DEFAULT NULL,
  browser_y_res             int(10) unsigned DEFAULT NULL,
  browser_scr_x_res         int(10) unsigned DEFAULT NULL,
  browser_scr_y_res         int(10) unsigned DEFAULT NULL,
  port                      int(10) unsigned DEFAULT NULL,
  mac_addr                  varchar(17) DEFAULT NULL
  ,
  PRIMARY KEY (client_id),
  KEY ip_address (ip_address),
  FOREIGN KEY (user_id) references users (user_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `genres`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE genres (
  genre_id                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  genre_name                text
  ,
  PRIMARY KEY (genre_id),
  UNIQUE KEY genre_name (genre_name(100))
) ENGINE=InnoDB;

CREATE TABLE genres_of_movie (
  movie_id                  int(10) unsigned NOT NULL,
  genre_id                  int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (movie_id,genre_id),
  FOREIGN KEY (genre_id) REFERENCES genres (genre_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE genres_of_tv (
  programme_id              int(10) unsigned NOT NULL,
  episode_id                int(10) unsigned DEFAULT NULL,
  genre_id                  int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (programme_id,episode_id,genre_id),
  FOREIGN KEY (genre_id) REFERENCES genres (genre_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `internet_urls`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE internet_urls (
  url_id                    int(10) unsigned NOT NULL AUTO_INCREMENT,
  type                      int(10) unsigned NOT NULL DEFAULT 0,
  url                       text NOT NULL,
  title                     varchar(50) NOT NULL DEFAULT '',
  cat_id                    int(10) unsigned NOT NULL DEFAULT 1,
  certificate               int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (url_id),
  KEY cat_id (cat_id),
  KEY certificate (certificate)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `iradio_countries`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE iradio_countries (
  id                        int(10) unsigned NOT NULL AUTO_INCREMENT,
  country                   text NOT NULL
  ,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

INSERT INTO iradio_countries (country) VALUES ('germany');
INSERT INTO iradio_countries (country) VALUES ('russia');
INSERT INTO iradio_countries (country) VALUES ('italy');
INSERT INTO iradio_countries (country) VALUES ('spain');
INSERT INTO iradio_countries (country) VALUES ('france');
INSERT INTO iradio_countries (country) VALUES ('usa');
INSERT INTO iradio_countries (country) VALUES ('england');
INSERT INTO iradio_countries (country) VALUES ('united kingdom');
INSERT INTO iradio_countries (country) VALUES ('brazil');
INSERT INTO iradio_countries (country) VALUES ('denmark');
INSERT INTO iradio_countries (country) VALUES ('poland');
INSERT INTO iradio_countries (country) VALUES ('netherlands');
INSERT INTO iradio_countries (country) VALUES ('sweden');
INSERT INTO iradio_countries (country) VALUES ('norway');
INSERT INTO iradio_countries (country) VALUES ('greece');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `iradio_genres`, official SHOUTcast genres
-- -------------------------------------------------------------------------------------------------

CREATE TABLE iradio_genres (
  id                        int(10) unsigned NOT NULL AUTO_INCREMENT,
  genre                     text NOT NULL,
  subgenre                  text NOT NULL,
  PRIMARY KEY (id),
  KEY genre (genre(20))
) ENGINE=InnoDB;

INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','adult alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','britpop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','classic alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','college');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','dancepunk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','dream pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','emo');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','goth');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','grunge');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','hardcore');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','indie pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','indie rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','industrial');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','lo-fi');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','modern rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','new wave');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','noise pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','post-punk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','power pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','punk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','ska');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','xtreme');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','acoustic blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','cajun/zydeco');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','chicago blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','contemporary blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','country blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','delta blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','electric blues');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','classical');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','baroque');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','chamber');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','choral');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','classical period');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','early classical');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','impressionist');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','modern');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','opera');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','piano');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','romantic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','symphony');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','alt-country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','americana');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','bluegrass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','classic country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','contemporary bluegrass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','contemporary country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','honky tonk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','hot country hits');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','western');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','decades');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','30s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','40s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','50s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','60s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','70s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','80s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','90s');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','easy listening');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','exotica');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','light rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','lounge');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','orchestral pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','polka');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','space age pop');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','electronic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','acid house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','ambient');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','big beat');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','breakbeat');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','dance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','demo');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','disco');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','down tempo');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','drum and bass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','electro');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','garage');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','hard house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','idm');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','jungle');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','progressive');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','techno');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','trance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','tribal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','trip hop');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','alternative folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','contemporary folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','folk rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','new acoustic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','traditional folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','world folk');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','inspirational');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','classic christian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','contemporary gospel');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','gospel');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','praise/worship');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','sermons/services');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','southern gospel');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','traditional gospel');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','international');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','african');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','arabic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','asian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','bollywood');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','brazilian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','caribbean');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','celtic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','chinese');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','european');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','filipino');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','french');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','greek');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','hawaiian/pacific');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','hindi');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','indian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','japanese');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','jewish');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','klezmer');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','korean');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','mediterranean');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','middle eastern');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','north american');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','russian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','soca');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','south american');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','tamil');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','worldbeat');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','zouk');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','acid jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','avant garde');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','big band');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','bop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','classic jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','cool jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','fusion');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','hard bop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','latin jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','smooth jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','swing');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','vocal jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','world fusion');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','bachata');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','banda');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','bossa nova');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','cumbia');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin dance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin rap/hip-hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','mariachi');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','merengue');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','ranchera');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','reggaeton');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','regional mexican');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','salsa');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','tango');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','tejano');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','tropicalia');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','black metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','classic metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','extreme metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','grindcore');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','hair metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','heavy metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','metalcore');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','power metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','progressive metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','rap metal');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('misc','misc');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','new age');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','environmental');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','ethnic fusion');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','healing');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','meditation');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','spiritual');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','adult contemporary');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','barbershop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','bubblegum pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','dance pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','idols');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','jpop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','oldies');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','soft rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','teen pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','top 40');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','world pop');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','public radio');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','college');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','news');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','sports');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','talk');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','r&b/urban');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','classic r&b');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','doo wop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','funk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','motown');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','neo-soul');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','quiet storm');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','soul');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','urban contemporary');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','alternative rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','dirty south');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','east coast rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','freestyle');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','gangsta rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','hip hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','mixtapes');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','old school');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','turntablism');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','underground hip-hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','west coast rap');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','contemporary reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','dancehall');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','dub');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','pop-reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','ragga');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','reggae roots');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','rock steady');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','adult album alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','british invasion');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','classic rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','garage rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','glam');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','hard rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','jam bands');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','piano rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','prog rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','psychedelic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rock & roll');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rockability');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','singer/songwriter');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','surf');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','seasonal/holiday');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','anniversary');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','birthday');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','christmas');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','halloween');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','hanukkah');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','honeymoon');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','kwanzaa');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','valentine');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','wedding');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','winter');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','soundtracks');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','anime');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','kids');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','original score');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','showtunes');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','video game music');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','talk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','blogtalk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','comedy');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','community');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','educational');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','government');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','news');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','old time radio');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','other talk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','political');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','scanner');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','spoken word');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','sports');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','technology');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','themes');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','adult');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','best of');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','chill');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','eclectic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','experimental');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','female');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','heartache');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','instrumental');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','lgbt');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','love/romance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','party mix');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','patriotic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','rainy day mix');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','reality');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','sexy');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','shuffle');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','travel mix');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','tribute');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','trippy');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','work mix');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `iradio_stations`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE iradio_stations (
  id                        int(10) unsigned NOT NULL AUTO_INCREMENT,
  iradio_type               int(10) unsigned NOT NULL DEFAULT 1,
  station                   text NOT NULL,
  image                     text
  ,
  PRIMARY KEY (id),
  KEY iradio_type (iradio_type)
) ENGINE=InnoDB;

INSERT INTO iradio_stations (station,image) VALUES ('.977','977_logo.gif');
INSERT INTO iradio_stations (station,image) VALUES ('Absolute Classic Rock','virgin_logo.gif');
INSERT INTO iradio_stations (station,image) VALUES ('Absolute Radio','virgin_logo.gif');
INSERT INTO iradio_stations (station,image) VALUES ('Avro','avro_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('Disney Tunes Radio Network','disney_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('Sky.FM','sky_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('.Kink','kink_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('Big R Radio','bigr_logo.jpg');
INSERT INTO iradio_stations (station,image) VALUES ('D i g i t a l l y - I m p o r t e d','di_logo.gif');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `itunes_map`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE itunes_map (
  itunes_id                 int(10) unsigned NOT NULL,
  swisscenter_id            int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (itunes_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `languages`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE languages (
  language_id               int(10) unsigned NOT NULL AUTO_INCREMENT,
  language                  text
  ,
  PRIMARY KEY (language_id),
  UNIQUE KEY language (language(100))
) ENGINE=InnoDB;

CREATE TABLE languages_of_movie (
  movie_id                  int(10) unsigned NOT NULL,
  language_id               int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (movie_id,language_id),
  FOREIGN KEY (language_id) REFERENCES languages (language_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE languages_of_tv (
  programme_id              int(10) unsigned NOT NULL,
  episode_id                int(10) unsigned DEFAULT NULL,
  language_id               int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (programme_id,episode_id,language_id),
  FOREIGN KEY (language_id) REFERENCES languages (language_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `lastfm_scrobble_tracks`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE lastfm_scrobble_tracks (
  scrobble_id               int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id                   int(10) unsigned NOT NULL,
  player_id                 varchar(17) NOT NULL,
  artist                    text NOT NULL,
  title                     text NOT NULL,
  album                     text,
  length                    int(10) unsigned NOT NULL,
  track                     int(10) unsigned DEFAULT NULL,
  play_start                int(10) unsigned NOT NULL,
  play_end                  int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (scrobble_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  KEY player_id (player_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `lastfm_tags`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE lastfm_tags (
  tag                       varchar(50) NOT NULL DEFAULT '',
  count                     int(10) unsigned DEFAULT 0,
  url                       varchar(255) DEFAULT NULL
  ,
  PRIMARY KEY (tag)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_types`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_types (
  media_id                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  media_name                varchar(20) DEFAULT NULL,
  media_table               varchar(20) DEFAULT NULL
  ,
  PRIMARY KEY (media_id)
) ENGINE=InnoDB;

INSERT INTO media_types (media_id,media_name,media_table) VALUES (1,'Music','media_audio'),
                                                                 (2,'Photo','media_photos'),
                                                                 (3,'Video','media_videos'),
                                                                 (4,'Radio',null),
                                                                 (5,'Web',null),
                                                                 (6,'TV Series','media_tv'),
                                                                 (7,'Internet TV',null);

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_locations`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_locations (
  location_id               int(10) unsigned NOT NULL AUTO_INCREMENT,
  name                      text,
  media_type                int(10) unsigned DEFAULT NULL,
  download_info             char(1) DEFAULT 'Y',
  cat_id                    int(10) unsigned NOT NULL DEFAULT '1',
  unrated                   int(10) unsigned NOT NULL DEFAULT '1',
  percent_scanned           int(10) unsigned DEFAULT NULL,
  network_share             text
  ,
  PRIMARY KEY (location_id),
  FOREIGN KEY (cat_id) REFERENCES categories (cat_id),
  FOREIGN KEY (media_type) REFERENCES media_types (media_id)
) ENGINE=InnoDB;

CREATE TABLE user_media_locations (
  user_id                   int(10) unsigned NOT NULL,
  location_id               int(10) unsigned NOT NULL
  ,
  PRIMARY KEY (user_id,location_id),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_artwork`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_artwork (
  image_id                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  art_sha1                  varchar(40) NOT NULL,
  mime_type                 varchar(25) NOT NULL,
  image                     mediumblob NOT NULL
  ,
  PRIMARY KEY (image_id),
  UNIQUE KEY art_sha1 (art_sha1)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_audio`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_audio (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  dirname                   text,
  filename                  text,
  file_ext                  varchar(10),
  location_id               int(10) unsigned DEFAULT NULL,
  size                      bigint(20) unsigned DEFAULT NULL,
  timestamp                 datetime DEFAULT NULL,
  discovered                datetime DEFAULT NULL,
  verified                  char(1) DEFAULT NULL,
  certificate               int(10) unsigned DEFAULT NULL,
  length                    int(10) unsigned DEFAULT NULL,
  lengthstring              varchar(10),
  version                   text,
  title                     text,
  sort_title                text,
  artist                    text,
  sort_artist               text,
  band                      text,
  sort_band                 text,
  album                     text,
  sort_album                text,
  composer                  text,
  year                      text,
  track                     int(10) unsigned DEFAULT NULL,
  disc                      int(10) unsigned DEFAULT NULL,
  genre                     text,
  mood                      text,
  publisher                 text,
  lyrics                    text,
  encoder                   text,
  comment                   text,
  mb_id                     int(10) unsigned DEFAULT NULL,
  bitrate                   int(10) unsigned DEFAULT NULL,
  bitrate_mode              varchar(10),
  channels                  int(10) unsigned DEFAULT NULL,
  channel_mode              varchar(10),
  sample_rate               int(10) unsigned DEFAULT NULL,
  image_id                  int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (file_id),
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE,
  UNIQUE KEY media_audio_fsp_u1 (dirname(250),filename(80)),
  KEY file_ext (file_ext),
  KEY location_id (location_id),
  KEY sort_title (sort_title(50)),
  KEY sort_artist (sort_artist(50)),
  KEY sort_album (sort_album(50)),
  KEY sort_band (sort_band(50)),
  KEY composer (composer(50)),
  KEY genre (genre(50)),
  KEY mood (mood(50)),
  KEY year (year(50))
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_photos`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_photos (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  dirname                   text,
  filename                  text,
  file_ext                  varchar(10),
  location_id               int(10) unsigned DEFAULT NULL,
  size                      bigint(20) unsigned DEFAULT NULL,
  timestamp                 datetime DEFAULT NULL,
  discovered                datetime DEFAULT NULL,
  verified                  char(1) DEFAULT NULL,
  certificate              int(10) unsigned DEFAULT NULL,
  width                     int(10) unsigned DEFAULT NULL,
  height                    int(10) unsigned DEFAULT NULL,
  date_modified             varchar(50) DEFAULT NULL,
  date_created              varchar(50) DEFAULT NULL,
  iptc_byline               text,
  iptc_location             text,
  iptc_city                 text,
  iptc_province_state       text,
  iptc_country              text,
  iptc_suppcategory         text,
  iptc_keywords             text,
  iptc_caption              text,
  iptc_date_created         text,
  xmp_rating                text,
  exif_exposure_mode        text,
  exif_exposure_time        text,
  exif_fnumber              text,
  exif_focal_length         text,
  exif_image_source         text,
  exif_make                 text,
  exif_model                text,
  exif_orientation          text,
  exif_white_balance        text,
  exif_flash                text,
  exif_iso                  text,
  exif_light_source         text,
  exif_exposure_prog        text,
  exif_meter_mode           text,
  exif_capture_type         text
  ,
  PRIMARY KEY (file_id),
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE,
  UNIQUE KEY media_photos_fsp_u1 (dirname(250),filename(80)),
  KEY file_ext (file_ext),
  KEY location_id (location_id),
  KEY iptc_byline (iptc_byline(50)),
  KEY iptc_location (iptc_location(50)),
  KEY iptc_city (iptc_city(50)),
  KEY iptc_province_state (iptc_province_state(50)),
  KEY iptc_country (iptc_country(50))
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Create table to hold photo "albums" information.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_photo_albums (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  dirname                   text,
  title                     text,
  sort_title                text,
  verified                  char(1) DEFAULT NULL,
  discovered                datetime DEFAULT NULL,
  location_id               int(10) unsigned DEFAULT NULL,
  certificate               int(10) unsigned DEFAULT NULL,
  timestamp                 datetime DEFAULT NULL
  ,
  PRIMARY KEY (file_id),
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE,
  KEY sort_title (sort_title(50)),
  KEY location_id (location_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_tv`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_tv (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  dirname                   text,
  filename                  text,
  file_ext                  varchar(10),
  title                     text,
  sort_title                text,
  programme                 text,
  sort_programme            text,
  series                    int(10) unsigned DEFAULT NULL,
  episode                   int(10) unsigned DEFAULT NULL,
  programme_id              int(10) unsigned DEFAULT NULL,
  episode_id                int(10) unsigned DEFAULT NULL,
  location_id               int(10) unsigned DEFAULT NULL,
  size                      bigint(20) unsigned DEFAULT NULL,
  timestamp                 datetime DEFAULT NULL,
  discovered                datetime DEFAULT NULL,
  verified                  char(1) DEFAULT NULL,
  length                    int(10) unsigned DEFAULT NULL,
  lengthstring              varchar(10),
  source                    varchar(10) DEFAULT NULL,
  audio_channels            int(10) unsigned DEFAULT NULL,
  audio_codec               text,
  video_codec               text,
  video_aspect              varchar(10) DEFAULT NULL,
  resolution                varchar(10) DEFAULT NULL,
  frame_rate                varchar(10) DEFAULT NULL,
  encoder                   text,
  os_hash                   varchar(40) DEFAULT NULL,
  image_id                  int(10) unsigned DEFAULT NULL,
  details_available         char(1) DEFAULT NULL,
  match_pc                  int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (file_id),
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE,
  UNIQUE KEY media_tv_fsp_u1 (dirname(250),filename(250)),
  KEY sort_title (sort_title(50)),
  KEY sort_programme (sort_programme(50)),
  KEY programme_id (programme_id),
  KEY episode_id (episode_id),
  KEY location_id (location_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_videos`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_videos (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  dirname                   text,
  filename                  text,
  file_ext                  varchar(10),
  title                     text,
  sort_title                text,
  movie_id                  int(10) unsigned DEFAULT NULL,
  location_id               int(10) unsigned DEFAULT NULL,
  size                      bigint(20) unsigned DEFAULT NULL,
  timestamp                 datetime DEFAULT NULL,
  discovered                datetime DEFAULT NULL,
  verified                  char(1) DEFAULT NULL,
  length                    int(10) unsigned DEFAULT NULL,
  lengthstring              text,
  source                    varchar(10) DEFAULT NULL,
  audio_channels            int(10) unsigned DEFAULT NULL,
  audio_codec               text,
  video_codec               text,
  video_aspect              varchar(10) DEFAULT NULL,
  resolution                varchar(10) DEFAULT NULL,
  frame_rate                varchar(10) DEFAULT NULL,
  encoder                   text,
  os_hash                   varchar(40) DEFAULT NULL,
  image_id                  int(10) unsigned DEFAULT NULL,
  details_available         char(1) DEFAULT NULL,
  match_pc                  int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (file_id),
  FOREIGN KEY (location_id) REFERENCES media_locations (location_id) ON DELETE CASCADE,
  UNIQUE KEY media_videos_fsp_u1 (dirname(250),filename(80)),
  KEY file_ext (file_ext),
  KEY sort_title (sort_title(50)),
  KEY movie_id (movie_id),
  KEY location_id (location_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `media_subtitles`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE media_subtitles (
  subtitle_id               int(10) unsigned NOT NULL AUTO_INCREMENT,
  file_id                   int(10) unsigned NOT NULL,
  media_type                int(10) unsigned NOT NULL,
  dirname                   text,
  filename                  text,
  file_ext                  varchar(10),
  lang                      varchar(10),
  size                      bigint(20) unsigned DEFAULT NULL,
  timestamp                 datetime DEFAULT NULL,
  discovered                datetime DEFAULT NULL,
  verified                  char(1) DEFAULT NULL
  ,
  PRIMARY KEY (subtitle_id),
  FOREIGN KEY (media_type) REFERENCES media_types (media_id),
  UNIQUE KEY media_subtitle_fsp_u1 (dirname(250),filename(80))
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `messages`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE messages (
  message_id              int(10) unsigned NOT NULL auto_increment,
  title                   varchar(255),
  added                   datetime NOT NULL,
  message_text            text,
  status                  int unsigned DEFAULT 0
  ,
  PRIMARY KEY (message_id)
) ENGINE=InnoDB;

insert into messages (title,message_text,added) values ('Welcome to the Swisscenter','This is the messages section, where you will be informed of new features and updates to the SwissCenter interface whenever you perform an automatic update.',now());

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `movies`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE movies (
  movie_id                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  set_id                    int(10) unsigned DEFAULT NULL,
  tmdb_id                   int(10) unsigned DEFAULT NULL,
  imdb_id                   int(10) unsigned DEFAULT NULL,
  title                     text,
  sort_title                text,
  synopsis                  text,
  tagline                   text,
  certificate               int(10) unsigned DEFAULT NULL,
  vote_average              int(10) unsigned DEFAULT NULL,
  vote_count                int(10) unsigned DEFAULT NULL,
  release_date              date DEFAULT NULL,
  budget                    bigint(20) unsigned DEFAULT NULL,
  revenue                   bigint(20) unsigned DEFAULT NULL,
  trailer                   text
  ,
  PRIMARY KEY (movie_id),
  FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL,
  KEY set_id (set_id),
  KEY sort_title (sort_title(50))
) ENGINE=InnoDB;

CREATE TABLE media_sets (
  set_id                    int(10) unsigned NOT NULL AUTO_INCREMENT,
  tmdb_id                   int(10) unsigned DEFAULT NULL,
  imdb_id                   int(10) unsigned DEFAULT NULL,
  title                     text,
  sort_title                text,
  overview                  text,
  external_rating_pc        int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (set_id),
  KEY sort_title (sort_title(50))
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `tv_series`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE tv_programmes (
  programme_id              int(10) unsigned NOT NULL AUTO_INCREMENT,
  tvdb_id                   int(10) unsigned DEFAULT NULL,
  imdb_id                   int(10) unsigned DEFAULT NULL,
  tvrage_id                 int(10) unsigned DEFAULT NULL,
  programme                 text,
  sort_programme            text,
  overview                  text,
  vote_average              int(10) unsigned DEFAULT NULL,
  vote_count                int(10) unsigned DEFAULT NULL,
  start_date                date DEFAULT NULL,
  end_date                  date DEFAULT NULL,
  next_episode_date         date DEFAULT NULL,
  status                    text,
  network                   text,
  country                   text
  ,
  PRIMARY KEY (programme_id),
  KEY sort_programme (sort_programme(50))
) ENGINE=InnoDB;

CREATE TABLE tv_episodes (
  episode_id                int(10) unsigned NOT NULL AUTO_INCREMENT,
  programme_id              int(10) unsigned NOT NULL,
  tvdb_id                   int(10) unsigned DEFAULT NULL,
  imdb_id                   int(10) unsigned DEFAULT NULL,
  tvrage_id                 int(10) unsigned DEFAULT NULL,
  series                    int(10) unsigned DEFAULT NULL,
  episode                   int(10) unsigned DEFAULT NULL,
  title                     text,
  sort_title                text,
  synopsis                  text,
  certificate               int(10) unsigned DEFAULT NULL,
  vote_average              int(10) unsigned DEFAULT NULL,
  vote_count                int(10) unsigned DEFAULT NULL,
  aired_date                date DEFAULT NULL,
  airsafter_season          int(10) unsigned DEFAULT NULL,
  airsbefore_episode        int(10) unsigned DEFAULT NULL,
  airsbefore_season         int(10) unsigned DEFAULT NULL,
  production_code           varchar(25) DEFAULT NULL
  ,
  PRIMARY KEY (episode_id),
  FOREIGN KEY (certificate) REFERENCES certificates(cert_id) ON DELETE SET NULL,
  KEY programme_id (programme_id),
  KEY sort_title (sort_title(50)),
  KEY series (series),
  KEY episode (episode)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `people`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE people (
  people_id                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  firstname                 varchar(50),
  lastname                  varchar(50),
  birthdate                 date DEFAULT NULL,
  biography                 text
  ,
  PRIMARY KEY (people_id),
  UNIQUE KEY people_u1 (firstname(50),lastname(50),birthdate)
) ENGINE=InnoDB;

CREATE TABLE people_in_movie (
  movie_id                  int(10) unsigned NOT NULL,
  people_id                 int(10) unsigned NOT NULL,
  people_role_type_id       int(10) unsigned NOT NULL,
  role                      varchar(100)
  ,
  PRIMARY KEY (people_id,movie_id,people_role_type_id),
  FOREIGN KEY (movie_id) REFERENCES movies (movie_id) ON DELETE CASCADE,
  FOREIGN KEY (people_id) REFERENCES people (people_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE people_in_tv (
  programme_id              int(10) unsigned NOT NULL,
  episode_id                int(10) unsigned DEFAULT NULL,
  people_id                 int(10) unsigned NOT NULL,
  people_role_type_id       int(10) unsigned NOT NULL,
  role                      varchar(100)
  ,
  PRIMARY KEY (people_id,programme_id,episode_id,people_role_type_id),
  FOREIGN KEY (programme_id) REFERENCES tv_programmes (programme_id) ON DELETE CASCADE,
  FOREIGN KEY (episode_id) REFERENCES tv_episodes (episode_id) ON DELETE CASCADE,
  FOREIGN KEY (people_id) REFERENCES people (people_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE people_credits (
  credit_id                 int(10) unsigned NOT NULL,
  people_id                 int(10) unsigned NOT NULL,
  people_role_type_id       int(10) unsigned NOT NULL,
  role                      varchar(100),
  title                     text
  ,
  PRIMARY KEY (credit_id),
  FOREIGN KEY (people_id) REFERENCES people (people_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `people_roles`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE people_role_types (
  people_role_type_id       int(10) unsigned NOT NULL AUTO_INCREMENT,
  people_role_type          varchar(25)
  ,
  PRIMARY KEY (people_role_type_id)
) ENGINE=InnoDB;

INSERT INTO people_role_types (people_role_type_id,people_role_type) VALUES (1,'Actor');
INSERT INTO people_role_types (people_role_type_id,people_role_type) VALUES (2,'Director');
INSERT INTO people_role_types (people_role_type_id,people_role_type) VALUES (3,'Writer');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `rss_subscriptions`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE rss_subscriptions (
  rss_sub_id              int(10) unsigned NOT NULL AUTO_INCREMENT,
  cat_id                  int(10) unsigned NOT NULL DEFAULT 1,
  type                    int(10) unsigned NOT NULL,
  url                     text NOT NULL,
  title                   varchar(50) NOT NULL,
  description             text,
  image_url               text,
  image                   MEDIUMBLOB,
  update_frequency        int(10) unsigned NOT NULL DEFAULT 60,
  cache                   int(10) unsigned NOT NULL DEFAULT 16,
  last_update             datetime DEFAULT NULL,
  percent_scanned         int(10) unsigned DEFAULT NULL
  ,
  PRIMARY KEY (rss_sub_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `rss_items`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE rss_items (
  rss_item_id             int(10) unsigned NOT NULL AUTO_INCREMENT,
  rss_sub_id              int(10) unsigned NOT NULL,
  title                   text NOT NULL,
  url                     text,
  description             text NOT NULL,
  published_date          datetime NOT NULL,
  timestamp               text NOT NULL,
  guid                    text,
  linked_file             text
  ,
  PRIMARY KEY (rss_item_id),
  FOREIGN KEY (rss_sub_id) REFERENCES rss_subscriptions (rss_sub_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `themes`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE themes (
  file_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  title                     text NOT NULL,
  sort_title                text NOT NULL,
  media_type                int unsigned NOT NULL,
  thumb_cache               text NOT NULL,
  original_url              text NOT NULL,
  resolution                varchar(10) DEFAULT NULL,
  colors                    text,
  flip_image                tinyint(1) NOT NULL DEFAULT 0,
  greyscale                 tinyint(1) NOT NULL DEFAULT 0,
  use_series                tinyint(1) NOT NULL DEFAULT 0,
  use_synopsis              tinyint(1) NOT NULL DEFAULT 0,
  show_banner               tinyint(1) NOT NULL DEFAULT 0,
  show_image                tinyint(1) NOT NULL DEFAULT 0,
  displayed                 tinyint(1) NOT NULL DEFAULT 0,
  download_path             text,
  processed_image           text,
  original_cache            text
  ,
  PRIMARY KEY (file_id),
  KEY sort_title (sort_title(50)),
  KEY media_type (media_type)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Table structure for table 'toma_channels' to contain channel data from TOMA.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE toma_channels (
  toma_id                     int(10) unsigned NOT NULL AUTO_INCREMENT,
  name                        varchar(50),
  description                 text,
  homepage                    text,
  stream_url                  text,
  country                     varchar(50),
  category                    varchar(50),
  media_type                  char(2),
  bitrate                     int(10),
  rating                      int(10),
  votes                       int(10),
  works_fine                  int(10),
  not_working                 int(10),
  wrong_info                  int(10),
  duplicate                   int(10),
  spam                        int(10)
  ,
  PRIMARY KEY (toma_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Create a table to contain regular expressions for pulling metadata from a TV series media file
-- by means of it's path and filename. Populate it with the initial common expressions.
-- -------------------------------------------------------------------------------------------------

CREATE TABLE tv_expressions (
  pos                       int(10) unsigned NOT NULL,
  expression                text
  ,
  PRIMARY KEY (pos)
) ENGINE=InnoDB;

INSERT INTO tv_expressions (pos, expression) VALUES ( 1,'{p}/[^/]*/.*\W+s{s}e{e}\W+{t}'),
                                                    ( 2,'{p}\W+s{s}e{e}\W+{t}'),
                                                    ( 3,'{p}\W+{s}x{e}\W+{t}'),
                                                    ( 4,'{p}/series {s}/{e}\W+{t}'),
                                                    ( 5,'{p}/season {s}/{e}\W+{t}'),
                                                    ( 6,'{p}\W+s{s}e{e}'),
                                                    ( 7,'{p}\W+{s}x{e}'),
                                                    ( 8,'{p}/{s}/{e}\W*{t}'),
                                                    ( 9,'{p}/{e}\W+{t}'),
                                                    (10,'{p}/{t}\W+\(?s{s}e{e}\)?'),
                                                    (11,'{p}/{t}\W+\(?{s}x{e}\)?'),
                                                    (12,'{p}\W+s{s}e{e}'),
                                                    (13,'{p}\W+{s}x{e}'),
                                                    (14,'{p}/{t}'),
                                                    (15,'{t}');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `tvid_prefs`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE tvid_prefs (
  tvid_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  player_type               varchar(30) NOT NULL,
  tvid_sc                   varchar(20) NOT NULL,
  tvid_default              varchar(20),
  tvid_custom               varchar(20)
  ,
  PRIMARY KEY (tvid_id),
  KEY player_type (player_type),
  KEY tvid_sc (tvid_sc)
) ENGINE=InnoDB;

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('POP-402','KEY_A','RED'),
                                                                 ('POP-402','KEY_B','GREEN'),
                                                                 ('POP-402','KEY_C','YELLOW'),
                                                                 ('POP-402','BACKSPACE','BACK');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('PIN-101','PGUP','KEY_0'),
                                                                 ('PIN-101','PGDN','KEY_9');
                                                                 
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('PIN-230','KEY_A','A'),
                                                                 ('PIN-230','KEY_B','B'),
                                                                 ('PIN-230','KEY_C','C');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('HNB-260','BACKSPACE','CLEAR'),
                                                                 ('HNB-260','KEY_A','RED'),
                                                                 ('HNB-260','KEY_B','GREEN'),
                                                                 ('HNB-260','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('IOD-234','BACKSPACE','BACK'),
                                                                 ('IOD-234','KEY_A','PLAY'),
                                                                 ('IOD-234','KEY_B','ESC'),
                                                                 ('IOD-234','KEY_C','REPEAT');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('EGT-103','KEY_A','RED'),
                                                                 ('EGT-103','KEY_B','GREEN'),
                                                                 ('EGT-103','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('LTI-254','KEY_A','RED'),
                                                                 ('LTI-254','KEY_B','GREEN'),
                                                                 ('LTI-254','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NST-105','KEY_A','RED'),
                                                                 ('NST-105','KEY_B','GREEN'),
                                                                 ('NST-105','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('SYB-230','BACKSPACE','BACK'),
                                                                 ('SYB-230','KEY_A','RED'),
                                                                 ('SYB-230','KEY_B','GREEN'),
                                                                 ('SYB-230','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NGR-293','KEY_A','PLAY'),
                                                                 ('NGR-293','KEY_B','SETUP'),
                                                                 ('NGR-293','KEY_C','BLUE'),
                                                                 ('NGR-293','MUSIC','GREEN'),
                                                                 ('NGR-293','MOVIE','RED'),
                                                                 ('NGR-293','PHOTO','YELLOW'),
                                                                 ('NGR-293','HOME','SETUP');

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `viewings`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE viewings (
  user_id                   int(10) unsigned NOT NULL,
  media_id                  int(10) unsigned NOT NULL,
  media_type                int(10) unsigned NOT NULL,
  last_viewed               datetime NOT NULL,
  total_viewings            int(10) unsigned DEFAULT 0
  ,
  PRIMARY KEY (user_id,media_id,media_type),
  FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX viewings_n1 ON viewings (media_id);

-- -------------------------------------------------------------------------------------------------
-- Table structures for language translations
-- -------------------------------------------------------------------------------------------------

CREATE TABLE translate_languages (
  lang_id                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  ietf_tag                  varchar(10) NOT NULL,
  name                      text NOT NULL
  ,
  PRIMARY KEY (lang_id)
) ENGINE=InnoDB;

CREATE TABLE translate_keys (
  key_id                    int(10) unsigned NOT NULL AUTO_INCREMENT,
  text_id                   text NOT NULL,
  verified                  char(1) DEFAULT NULL
  ,
  PRIMARY KEY (key_id),
  UNIQUE KEY text_id (text_id(100))
) ENGINE=InnoDB;

CREATE TABLE translate_text (
  key_id                    int(10) unsigned NOT NULL,
  lang_id					int(10) unsigned NOT NULL,
  text                      text NOT NULL,
  version                   varchar(10)	
  ,
  PRIMARY KEY (key_id, lang_id),
  FOREIGN KEY (key_id) REFERENCES translate_keys (key_id) ON DELETE CASCADE,
  FOREIGN KEY (lang_id) REFERENCES translate_languages (lang_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------------------------------------------
-- Set the current database version number
-- -------------------------------------------------------------------------------------------------

INSERT INTO system_prefs (name, value) VALUES('DATABASE_VERSION','2.00');

-- -------------------------------------------------------------------------------------------------
-- Views
-- -------------------------------------------------------------------------------------------------

CREATE OR REPLACE VIEW vw_people_in_movie AS
  SELECT movie_id, people_role_type, role, concat_ws(' ',firstname,lastname) fullname, birthdate, biography 
    FROM people_in_movie pim, people p, people_role_types pt
    WHERE pim.people_role_type_id = pt.people_role_type_id AND pim.people_id = p.people_id;
    
CREATE OR REPLACE VIEW vw_genres_of_movie AS
  SELECT movie_id, genre_name 
  FROM genres g, genres_of_movie gom 
    WHERE gom.genre_id = g.genre_id;

CREATE OR REPLACE VIEW vw_languages_of_movie AS
  SELECT movie_id, language 
  FROM languages l, languages_of_movie lom 
    WHERE lom.language_id = l.language_id;
    
CREATE OR REPLACE VIEW vw_people_in_tv AS
  SELECT programme_id, episode_id, people_role_type, role, concat_ws(' ',firstname,lastname) fullname, birthdate, biography
    FROM people_in_tv pit, people p, people_role_types pt
    WHERE pit.people_role_type_id = pt.people_role_type_id AND pit.people_id = p.people_id;
    
CREATE OR REPLACE VIEW vw_genres_of_tv AS
  SELECT programme_id, episode_id, genre_name 
  FROM genres g, genres_of_tv got 
    WHERE got.genre_id = g.genre_id;

CREATE OR REPLACE VIEW vw_languages_of_tv AS
  SELECT programme_id, episode_id, language 
  FROM languages l, languages_of_tv lot 
    WHERE lot.language_id = l.language_id;
     
    
    