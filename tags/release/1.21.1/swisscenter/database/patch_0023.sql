-- -------------------------------------------------------------------------------------------------
-- Add fields to the "clients" table to store browser screen size.
-- -------------------------------------------------------------------------------------------------

ALTER TABLE clients ADD ( browser_scr_x_res INT UNSIGNED default null);
ALTER TABLE clients ADD ( browser_scr_y_res INT UNSIGNED default null);