--
-- SQL for PositivePageRate extension
--

-- Table for storing page view and rate statistics
CREATE TABLE /*_*/ppr_page_stats (
    -- Page ID
    ps_page INT(10) UNSIGNED NOT NULL,
    -- User ID
    ps_user INT(10) UNSIGNED NOT NULL,
    -- Did user like this article or not
    ps_rate TINYINT(1) NOT NULL,
    -- View or rate timestamp
    ps_timestamp BINARY(14) NOT NULL,
    -- Primary key
    PRIMARY KEY (ps_page, ps_user, ps_timestamp),
    -- Foreign keys
    FOREIGN KEY (ps_user) REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ps_page) REFERENCES page (page_id) ON DELETE CASCADE ON UPDATE CASCADE
) /*$wgDBTableOptions*/;

-- Table for storing aggregate statistics
CREATE TABLE /*_*/ppr_page_aggr (
    -- Page ID
    pa_page INT(10) UNSIGNED NOT NULL,
    -- Positive votes
    pa_plus INT(10) UNSIGNED NOT NULL,
    -- Negative votes
    pa_minus INT(10) UNSIGNED NOT NULL,
    -- Total unique views
    pa_total INT(10) UNSIGNED NOT NULL,
    -- Primary key
    PRIMARY KEY (pa_page),
    -- Foreign key
    FOREIGN KEY (pa_page) REFERENCES page (page_id) ON DELETE CASCADE ON UPDATE CASCADE
) /*$wgDBTableOptions*/;
