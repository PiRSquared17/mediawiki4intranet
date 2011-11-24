<?php
/**
 * MediaWiki camouflaged like custis.ru
 * Configuration:
 *  $wgCatlinksTop =
 *   false (default): Show category links below article content
 *   true: Show category links below and above article content
 *   'only': Show category links above article content only
 *
 * @file
 * @ingroup Skins
 */

if( !defined( 'MEDIAWIKI' ) )
    die( -1 );

require_once( dirname( dirname( __FILE__ ) ) . '/includes/SkinTemplate.php');

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @ingroup Skins
 */
class SkinCustisRu extends SkinTemplate {
    function initPage( OutputPage $out ) {
        parent::initPage( $out );
        $this->skinname  = 'custisru';
        $this->stylename = 'custisru';
        $this->template  = 'CustisRuTemplate';
    }

    function setupSkinUserCss( OutputPage $out ) {
        global $wgHandheldStyle;

        parent::setupSkinUserCss( $out );

        // Append to the default screen common & print styles...
        $out->addStyle( 'custisru/cis.css', 'screen' );
        if( $wgHandheldStyle )
            $out->addStyle( $wgHandheldStyle, 'handheld' );

        $out->addStyle( 'monobook/IE50Fixes.css', 'screen', 'lt IE 5.5000' );
        $out->addStyle( 'monobook/IE55Fixes.css', 'screen', 'IE 5.5000' );
        $out->addStyle( 'monobook/IE60Fixes.css', 'screen', 'IE 6' );
        $out->addStyle( 'monobook/IE70Fixes.css', 'screen', 'IE 7' );
        $out->addStyle( 'custisru/IEFixes.css', 'screen', 'IE' );

        $out->addStyle( 'custisru/common.css', 'screen' );
        $out->addStyle( 'monobook/rtl.css', 'screen', '', 'rtl' );

        $out->addStyle( 'custisru/print.css', 'print' );
        $out->addStyle( 'common/commonPrint.css', 'print' );
    }
}

/**
 * @todo document
 * @ingroup Skins
 */
class CustisRuTemplate extends QuickTemplate {
    var $skin;
    /**
     * Template filter callback.
     * Takes an associative array of data set from a SkinTemplate-based
     * class, and a wrapper for MediaWiki's localization database, and
     * outputs a formatted page.
     *
     * @access private
     */
    function execute() {
        global $wgRequest, $wgScriptPath, $wgCatlinksTop;
        $this->skin = $skin = $this->data['skin'];
        $action = $wgRequest->getText( 'action' );
        $sp = $wgScriptPath;

        // Suppress warnings to prevent notices about missing indexes in $this->data
        wfSuppressWarnings();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="<?php $this->text('xhtmldefaultnamespace') ?>" <?php
    foreach($this->data['xhtmlnamespaces'] as $tag => $ns) {
        ?>xmlns:<?php echo "{$tag}=\"{$ns}\" ";
    } ?>xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">
    <head>
        <meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
        <meta name="google-site-verification" content="anyU3u45qDhtfNNBz4KB8XvxmIlUSVknkU0Nf92117M" />
        <meta name='yandex-verification' content='61f5d9b5d45859e0' />
        <?php $this->html('headlinks') ?>
        <title><?php $this->text('pagetitle') ?></title>
        <?php $this->html('csslinks') ?>

        <!--[if lt IE 7]><script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath') ?>/common/IEFixes.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>
        <meta http-equiv="imagetoolbar" content="no" /><![endif]-->

        <?php print Skin::makeGlobalVariablesScript( $this->data ); ?>

        <script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/common/wikibits.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"><!-- wikibits js --></script>
        <!-- Head Scripts -->
<?php $this->html('headscripts') ?>
<?php   if($this->data['jsvarurl']) { ?>
        <script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('jsvarurl') ?>"><!-- site js --></script>
<?php   } ?>
<?php   if($this->data['pagecss']) { ?>
        <style type="text/css"><?php $this->html('pagecss') ?></style>
<?php   }
        if($this->data['usercss']) { ?>
        <style type="text/css"><?php $this->html('usercss') ?></style>
<?php   }
        if($this->data['userjs']) { ?>
        <script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('userjs' ) ?>"></script>
<?php   }
        if($this->data['userjsprev']) { ?>
        <script type="<?php $this->text('jsmimetype') ?>"><?php $this->html('userjsprev') ?></script>
<?php   }
        if($this->data['trackbackhtml']) print $this->data['trackbackhtml']; ?>
    </head>
<body<?php if($this->data['body_ondblclick']) { ?> ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload']) { ?> onload="<?php $this->text('body_onload') ?>"<?php } ?>
 class="mediawiki <?php $this->text('dir') ?> <?php $this->text('pageclass') ?> <?php $this->text('skinnameclass') ?>">

<table class="cen header screenonly">
<tr>
 <td class="header_background_left" rowspan="2">
  <table class="cen">
   <tr>
    <td width="100%"><a href="<?=$sp?>/"><img align="top" <?= $skin->tooltipAndAccesskey('n-mainpage') ?> height="77" width="210" src="<?=$sp?>/skins/custisru/header_logo.png" /></a></td>
    <td><img alt="" align="top" height="77" width="10" src="<?=$sp?>/skins/custisru/header_shadow_left.gif" /></td>
   </tr>
  </table>
 </td>
 <td class="header_right" width="82%">
  <div class="portlet" id="p-personal">
    <h5><?php $this->msg('personaltools') ?></h5>
    <div class="pBody">
        <ul>
  <?php     foreach($this->data['personal_urls'] as $key => $item) { ?>
            <li id="<?php echo Sanitizer::escapeId( "pt-$key" ) ?>"<?php
                if ($item['active']) { ?> class="active"<?php } ?>><a href="<?php
            echo htmlspecialchars($item['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-'.$key) ?><?php
            if(!empty($item['class'])) { ?> class="<?php
            echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
            echo htmlspecialchars($item['text']) ?></a></li>
  <?php     } ?>
        </ul>
    </div>
  </div>
 </td>
 <td class="ar" rowspan="2"><img alt="" height="77" width="17" src="<?=$sp?>/skins/custisru/header_shadow_right.gif" /></td>
</tr>
<tr>
 <td class="header_bottom_right vb" width="82%">
  <table>
   <tr>
    <td class="shadow_text"></td>
    <td class="vb">
     <div id="p-cactions" class="portlet">
        <h5><?php $this->msg('views') ?></h5>
        <div class="pBody">
            <ul>
     <?php      foreach($this->data['content_actions'] as $key => $tab) {
                    echo '
                 <li id="' . Sanitizer::escapeId( "ca-$key" ) . '"';
                    if( $tab['class'] ) {
                        echo ' class="'.htmlspecialchars($tab['class']).'"';
                    }
                    echo'><a href="'.htmlspecialchars($tab['href']).'"';
                    // We don't want to give the watch tab an accesskey if the
                    // page is being edited, because that conflicts with the
                    // accesskey on the watch checkbox.  We also don't want to
                    // give the edit tab an accesskey, because that's fairly su-
                    // perfluous and conflicts with an accesskey (Ctrl-E) often
                    // used for editing in Safari.
                    if( in_array( $action, array( 'edit', 'submit' ) )
                    && in_array( $key, array( 'edit', 'watch', 'unwatch' ))) {
                        echo $skin->tooltip( "ca-$key" );
                    } else {
                        echo $skin->tooltipAndAccesskey( "ca-$key" );
                    }
                    echo '>'.htmlspecialchars($tab['text']).'</a></li>';
                } ?>
            </ul>
        </div>
     </div>
     <script type="<?php $this->text('jsmimetype') ?>"> if (window.isMSIE55) fixalpha(); </script>
    </td>
   </tr>
  </table>
 </td>
</tr>
</table>

<table class="printblock">
 <tr>
  <td width="18%" class="screenonly">
   <table class="cen">
    <tr>
     <td><img class="iefix1px" alt="" height="56" width="5" src="<?=$sp?>/skins/custisru/menu_top_left.gif"></td>
     <td class="menu_top ar" width="100%"><img class="iefix1px" alt="" height="56" width="10" src="<?=$sp?>/skins/custisru/menu_top_right.gif"></td>
    </tr>
    <?php
    $sidebar = $this->data['sidebar'];
    if ( !isset( $sidebar['SEARCH'] ) ) $sidebar['SEARCH'] = true;
    if ( !isset( $sidebar['TOOLBOX'] ) ) $sidebar['TOOLBOX'] = true;
    if ( !isset( $sidebar['LANGUAGES'] ) ) $sidebar['LANGUAGES'] = true;
    foreach ($sidebar as $boxName => $cont) {
        if ( $boxName == 'SEARCH' ) {
            $this->searchBox();
        } elseif ( $boxName == 'TOOLBOX' ) {
            $this->toolbox();
        } elseif ( $boxName == 'LANGUAGES' ) {
            $this->languageBox();
        } else {
            $this->customBox( $boxName, $cont );
        }
    }
    ?>
   </table>
   <img alt="" height="48" width="5" src="<?=$sp?>/skins/custisru/menu_bottom_shadow.gif">
  </td>
  <td rowspan="2" valign="top" class="info_text" width="82%" id="content">
   <a name="top" id="top"></a>
   <div class="headline">
    <img width="53" height="56" alt="" src="<?=$sp?>/skins/custisru/icon_contact.gif" class="screenonly" />
    <h1 id="firstHeading" class="firstHeading"><?php $this->data['displaytitle']!=""?$this->html('title'):$this->text('title') ?></h1>
   </div>
   <?php /* Site Notice */ if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
   <div id="bodyContent">
    <h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
    <div id="contentSub"><?php $this->html('subtitle') ?></div>
    <?php if($this->data['undelete']) { ?><div id="contentSub2"><?php     $this->html('undelete') ?></div><?php } ?>
    <?php if($this->data['newtalk'] ) { ?><div class="usermessage"><?php $this->html('newtalk')  ?></div><?php } ?>
    <?php if($this->data['showjumplinks']) { ?><div id="jump-to-nav"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div><?php } ?>
    <!-- start content -->
    <?php if($this->data['catlinks'] && $wgCatlinksTop) { ?><div id="catlinks-top"><?php $this->html('catlinks'); ?></div><div style="clear: left"></div><?php  } ?>
    <?php $this->html('bodytext') ?>
    <?php if($this->data['catlinks'] && $wgCatlinksTop !== 'only') { $this->html('catlinks'); } ?>
    <!-- end content -->
    <?php if($this->data['dataAfterContent']) { $this->html ('dataAfterContent'); } ?>
    <div class="visualClear"></div>
   </div>
  </td>
  <td class="headline_right ar screenonly"><img alt="" height="73" width="17" src="<?=$sp?>/skins/custisru/headline_right.gif" /></td>
 </tr>
 <tr class="screenonly">
  <td valign="top"></td>
  <td class="headline_right ar vb"><img alt="" height="89" width="17" src="<?=$sp?>/skins/custisru/info_bottom_right.gif" /></td>
 </tr>
</table>

<div id="prefooter1"></div>

<table bgcolor="#FBFCFD" class="screenonly">
 <tr>
  <td class="separator_left" width="18%"></td>
  <td class="separator_right" width="82%"></td>
  <td class="separator_right"><img alt="" height="15" width="17" src="<?=$sp?>/skins/custisru/spacer.gif" /></td>
 </tr>
</table>

<div id="footer">
<?php
        if($this->data['poweredbyico']) { ?>
                <div id="f-poweredbyico"><?php $this->html('poweredbyico') ?></div>
<?php   }
        if($this->data['copyrightico']) { ?>
                <div id="f-copyrightico"><?php $this->html('copyrightico') ?></div>
<?php   }

        // Generate additional footer links
        $footerlinks = array(
            'lastmod', 'viewcount', 'numberofwatchingusers', 'credits', 'copyright',
            'privacy', 'about', 'disclaimer', 'tagline',
        );
        $validFooterLinks = array();
        foreach( $footerlinks as $aLink ) {
            if( isset( $this->data[$aLink] ) && $this->data[$aLink] ) {
                $validFooterLinks[] = $aLink;
            }
        }
        if ( count( $validFooterLinks ) > 0 ) {
?>          <ul id="f-list">
<?php
            foreach( $validFooterLinks as $aLink ) {
                if( isset( $this->data[$aLink] ) && $this->data[$aLink] ) {
?>                  <li id="<?php echo$aLink?>"><?php $this->html($aLink) ?></li>
<?php           }
            }
?>
            </ul>
<?php   }
?>
</div>

<div class="bottom" style="width: 100%"></div>
<?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
<?php $this->html('reporttime') ?>
<?php if ( $this->data['debug'] ): ?>
<!-- Debug output:
<?php $this->text( 'debug' ); ?>

-->
<?php endif; ?>
</body></html>
<?php
    wfRestoreWarnings();
    } // end of execute() method

    /*************************************************************************************************/
    function searchBox()
    {
        ob_start();
?>
<div id="searchBody" class="pBody">
    <form action="<?php $this->text('searchaction') ?>" id="searchform"><div>
        <input id="searchInput" name="search" type="text"<?php echo $this->skin->tooltipAndAccesskey('search');
            if( isset( $this->data['search'] ) ) {
                ?> value="<?php $this->text('search') ?>"<?php } ?> />
        <input type='submit' name="go" class="searchButton" id="searchGoButton" value="<?php $this->msg('searcharticle') ?>"<?php echo $this->skin->tooltipAndAccesskey( 'search-go' ); ?> />&nbsp;<input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg('searchbutton') ?>"<?php echo $this->skin->tooltipAndAccesskey( 'search-fulltext' ); ?> />
    </div></form>
</div>
<?php
        $cont = ob_get_contents();
        ob_end_clean();
        $this->customBox('search', $cont);
    }

    /*************************************************************************************************/
    function toolbox()
    {
        global $wgScriptPath;
        $bar = 'toolbox';
        $cont = array();
        if($this->data['notspecialpage'])
            $cont[] = array(
                'href' => $this->data['nav_urls']['whatlinkshere']['href'],
                'id'   => 't-whatlinkshere',
                'text' => $this->translator->translate('whatlinkshere'),
            );
        if($this->data['nav_urls']['recentchangeslinked'])
            $cont[] = array(
                'href' => $this->data['nav_urls']['recentchangeslinked']['href'],
                'id'   => 't-recentchangeslinked',
                'text' => $this->translator->translate('recentchangeslinked'),
            );
        if(isset($this->data['nav_urls']['trackbacklink']))
            $cont[] = array(
                'href' => $this->data['nav_urls']['trackbacklink']['href'],
                'id'   => 't-trackbacklink',
                'text' => $this->translator->translate('trackbacklink'),
            );
        if($this->data['feeds'])
        {
            $a = '<img alt="" src="'.$wgScriptPath.'/skins/custisru/feed.png" width="16" height="16" />';
            foreach($this->data['feeds'] as $key => $feed)
                $a .= '<a class="m_uplink m_feedlink" href="'.htmlspecialchars($feed['href']).'" '.$this->skin->tooltipAndAccesskey("feed-$key").'>'.$feed['text'].'</a>';
            $cont[] = array(html => $a);
        }

        foreach(array('contributions', 'log', 'blockip', 'emailuser', 'upload', 'specialpages') as $special)
            if($this->data['nav_urls'][$special])
                $cont[] = array(
                    'href' => $this->data['nav_urls'][$special]['href'],
                    'id'   => "t-$special",
                    'text' => $this->translator->translate($special),
                );

        if(!empty($this->data['nav_urls']['print']['href']))
            $cont[] = array(
                'href' => $this->data['nav_urls']['print']['href'],
                'id'   => "t-print",
                'text' => $this->translator->translate('printableversion'),
            );

        if(!empty($this->data['nav_urls']['permalink']['href']))
            $cont[] = array(
                'href' => $this->data['nav_urls']['permalink']['href'],
                'id'   => "t-permalink",
                'text' => $this->translator->translate('permalink'),
            );
        else if ($this->data['nav_urls']['permalink']['href'] === '')
            $cont[] = array(
                'id'   => "t-ispermalink",
                'text' => $this->translator->translate('permalink'),
            );

        wfRunHooks('SkinTemplateToolboxLinks', array(&$this, &$cont));
        $this->customBox($bar, $cont);

        // A hack to support extensions which use SkinTemplateToolboxEnd hook
        ob_start();
        wfRunHooks('SkinTemplateToolboxEnd', array(&$this));
        $ob = ob_get_contents();
        ob_end_clean();
        if ($ob !== '')
        {
            $ob = preg_replace('#<li[^<>]*>(.*?)</li\s*>#is',
'<tr><td width="20" class="menu_partition_sep"><img alt="" height="1" width="5" src="'.$wgScriptPath.'/skins/custisru/spacer.gif"></td>
<td width="100%" class="menu_normal_text">\1</td></tr>
<tr><td class="menu_partition_sep"></td>
<td class="menu_separator"></td></tr>
', $ob);
            print $ob;
        }
    }

    /*************************************************************************************************/
    function languageBox()
    {
        if(!$this->data['language_urls'])
            return;
        $box = 'otherlanguages';
        $cont = array();
        foreach ($this->data['language_urls'] as $lang)
            $cont[] = array(
                href => $lang['href'],
                text => $lang['text'],
            );
        $this->customBox($box, $cont);
    }

    /*************************************************************************************************/
    function customBox( $bar, $cont ) {
        global $wgScriptPath;
        $sp = $wgScriptPath;
        if (!$cont)
            return '';
?>  <tr>
     <td class="menu_left_background"></td>
     <td class="menu_level_1 vb"><img alt="" height="12" width="4" src="<?=$sp?>/skins/custisru/ic_pass.gif" /><span class="menu_level_1"><?php $out = wfMsg( $bar ); if (wfEmptyMsg($bar, $out)) echo $bar; else echo $out; ?></span></td>
    </tr>
    <tr>
     <td class="menu_partition_sep"></td>
     <td class="menu_separator"></td>
    </tr>
<?php   if (is_array($cont) && count($cont) > 0) {
            $last = array_keys($cont);
            $last = $last[count($last)-1];
            foreach($cont as $key => $val) { ?>
    <tr>
     <td width="20" class="menu_partition_sep"><img alt="" height="1" width="5" src="<?=$sp?>/skins/custisru/spacer.gif"></td>
     <td width="100%" class="menu_normal_text">
      <a class="m_uplink" <?= $val['href'] ? 'href="'.htmlspecialchars($val['href']).'"' : '' ?> <?= $val['id'] ? $this->skin->tooltipAndAccesskey($val['id']) : '' ?>><?= $val['html'] ? $val['html'] : htmlspecialchars($val['text']) ?></a>
     </td>
    </tr>
    <tr>
     <td class="menu_partition_sep"></td>
     <td class="menu_separator"></td>
    </tr>
<?php       } } else { # allow raw HTML block to be defined by extensions ?>
    <tr>
     <td class="menu_partition_sep"></td>
     <td class="custom_box"><?=$cont?></td>
    </tr>
    <tr>
     <td class="menu_left_background"></td>
     <td class="menu_separator"></td>
    </tr>
<?php   }
    }

} // end of class
