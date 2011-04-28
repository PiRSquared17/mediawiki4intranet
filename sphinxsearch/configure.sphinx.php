#!/usr/bin/php
<?php

# MediaWiki4Intranet Sphinx search configuration generator
# (c) 2010 Vitaliy Filippov, Stas Fomin

if (!file_exists(dirname(__FILE__).'/sphinx.wikis.php'))
{
    print <<<'EOF'
MediaWiki4Intranet Sphinx configurator

sphinx.wikis.php with Wiki list is needed for generating Sphinx config
Sample sphinx.wikis.php:

<?php
$wikis = array(
    // hostname is taken from /etc/hostname on UNIX systems
    '<hostname>' => array(
        array('name' => '<unique index name>', 'user' => '<mysql DB user>', 'pass' => '<mysql DB password>', 'db' => '<mysql DB name>'),
        // more wikis...
    ),
    // more hosts...
);
?>

Copy this text to sphinx.wikis.php, edit it to match your setup, and re-run makesphinx.php

EOF;
    exit;
}

require_once(dirname(__FILE__).'/sphinx.wikis.php');

$reindex_main = '';
$reindex_inc = '';
$hostname = trim(file_get_contents('/etc/hostname'));

for ($i = 1; $i < count($argv); $i++)
{
    if ($argv[$i] == '--hostname')
        $hostname = $argv[++$i];
    elseif ($argv[$i] == '--help')
    {
        print 'Sphinx config for wiki search generator
USAGE: php sphinxconf.php [--hostname HOSTNAME]
Host names: '.implode(', ', array_keys($wikis)).'
Default host name is taken from /etc/hostname
';
        exit;
    }
}

if (!$wikis[$hostname])
{
    print "Host name '$hostname' is unknown\n";
    exit;
}

if (file_exists('sphinx.conf'))
{
    print "sphinx.conf already exists, delete or backup it before reconfiguring\n";
    exit;
}

if (!is_writable('.'))
{
    print "No permissions to create sphinx.conf in current directory\n";
    exit;
}

$init_indexes = '';
$config = '';
foreach ($wikis[$hostname] as $w)
{
    $config .= wiki_conf($w);
    $reindex_main .= ' main_'.$w['name'];
    $reindex_inc .= ' inc_'.$w['name'];
    if (!file_exists('/var/data/sphinx/main_'.$w['name'].'.spi'))
        $init_indexes .= '/usr/bin/indexer main_'.$w['name']."\n";
    if (!file_exists('/var/data/sphinx/inc_'.$w['name'].'.spi'))
        $init_indexes .= '/usr/bin/indexer inc_'.$w['name']."\n";
}
$config .= '### General configuration ###

indexer
{
    mem_limit = 128M
}

searchd
{
    listen       = 127.0.0.1:3112
    log          = /var/log/sphinx/sphinx.log
    query_log    = /var/log/sphinx/query.log
    read_timeout = 5
    max_children = 30
    pid_file     = /var/run/searchd.pid
    max_matches  = 1000
}
';
file_put_contents('sphinx.conf', $config);

print "sphinx.conf created for host '$hostname', move it to /etc/sphinxsearch/sphinx.conf

Then add the following to /etc/crontab:

# Sphinx search: rebuild full indexes at night
0 3 * * *       root    /usr/bin/indexer --quiet --rotate$reindex_main
# Sphinx search: update smaller indexes regularly
*/30 * * * *    root    /usr/bin/indexer --quiet --rotate$reindex_inc

".($init_indexes ? "Then stop your Sphinx searchd, initialize indexes and start it again
(Debian commands:
/etc/init.d/sphinxsearch stop
$init_indexes/etc/init.d/sphinxsearch start
)
" : "Then reload your Sphinx searchd (Debian: '/etc/init.d/sphinxsearch reload')
");
exit;

function wiki_conf($wiki)
{
    return "### $wiki[name] ###

source src_main_$wiki[name]
{
    type           = mysql
    sql_host       = localhost
    sql_user       = $wiki[user]
    sql_pass       = $wiki[pass]
    sql_db         = $wiki[db]
    sql_query_pre  = SET NAMES utf8
    sql_query      = SELECT page_id, REPLACE(page_title,'_',' ') page_title, page_namespace, old_id, old_text FROM page, revision, text WHERE rev_id=page_latest AND old_id=rev_text_id AND page_title NOT LIKE '%NOINDEX%'
    sql_attr_uint  = page_namespace
    sql_attr_uint  = old_id
    sql_attr_multi = uint category from query; SELECT cl_from, page_id AS category FROM categorylinks, page WHERE page_title=cl_to AND page_namespace=14
    sql_query_info = SELECT REPLACE(page_title,'_',' ') page_title, page_namespace FROM page WHERE page_id=$id
}

source src_incremental_$wiki[name] : src_main_$wiki[name]
{
    sql_query = SELECT page_id, REPLACE(page_title,'_',' ') page_title, page_namespace, old_id, old_text FROM page, revision, text WHERE rev_id=page_latest AND old_id=rev_text_id AND page_touched>=DATE_FORMAT(CURDATE(), '%Y%m%d050000') AND page_title NOT LIKE '%NOINDEX%'
}

index main_$wiki[name]
{
    source        = src_main_$wiki[name]
    path          = /var/data/sphinx/main_$wiki[name]
    docinfo       = extern
    morphology    = stem_ru
    #stopwords    = /var/data/sphinx/stopwords.txt
    min_word_len  = 2
    min_infix_len = 1
    enable_star   = 1
    charset_type  = utf-8
    charset_table = 0..9, A..Z->a..z, _, -, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F
}

index inc_$wiki[name] : main_$wiki[name]
{
    path   = /var/data/sphinx/inc_$wiki[name]
    source = src_incremental_$wiki[name]
}

";
}
