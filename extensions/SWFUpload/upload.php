<?php
/**
 * upload.php -- Simple PHP backend for using SWFUpload inside MediaWiki
 * Copyright 2009-2011 Vitaliy Filippov <vitalif@mail.ru>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @file
 * @ingroup Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */

if ($_POST['cookies'])
{
    $_GET['title'] = 'Special:Upload';
    $name = $_FILES['wpUploadFile']['name'];
    $name = trim($name, " \t\n\r\x0B\"\'\0");
    $name = preg_replace("#.*[/\\\\]#is", '', $name);
    $name = $_REQUEST['swfuploadNamePrefix'] . $name;
    $_GET['wpDestFile'] = $name;
    $_COOKIE = unserialize($_POST['cookies']);
    $args = array();
    foreach ($_GET as $k => $v)
        $args[] = urlencode($k).'='.urlencode($v);
    $args = join('&', $args);
    $_SERVER['REQUEST_URI'] = preg_replace('#extensions/SWFUpload/upload\.php(\?.*)?$#s', 'index.php?' . $args, $_SERVER['REQUEST_URI']);
    $IP = preg_replace('#[/\\\\]*[^/\\\\]+[/\\\\]+[^/\\\\]+[/\\\\]+upload\.php$#', '', $_SERVER['SCRIPT_FILENAME']);
    chdir($IP);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ob_start();
    require 'index.php';
    $ob = ob_get_contents();
    ob_end_clean();
    print "m";
    if (preg_match('#<span[^<>]*class=["\']error[^<>]*>(.*?)</span>#is', $ob, $m))
        echo trim($m[1]);
    elseif ($ob)
    {
        echo "msg-unknown-error";
        wfDebug("** SWFUpload ERROR **\n$ob\n** /SWFUpload ERROR **\n");
    }
}
exit;
