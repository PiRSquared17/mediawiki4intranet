<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Stas Fomin <stas-fomin@yandex.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if (!defined('MEDIAWIKI')) die();
require_once( 'DifferenceEngine.php' );
require_once( 'LinksUpdate.php' );


$wgExtensionFunctions[] = "wfBatchEditor";

function wfBatchEditor() {
  global $IP, $wgMessageCache;
  require_once( "$IP/includes/SpecialPage.php" );

  $wgMessageCache->addMessages(
    array(
      'specialpagename' => 'BatchEditor',
      'batcheditor' => "Batch Editor"
    )
  );

class BatchEditorPage extends SpecialPage {
  function BatchEditorPage() {
      SpecialPage::SpecialPage( 'BatchEditor' );
  }

  function execute( $par = null ) {
    global $wgOut, $wgRequest, $wgTitle, $wgUser, $wgContLang;
    $wgOut->setPagetitle('BatchEditor');
    
    extract( $wgRequest->getValues( 'a_titles' ) );
    extract( $wgRequest->getValues( 'a_comment' ) );
    extract( $wgRequest->getValues( 'a_find' ) );
    extract( $wgRequest->getValues( 'a_replace' ) );
    extract( $wgRequest->getValues( 'a_add' ) );
    extract( $wgRequest->getValues( 'a_delete' ) );
    extract( $wgRequest->getValues( 'a_action' ) );

    $a_minor=$wgRequest->getCheck( 'a_minor' );
    $a_minor_checked='';
    if ($a_minor) {$a_minor_checked=' checked="checked" ';}

    $parserOptions = ParserOptions::newFromUser( $wgUser );
    $numSessionID = preg_replace( "[\D]", "", session_id() );
    $action = $wgTitle->escapeLocalUrl("");

    $interface_form=<<<EOT
This page is intended to batch (mass) editing of [[{{SITENAME}}]] articles. Please, use this promptly!
<html>
<form action='$action' method='POST'>
<table>
<tr>
  <td><b>Articles:</b><br><small>Article titles,<br/> one per line</small></td>  
  <td width="90%"><textarea name="a_titles" rows="8" cols="60">{$a_titles}</textarea></td>  
</tr>
<tr>
  <td>Comment:</td>  
  <td width="90%">
    <input name="a_comment" size="80" value="{$a_comment}" />
    <input type="checkbox" name="a_minor" {$a_minor_checked} value="1"/><label for='a_minor'>Minor Edit</label>
  </td>  
</tr>
</table>
<table>
<tr>
  <th>Find</th><th>Replace</th>
</tr>
<tr>
  <td><textarea name="a_find"     rows="5" cols="45">
{$a_find}</textarea></td>  
  <td><textarea name="a_replace"  rows="5" cols="45">
{$a_replace}</textarea></td>  
</tr>
</table>
<table>
<tr>
  <th>Add lines</th><th>Delete lines</th>    
</tr>
<tr>
  <td><textarea name="a_add"      rows="5" cols="45">
{$a_add}</textarea></td>  
  <td><textarea name="a_delete"   rows="5" cols="45">
{$a_delete}</textarea></td>  
</tr>
</table>
<table width="100%">
<tr><td align="center">
  <input name='a_action' value='Preview' type='submit'>
  <input name='a_action' value='Run' type='submit'>
</td></tr>
</table>
</form>
</html>
EOT;
    $wgOut->addWikiText($interface_form);
    if (isset($a_titles))
    {
      if (isset($a_action) && ($a_action=="Run"))
      {
        $wgOut->addWikiText("= Results =");
      }
      else
      {
        $wgOut->addWikiText("= Preview =");
      }
      $arr_titles=split("\n", $a_titles);
      foreach($arr_titles as $s_title) 
      {
        $s_title=trim(str_replace( "\r", "", $s_title));
        if ($s_title) {
          $wgOut->addWikiText("==[[$s_title]]==");
          $title = Title::newFromURL( $s_title );
          if  ($title) 
          {
            $article = new Article( $title  );
            if ($article->mTitle->getArticleID()==0) 
            {
              $wgOut->addWikiText("Article [[$s_title]]  not found!");
            } 
            else 
            {
              $article->loadContent(false);
              $oldtext=$article->mContent;
              $newtext=$oldtext;
              if (isset($a_find)) {
                $a_find=trim($a_find);
                $a_find=str_replace("\r","",$a_find);
                $tmp=str_replace("\n","",$a_find);
                $tmp=str_replace("\r","",$tmp);
                $tmp=trim($tmp);
                if ($tmp) 
                {
                  $newtext= str_replace($a_find,$a_replace,$newtext);
                }
              }
              if (isset($a_delete)) {
                $arr_delline=split("\n", $a_delete);
                foreach($arr_delline as $s_delline) 
                {
                  $s_delline = str_replace("\r","", $s_delline);
                  if (trim(str_replace("\n","", $s_delline))!=""){
                    $newtext = str_replace($s_delline,"",$newtext);
                  }
                }
              }
              if (isset($a_add)) {
                if (trim($a_add)!=""){
                  $newtext.= "\n" . $a_add;
                }
              }
              //$ota = explode( "\n", $wgContLang->segmentForDiff( $oldtext ) );
              //$nta = explode( "\n", $wgContLang->segmentForDiff( $newtext ) );
              //$diffs = new Diff( $ota, $nta );
              //$formatter = new TableDiffFormatter();
              //$res = $formatter->format( $diffs );
              
              //$de=new DifferenceEngine($oldtext, $newtext);
              $de=new DifferenceEngine();
              $de->setText($oldtext, $newtext);
              //$de->showDiffStyle();
              $res = $de->getDiffBody();
              $res = "
                  <table class='diff'>
                  <col class='diff-marker' />
                  <col class='diff-content' />
                  <col class='diff-marker' />
                  <col class='diff-content' />
                  <tr>
                      <td colspan='2' width='50%' align='center' class='diff-otitle'>Old text</td>
                      <td colspan='2' width='50%' align='center' class='diff-ntitle'>New text</td>
                  </tr>
              " . $res ."</table>";
              $wgOut->addStyle( 'common/diff.css' );
              $wgOut->addHTML($res);
            }
            if ($a_action=="Run")
            {
              global $wgLinkCache, $wgParser,$wgLinkHolders,$wgInterwikiLinkHolders,$wgDeferredUpdateList;
              $article->updateArticle( $newtext, $a_comment, $a_minor, $article->mTitle->userIsWatching(), true);
            }
          }
        }
      } 
    }        
   }
}
    
SpecialPage::addPage( new BatchEditorPage );
}
?>
