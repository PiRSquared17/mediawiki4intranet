<?php

// A simple script: writes SQL code to change user IDs in one Wiki database
// so that they will be equal with another Wiki's database user IDs.
// Then you should be capable to use $wgSharedTables[] = 'user'.
// (c) Vitaliy Filippov, 2011

// Database configuration

if (count($argv) < 8)
    fwrite(STDERR, "User ID change script
USAGE: php $argv[0] <mysql_server> <db1> <user1> <pass1> <db2> <user2> <pass2>
mysql_server is 'address[:port]' or '/path/to/unix/socket' for MySQL
db1, user1, pass1 specify target database (database in which user IDs must be changed)
db2, user2, pass2 specify reference database (database WITH which user table will be shared)
");

$mysql_server = $argv[1];
$source_db = $argv[2];
$source_dbuser = $argv[3];
$source_dbpass = $argv[4];
$reference_db = $argv[5];
$reference_dbuser = $argv[6];
$reference_dbpass = $argv[7];

// Mapping onfiguration

$shared_tables = array('user', 'user_groups', 'mwq_choice', 'mwq_choice_stats', 'mwq_question', 'mwq_question_test', 'mwq_test', 'mwq_ticket');

$ID_LINKS = explode(' ',
    'user.user_id external_user.eu_local_id archive.ar_user drafts.draft_user filearchive.fa_deleted_user '.
    'filearchive.fa_user image.img_user ipblocks.ipb_user logging.log_user mwq_ticket.tk_user_id oldimage.oi_user '.
    'page_last_visit.pv_user page_restrictions.pr_user protected_titles.pt_user recentchanges.rc_user revision.rev_user '.
    'user_groups.ug_user user_newtalk.user_id user_properties.up_user watchlist.wl_user wikilog_authors.wla_author '.
    'wikilog_comments.wlc_user wikilog_subscriptions.ws_user halo_acl_quickacl.user_id'
);

$links = array();
foreach ($ID_LINKS as $s)
{
    list($table, $field) = explode('.', $s, 2);
    $links[$table][$field] = 'id';
}

$links['wikilog_wikilogs']['wlw_authors'] = 'wikilog';
$links['wikilog_posts']['wlp_authors'] = 'wikilog';
$links['halo_acl_groups']['mg_users'] = 'splitid';
$links['halo_acl_rights']['users'] = 'splitid';
$links['halo_acl_security_descriptors']['mr_users'] = 'splitid';
$links['halo_acl_group_members']['child_id'] = 'child_type==user';

// End configuration

$db = mysql_connect($mysql_server, $source_dbuser, $source_dbpass);
$refdb = mysql_connect($mysql_server, $reference_dbuser, $reference_dbpass);
if (!mysql_select_db($source_db, $db) || !mysql_select_db($reference_db, $refdb))
    die("Can't connect to one of databases\n");
mysql_query('SET NAMES utf8', $db);
mysql_query('SET NAMES utf8', $refdb);

// Determine NAME=>ID / EMAIL=>ID mapping for reference database
$res = mysql_query('SELECT user_id, user_name, user_email FROM user', $refdb);
$refuseridbyname = $refuseridbyemail = array();
$dupemail = array();
while ($row = mysql_fetch_row($res))
{
    $refuseridbyname[$row[1]] = $row[0];
    if ($refuseridbyemail[$row[2]])
        $dupemail[$row[2]]++;
    else
        $refuseridbyemail[$row[2]] = $row[0];
}
foreach ($dupemail as $email => $n)
    unset($refuseridbyemail[$email]);

// Determine SRCID=>REFID mapping for users
$res = mysql_query('SELECT * FROM user', $db);
$useridbyid = array();
while ($row = mysql_fetch_assoc($res))
{
    if ($refuseridbyemail[$row['user_email']])
        $useridbyid[$row['user_id']] = $refuseridbyemail[$row['user_email']];
    elseif ($refuseridbyname[$row['user_name']])
        $useridbyid[$row['user_id']] = $refuseridbyname[$row['user_name']];
    else
    {
        // User does not exist in reference database, add it there
        fwrite(STDERR, "Adding user $row[user_name] to $reference_db\n");
        $new = $row;
        unset($new['user_id']);
        mysql_query(
            "INSERT INTO `user` (`".implode("`,`", array_keys($new))."`) VALUES ('".
            implode("','", array_map('mysql_real_escape_string', array_values($new)))."')",
            $refdb
        );
        $useridbyid[$row['user_id']] = mysql_insert_id($refdb);
    }
}

// Rewrite user ids and print SQL
print "SET NAMES utf8;\n\n";
foreach ($links as $t => $fields)
{
    $id_field = false;
    $res = mysql_query("DESC `$t`", $db);
    while ($row = mysql_fetch_assoc($res))
        if (strpos(strtolower($row['Extra']), 'auto_increment') !== false)
            $id_field = $row['Field'];
    if ($t == 'user')
        $id_field = false;
    $res = mysql_query("SELECT * FROM `$t`", $db);
    $k = false;
    $updated = 0;
    while ($row = mysql_fetch_assoc($res))
    {
        foreach ($fields as $field => $mapping)
        {
            $new = $row[$field];
            if ($mapping == 'id')
            {
                if ($useridbyid[$row[$field]])
                    $new = $useridbyid[$row[$field]];
            }
            elseif ($mapping == 'wikilog')
            {
                $row[$field] = unserialize($row[$field]);
                foreach ($row[$field] as $username => &$userid)
                    $userid = $useridbyid[$userid];
                unset($userid);
                $new = serialize($row[$field]);
            }
            elseif ($mapping == 'splitid')
            {
                $row[$field] = explode(',', $row[$field]);
                foreach ($row[$field] as &$userid)
                    if ($useridbyid[$userid])
                        $userid = $useridbyid[$userid];
                unset($userid);
                $new = implode(',', $row[$field]);
            }
            elseif ($mapping == 'child_type==user')
            {
                if ($row['child_type'] == 'user' && $useridbyid[$row[$field]])
                    $new = $useridbyid[$row[$field]];
            }
            if ($new !== $row[$field])
            {
                $row[$field] = $new;
                $updated++;
            }
        }
        if (!$k)
        {
            if (!$id_field)
                print "TRUNCATE `$t`;\n";
            print "INSERT INTO `$t` (`".implode("`,`", array_keys($row))."`) VALUES\n";
        }
        if ($k)
            print ",\n";
        print "('".implode("','", array_map('mysql_real_escape_string', array_values($row)))."')";
        $k = true;
    }
    if ($k)
    {
        if ($id_field)
        {
            print "\nON DUPLICATE KEY UPDATE ";
            $a = array();
            foreach ($fields as $field => $mapping)
                $a[] = "`$field`=VALUES(`$field`)";
            print implode(', ', $a);
        }
        print ";\n-- (will update $updated rows)\n\n";
    }
}

foreach ($shared_tables as $t)
    print "GRANT ALL PRIVILEGES ON `$reference_db`.`$t` TO '$source_dbuser'@'localhost';\n";
print "FLUSH PRIVILEGES;\n";
