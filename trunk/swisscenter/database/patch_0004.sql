-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Add the iptc/xmp columns to the photos table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE photos ADD (iptc_caption 		TEXT default null,
 		    	iptc_suppcategory 	TEXT default null,
                    	iptc_keywords 		TEXT default null,
                    	iptc_city 		TEXT default null,
                    	iptc_province_state 	TEXT default null,
                    	iptc_country 		TEXT default null,
                   	iptc_byline 		TEXT default null,
			iptc_date_created	TEXT default null,
			iptc_location		TEXT default null,
                   	xmp_rating 		INT UNSIGNED default null);

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************






