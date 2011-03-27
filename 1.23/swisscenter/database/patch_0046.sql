-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************

-- -------------------------------------------------------------------------------------------------
-- Update internet radio genres
-- -------------------------------------------------------------------------------------------------

DELETE FROM iradio_genres;

INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','adult alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','britpop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','classic alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','college');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','dancepunk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','dream pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','emo');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','goth');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','grunge');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','hardcore');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','indie pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','indie rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','industrial');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','lo-fi');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','modern rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','new wave');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','noise pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','post-punk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','power pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','punk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','ska');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('alternative','xtreme');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','acoustic blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','cajun/zydeco');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','chicago blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','contemporary blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','country blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','delta blues');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('blues','electric blues');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','classical');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','baroque');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','chamber');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','choral');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','classical period');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','early classical');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','impressionist');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','modern');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','opera');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','piano');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','romantic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('classical','symphony');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','alt-country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','americana');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','bluegrass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','classic country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','contemporary bluegrass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','contemporary country');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','honky tonk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','hot country hits');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('country','western');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','decades');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','30s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','40s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','50s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','60s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','70s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','80s');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('decades','90s');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','easy listening');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','exotica');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','light rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','lounge');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','orchestral pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','polka');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('easy listening','space age pop');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','electronic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','acid house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','ambient');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','big beat');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','breakbeat');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','dance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','demo');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','disco');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','down tempo');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','drum and bass');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','electro');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','garage');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','hard house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','house');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','idm');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','jungle');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','progressive');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','techno');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','trance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','tribal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('electronic','trip hop');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','alternative folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','contemporary folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','folk rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','new acoustic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','traditional folk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('folk','world folk');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','inspirational');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','christian rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','classic christian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','contemporary gospel');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','gospel');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','praise/worship');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','sermons/services');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','southern gospel');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('inspirational','traditional gospel');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','international');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','african');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','arabic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','asian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','bollywood');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','brazilian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','caribbean');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','celtic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','chinese');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','european');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','filipino');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','french');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','greek');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','hawaiian/pacific');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','hindi');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','indian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','japanese');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','jewish');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','klezmer');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','korean');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','mediterranean');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','middle eastern');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','north american');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','russian');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','soca');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','south american');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','tamil');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','worldbeat');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('international','zouk');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','acid jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','avant garde');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','big band');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','bop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','classic jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','cool jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','fusion');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','hard bop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','latin jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','smooth jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','swing');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','vocal jazz');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('jazz','world fusion');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','bachata');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','banda');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','bossa nova');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','cumbia');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin dance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin rap/hip-hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','latin rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','mariachi');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','merengue');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','ranchera');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','reggaeton');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','regional mexican');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','salsa');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','tango');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','tejano');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('latin','tropicalia');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','black metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','classic metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','extreme metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','grindcore');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','hair metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','heavy metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','metalcore');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','power metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','progressive metal');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('metal','rap metal');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('misc','misc');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','new age');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','environmental');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','ethnic fusion');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','healing');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','meditation');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('new age','spiritual');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','adult contemporary');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','barbershop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','bubblegum pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','dance pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','idols');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','jpop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','oldies');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','soft rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','teen pop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','top 40');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('pop','world pop');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','public radio');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','college');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','news');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','sports');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('public radio','talk');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','r&b/urban');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','classic r&b');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','doo wop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','funk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','motown');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','neo-soul');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','quiet storm');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','soul');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('r&b/urban','urban contemporary');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','alternative rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','dirty south');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','east coast rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','freestyle');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','gangsta rap');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','hip hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','mixtapes');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','old school');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','turntablism');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','underground hip-hop');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rap','west coast rap');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','contemporary reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','dancehall');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','dub');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','pop-reggae');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','ragga');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','reggae roots');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('reggae','rock steady');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','adult album alternative');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','british invasion');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','classic rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','garage rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','glam');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','hard rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','jam bands');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','piano rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','prog rock');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','psychedelic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rock & roll');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','rockability');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','singer/songwriter');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('rock','surf');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','seasonal/holiday');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','anniversary');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','birthday');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','christmas');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','halloween');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','hanukkah');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','honeymoon');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','kwanzaa');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','valentine');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','wedding');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('seasonal/holiday','winter');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','soundtracks');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','anime');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','kids');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','original score');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','showtunes');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('soundtracks','video game music');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','talk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','blogtalk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','comedy');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','community');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','educational');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','government');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','news');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','old time radio');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','other talk');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','political');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','scanner');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','spoken word');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','sports');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('talk','technology');

INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','themes');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','adult');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','best of');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','chill');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','eclectic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','experimental');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','female');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','heartache');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','instrumental');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','lgbt');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','love/romance');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','party mix');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','patriotic');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','rainy day mix');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','reality');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','sexy');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','shuffle');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','travel mix');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','tribute');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','trippy');
INSERT INTO iradio_genres (genre,subgenre) VALUES ('themes','work mix');

-- *************************************************************************************************
--   SWISScenter Source                                                              Nigel Barnes
-- *************************************************************************************************