
-- Fixes a references to a non-existing table.
update media_types set media_table = null where media_name='Radio';

