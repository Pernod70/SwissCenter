-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Add the iptc/xmp columns to the photos table
-- -------------------------------------------------------------------------------------------------

ALTER TABLE photos ADD (
  iptc_caption            text,
  iptc_suppcategory       text,
  iptc_keywords           text,
  iptc_city               text,
  iptc_province_state     text,
  iptc_country            text,
  iptc_byline             text,
  iptc_date_created       text,
  iptc_location           text,
  xmp_rating              int unsigned
);

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************






