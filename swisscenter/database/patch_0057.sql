-- -------------------------------------------------------------------------------------------------
-- Update 'size' column in all media tables to handle >2GB
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `movies` MODIFY `size` BIGINT(21) SIGNED;
ALTER TABLE `mp3s`   MODIFY `size` BIGINT(21) SIGNED;
ALTER TABLE `photos` MODIFY `size` BIGINT(21) SIGNED;
ALTER TABLE `tv`     MODIFY `size` BIGINT(21) SIGNED;