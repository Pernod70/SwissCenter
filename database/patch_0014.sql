-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Additional rss subscription details.
-- -------------------------------------------------------------------------------------------------

ALTER TABLE rss_subscriptions ADD cat_id INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE rss_subscriptions ADD description TEXT;
ALTER TABLE rss_subscriptions ADD cache INT UNSIGNED NOT NULL DEFAULT 16;
ALTER TABLE rss_subscriptions ADD image_url TEXT;
ALTER TABLE rss_subscriptions ADD image MEDIUMBLOB;

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************