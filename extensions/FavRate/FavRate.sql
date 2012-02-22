--
-- SQL for FavRate extension
--

-- Table for storing page view and rate statistics
CREATE TABLE /*$wgDBprefix*/fr_page_stats (
    -- Page ID
    ps_page INT(10) UNSIGNED NOT NULL,
    -- User ID
    ps_user INT(10) UNSIGNED NOT NULL,
    -- Statistics entry type (0 = unique visitor, 1 = favorites)
    ps_type TINYINT(1) NOT NULL,
    -- Entry timestamp
    ps_timestamp BINARY(14) NOT NULL,
    -- Primary key
    PRIMARY KEY (ps_page, ps_user, ps_type)
) /*$wgDBTableOptions*/;

-- Create foreign keys (InnoDB only)
ALTER TABLE /*$wgDBprefix*/fr_page_stats ADD FOREIGN KEY (ps_user) REFERENCES /*$wgDBprefix*/user (user_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE /*$wgDBprefix*/fr_page_stats ADD FOREIGN KEY (ps_page) REFERENCES /*$wgDBprefix*/page (page_id) ON DELETE CASCADE ON UPDATE CASCADE;
