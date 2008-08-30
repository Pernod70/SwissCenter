-- -------------------------------------------------------------------------------------------------
-- Add a field onto the rss_subscriptions table to record the percentage scanned (for media searches)
-- -------------------------------------------------------------------------------------------------

ALTER TABLE rss_subscriptions ADD (percent_scanned integer);