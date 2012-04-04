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
    -- Comment
    ps_comment VARCHAR(255) DEFAULT NULL,
    -- Primary key
    PRIMARY KEY (ps_page, ps_user, ps_type)
) /*$wgDBTableOptions*/;
