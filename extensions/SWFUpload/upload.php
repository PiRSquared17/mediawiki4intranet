<?php
/**
 * upload.php -- Simple PHP backend for SWFUpload and MediaWiki
 * Copyright 2009 Vitaliy Filippov <vitalif@mail.ru>
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
    chdir('../..');
    error_reporting(E_ERROR | E_PARSE);
    print "m";
    ob_start();
    require 'index.php';
    $ob = ob_get_contents();
    ob_end_clean();
    if (preg_match('#<span[^<>]*class=["\']error[^<>]*>(.*?)</span>#is', $ob, $m))
        echo trim($m[1]);
    else if ($ob)
        echo "msg-unknown-error";
}
