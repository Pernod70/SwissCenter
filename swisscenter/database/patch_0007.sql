-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Add a parent id column to the categories table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE categories ADD (parent_id INT NOT NULL DEFAULT 0);
INSERT INTO categories (cat_name, parent_id) VALUES ('-None-', -1);
UPDATE categories SET cat_id=0 WHERE cat_name='-None-';


-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************





