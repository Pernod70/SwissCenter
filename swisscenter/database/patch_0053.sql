-- -------------------------------------------------------------------------------------------------
-- Drop unique indexes from media tables
-- -------------------------------------------------------------------------------------------------

CALL drop_index_if_exists('movies', 'movies_fsp_u1');
CALL drop_index_if_exists('movies', 'tv_fsp_u1');
CALL drop_index_if_exists('tv', 'tv_fsp_u1');
CALL drop_index_if_exists('mp3s', 'mp3s_fsp_u1');
CALL drop_index_if_exists('photos', 'photos_fsp_u1');

-- -------------------------------------------------------------------------------------------------
-- Set default character set and collation
-- -------------------------------------------------------------------------------------------------

ALTER DATABASE `swiss` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- -------------------------------------------------------------------------------------------------
-- Update tables to utf8
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `actors` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `actors` 
MODIFY `actor_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `actors_in_movie` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `actors_in_tv` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `art_files` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `art_files` 
MODIFY `filename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `cache_api_request` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `cache_api_request` 
MODIFY `request` VARCHAR(35) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `service` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `response` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `categories` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `categories` 
MODIFY `cat_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `download_info` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'N';

ALTER TABLE `certificates` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `certificates` 
MODIFY `name` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `description` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `scheme` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `cities` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `cities` 
MODIFY `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
MODIFY `twc_code` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `client_profiles` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `client_profiles` 
MODIFY `make` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `chipset` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `resume` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `clients` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `clients` 
MODIFY `ip_address` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
MODIFY `box_id` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `agent_string` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `device_type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `screen_type` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `aspect` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `mac_addr` VARCHAR(17) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `directors` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `directors` 
MODIFY `director_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `directors_of_movie` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `directors_of_tv` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `genres` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `genres` 
MODIFY `genre_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `genres_of_movie` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `genres_of_tv` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `internet_urls` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `internet_urls` 
MODIFY `url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `title` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `iradio_countries` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `iradio_countries` 
MODIFY `country` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `iradio_genres` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `iradio_genres` 
MODIFY `genre` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `subgenre` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `iradio_stations` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `iradio_stations` 
MODIFY `station` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `image` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `itunes_map` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `languages` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `languages` 
MODIFY `language` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `languages_of_movie` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `languages_of_tv` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `lastfm_scrobble_tracks` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `lastfm_scrobble_tracks` 
MODIFY `player_id` VARCHAR(17) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `artist` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `album` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `lastfm_tags` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `lastfm_tags` 
MODIFY `tag` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
MODIFY `url` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `media_art` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `media_art` 
MODIFY `art_sha1` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE `media_locations` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `media_locations` 
MODIFY `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `download_info` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'Y',
MODIFY `network_share` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `media_types` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `media_types` 
MODIFY `media_name` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `media_table` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `messages` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `messages` 
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `added` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `message_text` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `movies` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `movies` 
MODIFY `dirname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `filename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `lengthstring` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `verified` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `year` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `details_available` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `synopsis` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `art_sha1` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `audio_codec` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `video_codec` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `resolution` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `frame_rate` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `trailer` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `os_hash` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mp3s` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `mp3s` 
MODIFY `dirname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `filename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `lengthstring` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `bitrate` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `version` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `artist` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `album` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `year` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `track` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `genre` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `verified` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `bitrate_mode` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `band` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `art_sha1` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `composer` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `mood` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `lyrics` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `publisher` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `involved_people_list` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `photos` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `photos` 
MODIFY `dirname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `filename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `date_modified` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `date_created` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `verified` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_exposure_mode` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_exposure_time` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_fnumber` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_focal_length` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_image_source` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_make` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_model` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_orientation` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_white_balance` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_flash` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_iso` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_light_source` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_exposure_prog` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_meter_mode` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `exif_capture_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_caption` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_suppcategory` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_keywords` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_city` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_province_state` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_country` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_byline` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_date_created` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `iptc_location` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `xmp_rating` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `photo_albums` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `photo_albums` 
MODIFY `dirname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `verified` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `rss_items` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `rss_items` 
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `url` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `guid` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `linked_file` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `rss_subscriptions` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `rss_subscriptions` 
MODIFY `url` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `image_url` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `system_prefs` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `system_prefs` 
MODIFY `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
MODIFY `value` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `themes` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `themes` 
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `thumb_cache` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `original_url` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `resolution` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `colors` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `original_cache` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `processed_image` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `toma_channels` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `toma_channels` 
MODIFY `name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `homepage` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `stream_url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `country` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `category` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `media_type` CHAR(2) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `tv` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `tv` 
MODIFY `dirname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `filename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `programme` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `lengthstring` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `verified` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `year` VARCHAR(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `details_available` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `synopsis` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `audio_codec` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `video_codec` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `resolution` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `frame_rate` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `os_hash` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `tvid_prefs` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `tvid_prefs` 
MODIFY `player_type` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `tvid_sc` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
MODIFY `tvid_default` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `tvid_custom` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `tv_expressions` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `tv_expressions` 
MODIFY `expression` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `users` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `users` 
MODIFY `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
MODIFY `pin` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `user_prefs` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE `user_prefs` 
MODIFY `name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
MODIFY `value` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `viewings` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- -------------------------------------------------------------------------------------------------
-- Add unique indexes to media tables
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `movies` ADD UNIQUE `movies_fsp_u1` (`dirname`(160), `filename`(160));
ALTER TABLE `tv` ADD UNIQUE `tv_fsp_u1` (`dirname`(160), `filename`(160));
ALTER TABLE `mp3s` ADD UNIQUE `mp3s_fsp_u1` (`dirname`(160), `filename`(160));
ALTER TABLE `photos` ADD UNIQUE `photos_fsp_u1` (`dirname`(160), `filename`(160));

-- -------------------------------------------------------------------------------------------------
-- Fix other indexes
-- -------------------------------------------------------------------------------------------------

CALL drop_index_if_exists('movies', 'dirname');
CALL drop_index_if_exists('movies', 'filename');
CALL drop_index_if_exists('tv', 'dirname');
CALL drop_index_if_exists('tv', 'filename');
CALL drop_index_if_exists('mp3s', 'dirname');
CALL drop_index_if_exists('mp3s', 'filename');
CALL drop_index_if_exists('photos', 'dirname');
CALL drop_index_if_exists('photos', 'filename');

ALTER TABLE `movies` ADD INDEX dirname(`dirname`);
ALTER TABLE `movies` ADD INDEX filename(`filename`);
ALTER TABLE `tv` ADD INDEX dirname(`dirname`);
ALTER TABLE `tv` ADD INDEX filename(`filename`);
ALTER TABLE `mp3s` ADD INDEX dirname(`dirname`);
ALTER TABLE `mp3s` ADD INDEX filename(`filename`);
ALTER TABLE `photos` ADD INDEX dirname(`dirname`);
ALTER TABLE `photos` ADD INDEX filename(`filename`);
