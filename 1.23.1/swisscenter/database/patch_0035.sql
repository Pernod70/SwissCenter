-- -------------------------------------------------------------------------------------------------
-- Add video details to the movies and tv tables
-- -------------------------------------------------------------------------------------------------

ALTER TABLE movies ADD ( audio_channels INT         default null );
ALTER TABLE movies ADD ( audio_codec    TEXT        default null );
ALTER TABLE movies ADD ( video_codec    TEXT        default null );
ALTER TABLE movies ADD ( resolution     VARCHAR(10) default null );
ALTER TABLE movies ADD ( frame_rate     VARCHAR(10) default null );

ALTER TABLE tv ADD ( audio_channels INT         default null );
ALTER TABLE tv ADD ( audio_codec    TEXT        default null );
ALTER TABLE tv ADD ( video_codec    TEXT        default null );
ALTER TABLE tv ADD ( resolution     VARCHAR(10) default null );
ALTER TABLE tv ADD ( frame_rate     VARCHAR(10) default null );


