-- -------------------------------------------------------------------------------------------------
-- Create stored function 'trim_article' which will remove leading words from 'title' specified
-- in the comma-separated 'articles' list. 
-- -------------------------------------------------------------------------------------------------

CREATE FUNCTION trim_article(title TEXT, articles TEXT) RETURNS text
    DETERMINISTIC
BEGIN
    DECLARE expr TEXT;
    DECLARE pos INTEGER;

    IF articles = '' THEN
     SET pos = 0;
    ELSE
     SET expr = TRIM(REPLACE(articles, ',', '|'));
     SET expr = CONCAT('^(', expr, ')[[:space:]]');

     IF title REGEXP expr THEN
      SET pos = LOCATE(' ', title);
     ELSE
      SET pos = 0;
     END IF;
    END IF;

    RETURN SUBSTRING(title, pos+1);
END