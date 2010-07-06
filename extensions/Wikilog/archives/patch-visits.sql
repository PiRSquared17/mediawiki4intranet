--
-- Last visit dates for each post.
--
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/wikilog_visits (
  wlv_user INTEGER UNSIGNED NOT NULL,
  wlv_post INTEGER UNSIGNED NOT NULL,
  wlv_date BINARY(14) NOT NULL,
  PRIMARY KEY (wlv_user, wlv_post)
) /*$wgDBTableOptions*/;

