DROP TABLE IF EXISTS clients;

CREATE TABLE clients (
  ip_address   varchar(100) NOT NULL default '',
  box_id       varchar(100),
  user_id      int(10) unsigned
  ,
  PRIMARY KEY  (ip_address),
  FOREIGN KEY (user_id) references users (user_id)
) TYPE=MyISAM;


DELETE FROM messages;
ALTER TABLE messages CHANGE deleted status int;
ALTER TABLE messages ALTER status SET DEFAULT 0;
INSERT INTO messages (message_id, title, added, message_text)
  VALUES
  (
    1
    ,'Welcome to the Swisscenter'
    ,now()
    ,'This is the messages section, where you will be informed of new features and updates to the SwissCenter interface whenever you perform an automatic update.'
  );
