#!/usr/bin/php
<?php

# MediaWiki4Intranet Sphinx search configuration generator
# (c) 2010+ Vitaliy Filippov, Stas Fomin

for ($i = 1; $i < count($argv); $i++)
{
    if ($argv[$i] == '--conf')
        $conf = $argv[++$i];
    elseif ($argv[$i] == '--hostname' || $argv[$i] == '--style')
        $i++;
}
if (!$conf)
    $conf = dirname(__FILE__).'/sphinx.wikis.php';

$wikis = array();
if (file_exists($conf))
    require_once($conf);

$hostname = trim(file_get_contents('/etc/hostname'));
$style = 'rt';

for ($i = 1; $i < count($argv); $i++)
{
    if ($argv[$i] == '--hostname')
        $hostname = $argv[++$i];
    elseif ($argv[$i] == '--style')
        $style = $argv[++$i];
    elseif ($argv[$i] == '--help' || $argv[$i] == '-h')
        $help = true;
    elseif ($argv[$i] == '--conf')
        $i++;
}

$err = !$wikis || !$wikis[$hostname] || $style != 'rt' && $style != 'old' ||
    file_exists('sphinx.conf') || !is_writable('.');
if ($help || $err)
{
    print "MediaWiki4Intranet Sphinx Search configurator\n";
    if ($err)
        print "\n";
    if (!$wikis)
        print "ERROR: Empty config file '$conf'\n";
    if (!$wikis[$hostname])
        print "ERROR: Host name '$hostname' is unknown\n";
    if ($style != 'rt' && $style != 'old')
        print "ERROR: Sphinx config style '$style' is unknown\n";
    if (file_exists('sphinx.conf'))
        print "ERROR: sphinx.conf already exists, delete or backup it before reconfiguring\n";
    if (!is_writable('.'))
        print "ERROR: No permissions to create sphinx.conf in current directory\n";
    print "\nUSAGE: php configure.sphinx.php [--help] [--hostname HOSTNAME] [--conf sphinx.wikis.php] [--style rt|local]\n";
    if (!$help)
        print "See full help with php configure.sphinx.php --help\n";
    else
        print "\nOPTIONS:
--conf FILE
  Take per-host Wiki list from this file (./sphinx.wikis.php by default).
  Needed to generate Sphinx config. See sample below.
--hostname HOSTNAME
  Print configuration for host HOSTNAME".($wikis ? "
  Host names: ".implode(', ', array_keys($wikis)) : '')."
  Default host name = $hostname (taken from /etc/hostname)
--style STYLE
  Select one of 'rt' (default) or 'old'.
  'rt' stands for one real-time index, gives you instant search update and requires
    Sphinx >= 0.9.9, MediaWiki >= 1.16 and SphinxSearchEngine extension
    (http://wiki.4intra.net/SphinxSearchEngine)
  'old' uses delta index updates (http://sphinxsearch.com/docs/2.0.1/delta-updates.html),
    i.e. one \"big\" index, rebuilt every day and one \"incremental\" for pages changed in
    the last day, rebuilt each 30 minutes or so. It works for older Sphinx and MediaWiki
    versions and requires SphinxSearch extension (http://www.mediawiki.org/Extension:SphinxSearch).

You can take this sample sphinx.wikis.php as the base for your config:

<?php
$wikis = array(
    // hostname is taken from /etc/hostname on UNIX systems
    '<hostname>' => array(
        array('name' => '<unique index name>', 'user' => '<mysql DB user>', 'pass' => '<mysql DB password>', 'db' => '<mysql DB name>'),
        // more wikis...
    ),
    // more hosts...
);
";
    exit;
}

$reindex_main = '';
$reindex_inc = '';
$init_indexes = '';
$config = '';
foreach ($wikis[$hostname] as $w)
{
    if ($style == 'old')
    {
        $config .= old_conf($w);
        $reindex_main .= ' main_'.$w['name'];
        $reindex_inc .= ' inc_'.$w['name'];
        if (!file_exists("/var/lib/sphinxsearch/data/main_$w[name].spi"))
            $init_indexes .= "/usr/bin/indexer main_$w[name]\n";
        if (!file_exists("/var/lib/sphinxsearch/data/inc_$w[name].spi"))
            $init_indexes .= "/usr/bin/indexer inc_$w[name]\n";
    }
    else
        $config .= rt_conf($w);
}
$config .= '### General configuration ###

indexer
{
    mem_limit = 128M
}

searchd
{
    listen       = 127.0.0.1:3112
    log          = /var/log/sphinxsearch/sphinx.log
    query_log    = /var/log/sphinxsearch/query.log
    read_timeout = 5
    max_children = 30
    pid_file     = /var/run/searchd.pid
    max_matches  = 1000'.($style == 'rt' ? (substr(php_uname(), 0, 7) == 'Windows' ? '
    listen       = 127.0.0.1:9306:mysql41' : '
    listen       = /var/run/searchd.sock:mysql41').'
    workers      = threads
    compat_sphinxql_magics = 0' : '').'
}
';
file_put_contents('sphinx.conf', $config);

print "sphinx.conf created for host '$hostname', move it to /etc/sphinxsearch/sphinx.conf\n";

if ($style == 'old')
{
    print "
Then add the following to /etc/crontab:

# Sphinx search: rebuild full indexes at night
0 3 * * *       root    /usr/bin/indexer --quiet --rotate$reindex_main
# Sphinx search: update smaller indexes regularly
*/30 * * * *    root    /usr/bin/indexer --quiet --rotate$reindex_inc

";
}

if ($init_indexes)
{
    print "Then stop your Sphinx searchd, initialize indexes and start it again
(Debian commands:
/etc/init.d/sphinxsearch stop
$init_indexes/etc/init.d/sphinxsearch start
)
";
}
else
    print "Then reload your Sphinx searchd (Debian: '/etc/init.d/sphinxsearch reload')\n";
if ($style == 'rt')
    print "Don't forget to populate new indexes with 'php maintenance.php'\n";
exit;

function rt_conf($wiki)
{
    return "### $wiki[name] ###

index $wiki[name]
{
    type            = rt
    path            = /var/lib/sphinxsearch/data/$wiki[name]
    rt_field        = text
    rt_field        = title
    rt_attr_uint    = namespace
    rt_field        = category
    enable_star     = 1
    charset_type    = utf-8
    charset_table   = 0..9, A..Z->a..z, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F
    blend_chars     = _, -, &, +, @, $
    morphology      = stem_ru
    min_word_len    = 2
}

";
}

function old_conf($wiki)
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
    path          = /var/lib/sphinxsearch/data/main_$wiki[name]
    docinfo       = extern
    morphology    = stem_ru
    #stopwords    = /var/lib/sphinxsearch/data/stopwords.txt
    min_word_len  = 2
    #min_infix_len = 1
    enable_star   = 1
    charset_type  = utf-8
    charset_table = 0..9, A..Z->a..z, _, -, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F
}

index inc_$wiki[name] : main_$wiki[name]
{
    path   = /var/lib/sphinxsearch/data/inc_$wiki[name]
    source = src_incremental_$wiki[name]
}

";
}
