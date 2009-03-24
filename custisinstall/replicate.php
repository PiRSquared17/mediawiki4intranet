#!/usr/bin/php
<?php
# Автоматический скрипт репликации вики-статей
# Реплицирует как сами статьи, так и изображения, используемые в них

$PHP_SELF = array_shift($argv);
chdir(dirname($PHP_SELF));

$config = read_config(array_shift($argv));
if (!$config)
    die(<<<EOF
MediaWiki replication script by Vitaliy Filippov <vfilippov@custis.ru>

USAGE: $PHP_SELF <replication-config.ini> [targets...]
When called without target list, $PHP_SELF will attempt to replicate all targets
found in config file. There must be 2 sections in config file according to
each target and named "<Target>SourceWiki" and "<Target>DestinationWiki".

Config file fragment syntax (Replace __Test__ with desired [target] name):

[__Test__SourceWiki]
URL=<source wiki url>
Category=<source category name>
FullHistory=<'yes' or 'no' (default), 'yes' replicates all page revisions, not only the last one>
ForceImageDownload=<'yes' or 'no' (default), 'yes' means force image fetching>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>

[__Test__DestinationWiki]
URL=<destination wiki url>
Path=<destination wiki installation path>
BasicLogin=<HTTP basic auth username, if needed>
BasicPassword=<HTTP basic auth password, if needed>
User=<name of a user having import rights in destination wiki>
Password=<his password>

EOF
);

if (!function_exists('curl_init'))
    die("This script needs cURL extension to work - please enable it in your php.ini\n");

require_once "../maintenance/commandLine.inc";

$temps = array();
$targets = array_map("strtolower", $argv);
if (!count($targets))
    $targets = array_keys($config);
foreach ($targets as $target)
{
    # replicate targets
    echo "[$target] Begin replication\n";
    if ($err = replicate($config[$target][src], $config[$target][dest], $target))
        fwrite(STDERR, "[$target] Could not replicate:\n[$target] $err\n");
}
foreach ($temps as $temp)
    unlink($temp);
exit(0);

function xmlfh_repl_cb($m)
{
    global $enqa, $q;
    return $m[1].enqueue($m[2], $enqa[0], $enqa[1], $enqa[2], $q, $enqa[3], $enqa[4]).$m[3];
}

function xmlfh_trans_write($curl, $content)
{
    global $xmlfh;
    fwrite($xmlfh, preg_replace_callback('#(<src[^<>]*>)(.*?)(<\/src\s*>)#is', 'xmlfh_repl_cb', $content));
    return strlen($content);
}

function replicate($src, $dest, $targetname)
{
    global $temps, $xmlfh, $enqa, $cookiefile, $q;
    $curl = curl_init("$src[url]/index.php?title=Special:Export&action=submit");
    # Читаем список страниц категории
    curl_setopt_array($curl, array(
        CURLOPT_POST            => 1,
        CURLOPT_HEADER          => 0,
        CURLOPT_POSTFIELDS      => "addcat=Добавить&catname=$src[category]",
        CURLOPT_RETURNTRANSFER  => 1,
    ));
    if ($src[basiclogin])
        curl_setopt($curl, CURLOPT_USERPWD, $src[basiclogin].':'.$src[basicpassword]);
    $text = curl_exec($curl);
    if (!$text)
        return "Could not post '$src[url]/index.php?title=Special:Export&action=submit&catname=$src[category]': ".curl_error($curl);
    curl_close($curl);
    if (preg_match('#<textarea[^<>]*>(.*?)</textarea>#is', $text, $m))
        $text = $m[1];
    if (!$text)
        return "No pages in category $src[category]";
    $text = html_entity_decode($text);
    $ts = gettimeofday();
    $ts = $ts[sec] + $ts[usec]/1000000;
    $q = array(async => curl_multi_init());
    # Читаем экспортную XML-ку
    array_push($temps, $fn = tempnam('/tmp', 'wikiexport'));
    $fh = fopen($fn, "wb");
    if (!$fh)
        return "Could not write into temp file $fn";
    $curl = curl_init("$src[url]/index.php?title=Special:Export&action=submit");
    $xmlfh = $fh;
    $enqa = array($src[url], $dest[url], $dest[path], $src[basiclogin] ? $src[basiclogin].':'.$src[basicpassword] : '', $src[forceimagedownload]);
    curl_setopt_array($curl, array(
        CURLOPT_POST          => 1,
        CURLOPT_HEADER        => 0,
        CURLOPT_WRITEFUNCTION => 'xmlfh_trans_write',
        CURLOPT_POSTFIELDS    => "templates=1&images=1&wpDownload=1&curonly=".($src[fullhistory] ? 1 : 0)."&pages=".urlencode($text),
    ));
    if ($src[basiclogin])
        curl_setopt($curl, CURLOPT_USERPWD, $src[basiclogin].':'.$src[basicpassword]);
    $r = curl_exec($curl);
    if (!$r)
        return "Could not retrieve export XML file from '$src[url]/index.php?title=Special:Export&action=submit': ".curl_error($curl);
    curl_close($curl);
    $tx = gettimeofday();
    $tx = $tx[sec] + $tx[usec]/1000000;
    echo sprintf("[$targetname] Retrieved %d bytes in %.2f seconds\n", ftell($fh), $tx-$ts);
    fclose($fh);
    # Дожидаемся сливания картинок
    $still = 1;
    while ($still)
        curl_multi_exec($q[async], $still);
    # Публикуем картинки, вызывая код медиавики
    $total = 0;
    foreach ($q[filenames] as $base => $file)
    {
        fseek($q[fhby][$base], 0, 2);
        $tell = ftell($q[fhby][$base]);
        $total += $tell;
        fclose($q[fhby][$base]);
        curl_close($q[curl][$base]);
        if ($tell && ($src[forceimagedownload] || sha1_file($file) != $q[sha1sum][$base]))
        {
            echo "Importing $file into Image:$base...";
            # Validate a title
            $title = Title::makeTitleSafe(NS_IMAGE, $base);
            if (is_object($title))
            {
                # Check existence
                $image = wfLocalFile($title);
                if ($image->exists())
                    echo "overwriting...";
                else
                    echo "adding...";
                # Import the file
                $archive = $image->publish($file);
                if (!WikiError::isError($archive) && $archive->isGood())
                {
                    if ($image->recordUpload($archive->value, 'Imported image'))
                        echo "done.\n";
                    else
                        echo "failed: could not log upload.\n";
                }
                else
                    echo "failed: could not publish.\n";
            }
            else
                echo "failed: invalid title.\n";
        }
    }
    $ti = gettimeofday();
    $ti = $ti[sec] + $ti[usec]/1000000;
    print sprintf("[$targetname] Retrieved %d objects (total %d bytes) in %.2f seconds\n", count($q[filenames]), $total, $ti-$tx);
    curl_multi_close($q[async]);
    # Логинимся по назначению, если надо
    array_push($temps, $cookiefile = tempnam('/tmp', 'cookie'));
    if ($dest[user] && $dest[password])
    {
        $curl = curl_init("$dest[url]/index.php?title=Special:UserLogin&action=submitlogin&type=login");
        curl_setopt_array($curl, array(
            CURLOPT_POST            => 1,
            CURLOPT_HEADER          => 0,
            CURLOPT_POSTFIELDS      => "wpName=$dest[user]&wpPassword=$dest[password]&wpLoginAttempt=1",
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_COOKIEFILE      => $cookiefile,
            CURLOPT_COOKIEJAR       => $cookiefile,
        ));
        if ($dest[basiclogin])
            curl_setopt($curl, CURLOPT_USERPWD, $dest[basiclogin].':'.$dest[basicpassword]);
        $r = curl_exec($curl);
        if (($code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) != 302)
            return "Could not login into destination wiki under user '$dest[user]': HTTP $code";
        curl_close($curl);
    }
    # Запускаем импорт исправленной XML-ки
    $curl = curl_init("$dest[url]/index.php?title=Special:Import&action=submit");
    curl_setopt_array($curl, array(
        CURLOPT_POST            => 1,
        CURLOPT_HEADER          => 0,
        CURLOPT_POSTFIELDS      => array(source => 'upload', xmlimport => '@'.$fn),
        CURLOPT_RETURNTRANSFER  => 1,
        CURLOPT_COOKIEFILE      => $cookiefile,
        CURLOPT_COOKIEJAR       => $cookiefile,
    ));
    if ($dest[basiclogin])
        curl_setopt($curl, CURLOPT_USERPWD, $dest[basiclogin].':'.$dest[basicpassword]);
    $r = curl_exec($curl);
    if (!$r)
        return "Could not import XML data into '$dest[url]/index.php?title=Special:Import&action=submit': ".curl_error($curl);
    curl_close($curl);
    if (preg_match('/<p[^<>]*class\s*=\s*["\']?error[^<>]*>\s*(.*?)\s*<\/p\s*>/is', $r, $m))
        return "Could not import XML data into $dest[url]: $m[1]";
    $tp = gettimeofday();
    $tp = $tp[sec] + $tp[usec]/1000000;
    print sprintf("[$targetname] Imported in %.2f seconds\n", $tp-$ti);
    # Всё ОК
    return '';
}

function enqueue ($url, $wikiurl, $towiki, $topath, &$q, $auth, $force)
{
    global $cookiefile, $temps;
    if (substr($url, 0, strlen($wikiurl)) == $wikiurl)
        $url = substr($url, strlen($wikiurl));
    $url = preg_replace('#^/*#s', '', $url);
    # Уже поставлено в очередь?
    if ($q[alr][$url])
        return $towiki.'/'.$url;
    $q[alr][$url] = 1;
    $fn = "$topath/$url";
    $fn = urldecode($fn);
    # архивные картинки вызывают странные баги в MediaWiki, так что пропускаем их.
    # - баги типа Fatal error: Cannot redeclare wfspecialupload() (previously
    # declared in /home/www/localhost/WWW/wiki/includes/specials/SpecialUpload.php:12)
    # in /home/www/localhost/WWW/wiki/includes/specials/SpecialUpload.php on line 15
    if (strstr($fn, '!'))
        return $towiki.'/'.$url;
    $curl = curl_init("$wikiurl/$url");
    array_push($temps, $tmpfn = tempnam('/tmp', 'img'));
    $tmpfh = fopen($tmpfn, "wb");
    curl_setopt_array($curl, array(
        CURLOPT_HEADER      => 0,
        CURLOPT_COOKIEFILE  => $cookiefile,
        CURLOPT_COOKIEJAR   => $cookiefile,
        CURLOPT_FILE        => $tmpfh,
    ));
    if ($auth)
        curl_setopt($curl, CURLOPT_USERPWD, $auth);
    # Чтобы не перезасасывать неизменённые файлы
    $stat = stat($fn);
    $sha1 = '';
    if ($stat[9])
    {
        $sha1 = sha1_file($fn);
        if (!$force)
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T', $stat[9])));
    }
    curl_multi_add_handle($q[async], $curl);
    $fn = preg_replace('#^.*/#s', '', $fn);
    $q[filenames][$fn] = $tmpfn;
    $q[sha1sum][$fn] = $sha1;
    $q[fhby][$fn] = $tmpfh;
    $q[curl][$fn] = $curl;
    return $towiki.'/'.$url;
}

function read_config ($file)
{
    if (!($fh = fopen($file, "rb")))
        return false;
    $cfg = array();
    $th = array();
    while ($s = fgets($fh))
    {
        $s = preg_replace('/(^|\s+)(;|\#).*$/s', '', preg_replace('/\s+$/s', '', $s));
        if (!$s)
            continue;
        if (preg_match('/^\s*\[([^\]]*)(Source|Destination)Wiki\]\s*$/is', $s, $m))
        {
            $target = strtolower($m[1]);
            $key = strtolower($m[2]) == 'source' ? 'src' : 'dest';
            if (is_array($cfg[$target][strtolower($m[2]) == 'source' ? 'dest' : 'src']))
                $th[$target] = 1;
        }
        else if (preg_match('/^\s*([^=]*[^\s=])\s*=\s*(.*)/is', $s, $m))
        {
            $k = strtolower($m[1]);
            $v = $m[2];
            if ($k == 'url' || $k == 'path')
                $v = preg_replace('#/+$#s', '', $v);
            else if ($k == 'fullhistory' || $k == 'forceimagedownload')
            {
                $v = strtolower($v);
                $v = $v == 'yes' || $v == 'true' || $v == 'on' || $v == '1';
            }
            if ($target && $key)
                $cfg[$target][$key][$k] = $v;
            else
                $cfg[$k] = $v;
        }
    }
    fclose($fh);
    foreach (array_keys($cfg) as $target)
        if (!$th[$target])
            unset($cfg[$target]);
    if (!count(array_keys($cfg)))
        return false;
    return $cfg;
}
