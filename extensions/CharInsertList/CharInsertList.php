<?php
# Copyright (C) 2010 Vitaliy Filippov <vitalif at mail.ru>
# http://yourcmc.ru/wiki/CharInsertList_(MediaWiki)
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html

/**
 * Extension is very similar to CharInsert, but allows to create HTML
 * listboxes (<select>) with charinsert items instead of simple hyperlinks.
 *
 * Usage syntax:
 * <listinsert [attributes]>
 * Item Name = Item Text
 * Item Name = Multiline \
 *             Item Text
 * Item Name = Prefix+Suffix \
 *             CharInsert-like syntax
 * </listinsert>
 *
 * [attributes] are copied to HTML <select> attributes without any change.
 *
 * @author Vitaliy Filippov <vitalif at mail.ru>
 * @addtogroup Extensions
 */

if (!defined('MEDIAWIKI'))
    die();

if (defined('MW_SUPPORTS_PARSERFIRSTCALLINIT'))
    $wgHooks['ParserFirstCallInit'][] = 'efListInsertSetup';
else
    $wgExtensionFunctions[] = 'efListInsertSetup';

$wgExtensionCredits['parserhook'][] = array(
    'name' => 'CharInsertList',
    'author' => 'VitaliyFilippov',
    'svn-date' => '$LastChangedDate: 2008-06-17 00:54:29 +0400 (Втр, 17 Июн 2008) $',
    'svn-revision' => '$LastChangedRevision: 36357 $',
    'url' => 'http://yourcmc.ru/wiki/CharInsertList_(MediaWiki)',
    'description' => 'Allows creation of HTML selectboxes for inserting non-standard characters',
);

function efListInsertSetup()
{
    global $wgParser;
    $wgParser->setHook('listinsert', 'efListInsertParserHook');
    return true;
}

function efListInsertParserHook($text, $attrs, $parser)
{
    $data = explode("\n", trim($text));
    if (!$data)
        return '';
    $line = trim($data[count($data)-1]);
    $html = '';
    for ($i = count($data)-2; $i >= 0; $i--)
    {
        $prev = trim($data[$i]);
        if (substr($prev, -1) == "\\")
            $line = substr($prev, 0, -1) . $line;
        else
        {
            $html = efListInsertOption($line) . $html;
            $line = $prev;
        }
    }
    $html = efListInsertOption($line) . $html;
    $select_attr = '';
    foreach ($attrs as $k => $v)
        $select_attr .= htmlspecialchars($k, ENT_QUOTES) . '="' . htmlspecialchars($v, ENT_QUOTES).'" ';
    $html = '<select '.$select_attr.'onchange="if(this.value){var a=this.value.split(\'+\',2);if(a.length>1)insertTags(a[0],a[1],\'\');else{insertTags(a[0],\'\',\'\');}this.selectedIndex=0;}"><option value="">-</option>' . $html . '</select>';
    return $html;
}

function efListInsertOption($line)
{
    list($name, $value) = explode("=", $line, 2);
    $name = trim($name);
    $value = trim($value);
    return '<option value="'.htmlspecialchars($value, ENT_QUOTES).'">'.htmlspecialchars($name, ENT_QUOTES).'</option>';
}
