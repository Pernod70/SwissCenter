-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Table structure for table `tvid_prefs`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE tvid_prefs (
  tvid_id       int unsigned auto_increment primary key not null,
  player_type   varchar(30) NOT NULL,
  tvid_sc       varchar(20) NOT NULL,
  tvid_default  varchar(20),
  tvid_custom   varchar(20)
) TYPE=MyISAM;

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('PINNACLE SC200','KEY_A','A');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('PINNACLE SC200','KEY_B','B');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('PINNACLE SC200','KEY_C','C');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('H&B','BACKSPACE','CLEAR');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('H&B','KEY_A','RED');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('H&B','KEY_B','GREEN');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('H&B','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('IO-DATA','BACKSPACE','BACK');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('IO-DATA','KEY_A','PLAY');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('IO-DATA','KEY_B','ESC');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('IO-DATA','KEY_C','REPEAT');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('ELGATO','KEY_A','RED');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('ELGATO','KEY_B','GREEN');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('ELGATO','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('BUFFALO','KEY_A','RED');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('BUFFALO','KEY_B','GREEN');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('BUFFALO','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NEUSTON','KEY_A','RED');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NEUSTON','KEY_B','GREEN');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NEUSTON','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('SYABAS','BACKSPACE','BACK');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('SYABAS','KEY_A','RED');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('SYABAS','KEY_B','GREEN');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('SYABAS','KEY_C','BLUE');

INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','KEY_A','PLAY');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','KEY_B','SETUP');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','KEY_C','BLUE');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','MUSIC','GREEN');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','MOVIE','RED');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','PHOTO','YELLOW');
INSERT INTO tvid_prefs (player_type,tvid_sc,tvid_default) VALUES ('NETGEAR','HOME','SETUP');

DELETE FROM system_prefs WHERE name LIKE 'TVID_%';

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************