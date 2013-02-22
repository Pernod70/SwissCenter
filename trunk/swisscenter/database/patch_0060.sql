-- -------------------------------------------------------------------------------------------------
-- Add sort columns to media tables
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `movies` 
ADD `sort_title` VARCHAR(255) AFTER `title`,
ADD INDEX sort_title(`sort_title`(50));

UPDATE `movies` SET `sort_title` = trim_article(`title`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));

ALTER TABLE `mp3s` 
ADD `sort_title` VARCHAR(255) AFTER `title`,
ADD INDEX sort_title(`sort_title`(50)),
ADD `sort_artist` VARCHAR(255) AFTER `artist`,
ADD INDEX sort_artist(`sort_artist`(50)),
ADD `sort_album` VARCHAR(255) AFTER `album`,
ADD INDEX sort_album(`sort_album`(50)),
ADD `sort_band` VARCHAR(255) AFTER `band`,
ADD INDEX sort_band(`sort_band`(50));

UPDATE `mp3s` SET `sort_title` = trim_article(`title`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));
UPDATE `mp3s` SET `sort_artist` = trim_article(`artist`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));
UPDATE `mp3s` SET `sort_album` = trim_article(`album`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));
UPDATE `mp3s` SET `sort_band` = trim_article(`band`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));

ALTER TABLE `photo_albums` 
ADD `sort_title` VARCHAR(255) AFTER `title`,
ADD INDEX sort_title(`sort_title`(50));

UPDATE `photo_albums` SET `sort_title` = trim_article(`title`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));

ALTER TABLE `tv` 
ADD `sort_title` VARCHAR(255) AFTER `title`,
ADD INDEX sort_title(`sort_title`(50)),
ADD `sort_programme` VARCHAR(255) AFTER `programme`,
ADD INDEX sort_programme(`sort_programme`(50));

UPDATE `tv` SET `sort_title` = trim_article(`title`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));
UPDATE `tv` SET `sort_programme` = trim_article(`programme`,(SELECT `value` FROM `system_prefs` WHERE `name`='IGNORE_ARTICLES'));
