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
    global $wgHooks;
    $wgHooks[ParserAfterTidy][] = "ShowHideExtension";
}

function ShowHideExtension(&$parser, &$out)
{
    static $run = 0;
    if ((($s = strpos($out,htmlentities("<show>"))) !== false &&
               strpos($out,htmlentities("</show>")) > $s) ||
        (($h = strpos($out,htmlentities("<hide>"))) !== false &&
               strpos($out,htmlentities("</hide>")) > $h))
    {
        $hideline = ' <script type="text/javascript">showSHToggle("' . addslashes( wfMsg('showtoc') ) . '","' . addslashes( wfMsg('hidetoc') ) . '",' . $run . ')</script> ';
        if ($s !== false)
        {
            $out = rtrim(substr($out,0,$s)) . $hideline . ltrim(substr($out,$s));
            $act = 'show';
        }
        else
        {
            $out = rtrim(substr($out,0,$h)) . $hideline . ltrim(substr($out,$h));
            $act = 'hide';
        }

        $out = str_replace(
            array(htmlentities("<$act>"), htmlentities("</$act>"), htmlentities("<showhide>"), htmlentities("</showhide>")),
            array("<span id=\"showhide$run\"><div id=\"shinside$run\">", "</div></span>", "", ""),
            $out
        );
        if ($act == "hide")
            $out .= "<script type=\"text/javascript\">toggleSH($run)</script>";
        if (!$run++)
            $out =
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
" . $out;
    }
    return true;
}
