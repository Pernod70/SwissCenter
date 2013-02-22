-- -------------------------------------------------------------------------------------------------
-- Create stored function 'trim_article' which will remove leading words from 'title' specified
-- in the comma-separated 'articles' list. 
-- -------------------------------------------------------------------------------------------------

CREATE FUNCTION trim_article(title TEXT, articles TEXT) RETURNS text CHARSET utf8
    DETERMINISTIC
BEGIN
     DECLARE expr TEXT;
     DECLARE ret TEXT;
     DECLARE pos INTEGER;
     DECLARE len INTEGER;

     SET ret = title;

     IF TRIM(articles) != '' THEN
      SET expr = TRIM(REPLACE(articles, ',', '|'));
      SET expr = CONCAT('^(', expr, ')[[:space:]]');
      SET len = CHAR_LENGTH(title);

      IF title REGEXP expr THEN
       SET pos = LOCATE(' ', title);
       IF title REGEXP '\([0-9]{4}\)' THEN
        SET ret = CONCAT(TRIM(SUBSTRING(title, pos+1, len-pos-6)), ', ', SUBSTRING(title, 1, pos), SUBSTRING(title, len-5));
       ELSE
        SET ret = CONCAT(SUBSTRING(title, pos+1), ', ', SUBSTRING(title, 1, pos-1));
       END IF;
      END IF;
     END IF;

     RETURN ret;
 END
