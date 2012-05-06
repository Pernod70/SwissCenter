-- -------------------------------------------------------------------------------------------------
-- Table structure for table `client_profiles`
-- -------------------------------------------------------------------------------------------------

CREATE TABLE client_profiles (
  player_id INT(10)     UNSIGNED NOT NULL AUTO_INCREMENT,
  make         VARCHAR(3)  NOT NULL,
  model        SMALLINT(3) NOT NULL,
  name         VARCHAR(50) NOT NULL,
  chipset      VARCHAR(10) NOT NULL,
  resume       VARCHAR(3)  NOT NULL,
  pod_sync     SMALLINT(2) NOT NULL,
  pod_no_sync  SMALLINT(2) NOT NULL,
  pod_stream   SMALLINT(2) NOT NULL,
  transition   SMALLINT(2) NOT NULL
  ,
  PRIMARY KEY (player_id),
  KEY make    (make(3)),
  KEY model   (model)
) ENGINE=MyISAM;

-- -------------------------------------------------------------------------------------------------
-- Remove existing entries from 'clients' table
-- -------------------------------------------------------------------------------------------------

DELETE FROM clients;

-- -------------------------------------------------------------------------------------------------
-- Update `tvid_prefs` with the new player_types
-- -------------------------------------------------------------------------------------------------

UPDATE tvid_prefs SET player_type='HNB-260' WHERE player_type='H&B';
UPDATE tvid_prefs SET player_type='IOD-234' WHERE player_type='IO-DATA';
UPDATE tvid_prefs SET player_type='EGT-103' WHERE player_type='ELGATO';
UPDATE tvid_prefs SET player_type='LTI-254' WHERE player_type='BUFFALO';
UPDATE tvid_prefs SET player_type='NST-105' WHERE player_type='NEUSTON';
UPDATE tvid_prefs SET player_type='SYB-230' WHERE player_type='SYABAS';
UPDATE tvid_prefs SET player_type='PIN-230' WHERE player_type='PINNACLE SC200';
UPDATE tvid_prefs SET player_type='POP-402' WHERE player_type='POPCORN';
UPDATE tvid_prefs SET player_type='PIN-101' WHERE player_type='PINNACLE';
UPDATE tvid_prefs SET player_type='NGR-293' WHERE player_type='NETGEAR';