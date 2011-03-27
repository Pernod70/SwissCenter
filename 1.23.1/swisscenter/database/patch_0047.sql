-- -------------------------------------------------------------------------------------------------
-- Add the Kijkwijzer rating system from the Netherlands to the certificates table.
-- -------------------------------------------------------------------------------------------------

INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'AL',    5, 'Kijkwijzer','Not harmful / All Ages');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( '6',    25, 'Kijkwijzer','Watch out with children under 6');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'MG6',  30, 'Kijkwijzer','Watch out with children under 6, parental guidance advised');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( '9',    35, 'Kijkwijzer','Watch out with children under 9');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( '12',   40, 'Kijkwijzer','Watch out with children under 12');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( '16',   70, 'Kijkwijzer','Watch out with children under 16');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( '18',   90, 'Kijkwijzer','Watch out with children under 18');
INSERT INTO certificates (name, rank, scheme, description) VALUES ( 'XXX', 100, 'Kijkwijzer','Adult');


