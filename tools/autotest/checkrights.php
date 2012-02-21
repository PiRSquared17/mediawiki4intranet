#!/usr/bin/php
<?php
// Simple autotest for MediaWiki right system - will croak and disable
// Apache 2 virtual host if the given user can access the given page.
// (c) Vitaliy Filippov, 2011.

$USAGE = "Invalid configuration file specified.

This is simple 'emergency' automatic test for IntraACL MediaWiki rights system.
Will check that given pages are inaccessible for given user, croak and disable
Apache 2 virtual host if they will be accessible.

USAGE: php $argv[0] <config file>

Configuration syntax:
db=<mysql database>
dbhost=<mysql host>
dbuser=<mysql user>
dbpassword=<mysql password>
url=<wiki base url with / at the end>
config=<virtual host file name in /etc/apache2/sites-enabled>

UserName|PageWhichShouldNotBeAccessible
UserName2|PageWhichShouldNotBeAccessible2
...
";

$access_denied_page = "Служебная:Badtitle|Доступ_запрещён";

check(read_config($argv[1]));
exit;

function read_config($file)
{
    global $USAGE;
    $check = array(
        'db' => '',
        'dbhost' => '',
        'dbuser' => '',
        'dbpassword' => '',
        'url' => '',
        'config' => '',
    );
    $pages = array();
    if (!$file || !file_exists($file))
        die($USAGE);
    foreach (explode("\n", file_get_contents($file)) as $str)
    {
        list($k, $v) = explode('=', $str);
        $k = trim($k);
        $v = trim($v);
        if (isset($check[$k]))
            $check[$k] = $v;
        else
        {
            $page = explode('|', trim($str));
            if (count($page) > 1)
                $pages[] = $page;
        }
    }
    $check['pages'] = $pages;
    return $check;
}

function encode_title($title)
{
    $title = str_replace(' ', '_', $title);
    $title = urlencode($title);
    $title = str_replace(array("%2F", "%2B"), array('/', '+'), $title);
    return $title;
}

function check($check)
{
    global $USAGE, $access_denied_page;
    if (!$check['db'])
        die($USAGE);
    $dbh = mysql_connect($check['dbhost'], $check['dbuser'], $check['dbpassword']);
    if (!$dbh)
        die("Cannot connect as $check[dbuser]\n");
    if (!mysql_select_db($check['db'], $dbh))
        die("No such DB $check[db]\n");
    $p = $check['db'];
    foreach ($check['pages'] as $page)
    {
        list($username, $title) = $page;
        $res = mysql_query("SELECT user_id, user_token FROM user WHERE user_name='".mysql_real_escape_string($username)."'");
        $row = mysql_fetch_row($res);
        if (!$row)
            die("No such user $username\n");
        list($userid, $usertoken) = $row;
        list($status, $content) = GET($check['url'].encode_title($title), array(
            CURLOPT_COOKIE =>
                $p.'Token='.urlencode($usertoken).'; '.
                $p.'UserID='.$userid.'; '.
                $p.'UserName='.urlencode($username)
        ));
        if (!preg_match('/wgUserName\W+'.preg_quote($username).'.*<\/head/s', $content))
            die("Couldn't authenticate under $username\n");
        if (!preg_match('/wgPageName\W+'.$access_denied_page.'/s', $content))
        {
            fprintf(STDERR, "POLUNDRA!!! User $username can access page $title!\n");
            if (file_exists("/etc/apache2/sites-enabled/$check[config]"))
            {
                fprintf(STDERR, "Emergency disabling virtual host $check[config]!\n");
                rename("/etc/apache2/sites-enabled/$check[config]", "/etc/apache2/DISABLED-$check[config]");
                system("apache2ctl restart");
            }
            exit(1);
        }
    }
}

function GET($url, $opts = array())
{
    $curl = curl_init();
    curl_setopt_array($curl, $opts+array(
        CURLOPT_URL => $url,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $content = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return array($status, $content);
}
