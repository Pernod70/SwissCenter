-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Add the FSK rating system from Germany to the certificates table.
-- -------------------------------------------------------------------------------------------------

INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'FSK 0',   5, 'FSK','Released without age restriction');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'FSK 6',  25, 'FSK','Released to age 6 or older');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'FSK 12', 40, 'FSK','Released to age 12 or older and to age 6 or older with parental guidance');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'FSK 16', 70, 'FSK','Released to age 16 or older');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'FSK 18', 90, 'FSK','No release to youths (released to age 18 or older)');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'SPIO',  100, 'FSK','Rating denied');
