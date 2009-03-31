<?php

# MediaWiki ShowHide extension v0.1.2
#
# Based @ http://meta.wikimedia.org/wiki/Write_your_own_MediaWiki_extension
# Based @ http://www.mediawiki.org/wiki/Extension:ShowHide <¿ 2005 Nikola Smolenski <smolensk@eunet.yu>>
# Contains code from MediaWiki's Skin.php and wikibits.js
# ¿ 2009 Vitaliy Filippov <vfilippov@custis.ru>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# To install, copy the extension to your extensions directory and add line
# include("extensions/ShowHide.php");
# to the bottom of your LocalSettings.php
#
# Example syntax:
#
# <showhide>
# Some text (usually title) which will not be hidden
# <hide>Text which will be hidden</hide>
# </showhide>
#
# If <show></show> tags are used instead of <hide></hide>, the text will be
# shown by default.

$wgExtensionFunctions[] = "wfShowHideExtension";

function wfShowHideExtension()
{
    $GLOBALS[wgParser]->setHook("showhide","ShowHideExtension");
}

function ShowHideExtension($in,$argv,&$parser)
{
    global $wgOut;
    static $numrun = 0;

    $out = $parser->unstrip($parser->recursiveTagParse($in),$parser->mStripState);
    if ((($s = strpos($out,htmlentities("<show>"))) !== false &&
               strpos($out,htmlentities("</show>")) > $s) ||
        (($h = strpos($out,htmlentities("<hide>"))) !== false &&
               strpos($out,htmlentities("</hide>")) > $h))
    {
        if (!$numrun)
        {
            $wgOut->addHTML(
"<script type=\"text/javascript\"><!--
shWas=new Array();
function showSHToggle(show,hide,num) {
        if(document.getElementById) {
                document.writeln('<span class=\'toctoggle\'>[<a href=\"javascript:toggleSH('+num+')\" class=\"internal\">' +
                '<span id=\"showlink'+num+'\" style=\"display:none;\">' + show + '</span>' +
                '<span id=\"hidelink'+num+'\">' + hide + '</span>' +
                '</a>]</span>');
        }
}
function toggleSH(num) {
        var shmain = document.getElementById('showhide'+num);
        var sh = document.getElementById('shinside'+num);
        var showlink=document.getElementById('showlink'+num);
        var hidelink=document.getElementById('hidelink'+num);
        if(sh.style.display == 'none') {
                sh.style.display = shWas[num];
                hidelink.style.display='';
                showlink.style.display='none';
                shmain.className = '';
        } else {
                shWas[num] = sh.style.display;
                sh.style.display = 'none';
                hidelink.style.display='none';
                showlink.style.display='';
                shmain.className = 'tochidden';
        }
} // --></script>
");
        }
        $numrun++;

        if ($s !== false)
            $act = "show";
        else
            $act = "hide";
        $hideline = ' <script type="text/javascript">showSHToggle("' . addslashes( wfMsg('showtoc') ) . '","' . addslashes( wfMsg('hidetoc') ) . '",' . $numrun . ')</script>';

        $out =
            rtrim(substr($out,0,strpos($out,"&lt;$act&gt;")),"\n\r") .
            $hideline .
            substr($out,strpos($out,"&lt;$act&gt;"));
        $out = str_replace(
            array(htmlentities("<$act>"), htmlentities("</$act>")),
            array("<div id=\"shinside$numrun\">","</div>"),
            $out
        );
        $out = "<span id=\"showhide$numrun\">$out</span>";
        if ($act == "hide")
            $out .= "<script type=\"text/javascript\">toggleSH($numrun)</script>";
    }
    return $out;
}
