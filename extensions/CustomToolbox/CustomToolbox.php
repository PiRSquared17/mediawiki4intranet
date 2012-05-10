<?php

/**
 * MediaWiki Custom Toolbox extension
 *
 * Copyright © 2011+ Vitaliy Filippov
 * http://wiki.4intra.net/CustomToolbox
 *
 * Allows to customize Mediawiki TOOLBOX content with MediaWiki:Toolbox-content message
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

if (!defined('MEDIAWIKI'))
{
    ?>
<p>This is the CustomToolbox extension. To enable it, put </p>
<pre>require_once("$IP/extensions/CustomToolbox/CustomToolbox.php");</pre>
<p>at the bottom of your LocalSettings.php.</p>
    <?php
    exit(1);
}

$wgHooks['SkinTemplateToolboxEnd'][] = 'efCustomToolboxSkinTemplateToolboxEnd';

$wgExtensionCredits['other'][] = array(
    'name'        => 'Custom Toolbox',
    'author'      => 'Vitaliy Filippov',
    'version'     => '2011-12-06',
    'description' => 'Allows to customize toolbox with MediaWiki:Toolbox-content message',
    'url'         => 'http://wiki.4intra.net/CustomToolbox',
);

// Output our TOOLBOX links
function efCustomToolboxSkinTemplateToolboxEnd($tpl)
{
    global $wgParser, $wgTitle, $wgUser;
    $text = wfMsgNoTrans('toolbox-content');
    if (wfEmptyMsg('toolbox-content', $text))
        return true;
    $options = ParserOptions::newFromUser($wgUser);
    $output = $wgParser->parse($text, $wgTitle, $options, false, true);
    print $output->getText();
    return true;
}
