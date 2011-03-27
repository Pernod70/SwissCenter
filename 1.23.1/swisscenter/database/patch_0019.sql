-- Add a column to the preference tables to track the date + time the preference was last modified.

alter table system_prefs add (modified datetime);
alter table user_prefs add (modified datetime);