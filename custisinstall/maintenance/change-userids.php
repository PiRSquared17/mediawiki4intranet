<?php

// A simple script to change user IDs in the Wiki database
// to be equal with another Wiki's database user IDs.
// Then you should be capable to use $wgSharedTables=array('user')
// (c) Vitaliy Filippov, 2011

// Configuration

$source_database = array('localhost', 'dpwiki', 'dpwiki', 'dpwiki13');
$reference_database = array('localhost', 'wiki', 'wiki', 'wiki13');
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

$db = mysql_connect($source_database[0], $source_database[1], $source_database[2]);
$refdb = mysql_connect($reference_database[0], $reference_database[1], $reference_database[2]);
if (!mysql_select_db($source_database[3], $db) || !mysql_select_db($reference_database[3], $refdb))
    die("Can't connect");
mysql_query('SET NAMES utf8', $db);
mysql_query('SET NAMES utf8', $refdb);

// Determine NAME=>ID mapping for reference database
$res = mysql_query('SELECT user_id, user_name FROM user', $refdb);
$refuserids = array();
while ($row = mysql_fetch_row($res))
    $refuserids[$row[1]] = $row[0];

// Determine SRCID=>REFID mapping for users
$res = mysql_query('SELECT user_id, user_name FROM user', $db);
$useridbyname = array();
$useridbyid = array();
while ($row = mysql_fetch_row($res))
{
    if ($refuserids[$row[1]])
    {
        $useridbyname[$row[1]] = $refuserids[$row[1]];
        $useridbyid[$row[0]] = $refuserids[$row[1]];
    }
    else
    {
        $useridbyname[$row[1]] = $row[0];
        $useridbyid[$row[0]] = $row[0];
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
    print "GRANT ALL PRIVILEGES ON `$reference_database[3]`.`$t` TO '$source_database[1]'@'localhost';\n";
print "FLUSH PRIVILEGES;\n";
