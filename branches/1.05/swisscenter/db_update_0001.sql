ALTER TABLE messages CHANGE deleted status int;
UPDATE messages SET status=2 WHERE status is not null;
UPDATE messages SET status=0 WHERE status is null;
ALTER TABLE messages ALTER status SET DEFAULT 0;
