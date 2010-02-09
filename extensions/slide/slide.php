<?php
# Copyright (C) 2005 TooooOld <tianshuen@gmail.com>
# http://www.mediawiki.org/
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
 * Extension to create slide show
 *
 * @author TooooOld <tianshuen@gmail.com>
 * @package MediaWiki
 * @subpackage Extensions
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die();
}

define( 'SLIDE_INC_MARK', 	'(step)' );
define( 'SLIDE_PAGE_BREAK', '\\\\\\\\' );
$ce_slide_tpl_file 	= "$IP/extensions/slide/slide.htm";

$wgExtensionFunctions[] = 'setupSlideShow';

function setupSlideShow() {

	global $wgParser, $wgRequest;

    $wgParser->setHook( 'slide', 'slide' );

	$ce_gen			= $wgRequest->getText('ce_gen', false);
	$ce_slide_title   = $wgRequest->getText('title', false);

	$ce_slide		= $wgRequest->getText('ce_slide', false);
	$ce_slide_style	= $wgRequest->getText('ce_style', false);

	if($ce_slide){
		$ceTitle =& Title::newFromURL($ce_slide_title);
		$slideShow = new slideShow($ceTitle, $ce_slide_style);
		$slideShow->genSlideFile();
	}
}

function slide( $style = 'default' ) {

 	global $wgTitle, $wgScriptPath;

	if(is_object($wgTitle)){
		$slideShow = new slideShow($wgTitle, $style);
		$url = $wgTitle->escapeLocalURL("ce_style=$style&ce_slide=true");
		return '<div class="floatright"><span>
				<a href="'.$url.'" class="image" title="Slide Show" target="_blank">
				<img src="'.$wgScriptPath.'/extensions/slide/'.$slideShow->style.'/'.$slideShow->style.'.gif" alt="Slide Show" width="240px" /><br />
				Slide Show</a></span></div>';
	}else{
		return "this is a slide show page.\n";
	}
}

class slideShow
{
 	var $sTitle;
 	var $style;
 	var $mContent;
 	var $mSlides;
 	var $ts;

	function slideShow($slideTitle, $style = 'default'){
		
		if(is_object($slideTitle)){
		 	$this->sTitle = $slideTitle->getFullText();
			$slideArticle = new Article( $slideTitle );
			$this->ts = $slideArticle->getTimestamp();
			$this->mContent = $slideArticle->getContent(0);
			$this->setStyle($style);
			$this->slideParser();
		}else{
			wfDebug("Slide: Error! Pass a title object, NOT a title string!\n");
		}
	}
	
	function setStyle($style = 'default'){
		$this->style = $style;
	}

	function slideParser(){
		$secs = preg_split(
			'/(^==[^=].*?==)(?!\S)/mi',
			$this->mContent, -1,
			PREG_SPLIT_DELIM_CAPTURE);

		$this->mSlides = array();

		$secCount = count($secs);
		for($i=1; $i<$secCount; $i=$i+2)
		{
		 	$this->mSlides[] = array('title' => str_replace('==', '', $secs[$i]),
		 							  'content' => $secs[$i+1]);
		}
		$this->desc = $secs[0];
		return true;
	}

	function genSlideFile(){

		global $ce_slide_tpl_file, $ce_file_dir, $ce_slide_tpl,
				$wgUser, $wgContLang, $wgOut;

	 	if(empty($this->mSlides)){
	 	 	return false;
	 	}

		#get template
		$ce_slide_tpl = @file_get_contents($ce_slide_tpl_file);
		if( '' == $ce_slide_tpl ){
			return false;
		}

		#generate content
		$fc = '';
		$s = "<div class=\"slide\"><h1>%s</h1><div class=\"slidecontent\">%s</div></div>\n";

		$options =& ParserOptions::newFromUser( $wgUser );
		$fileParser = new Parser;
		
		$fileParser->setHook( 'slide','ceFakeSlide' );
		$nt = & Title::newFromText( $this->sTitle );

		foreach( $this->mSlides as $slide ){
			
			$title = $slide['title'];

//			if( ! preg_match( '/^'.SLIDE_PAGE_BREAK.'/mi', $slide['content']) ){
			if( ! preg_match( '/'.SLIDE_PAGE_BREAK.'$/mi', $slide['content']) ){
//			if( ! strpos( $slide['content'], SLIDE_PAGE_BREAK ) ){
				$output =& $fileParser->parse($slide['content']."\n__NOTOC__\n__NOEDITSECTION__", $nt, $options);
				$slideContent = $output->getText();
				if(strpos($title, SLIDE_INC_MARK)){
					$slideContent = str_replace('<ul>', '<ul class="incremental">', $slideContent);
					$slideContent = str_replace('<ol>', '<ol class="incremental">', $slideContent);
					$title = str_replace(SLIDE_INC_MARK, '', $title);
				}
				$fc .= sprintf($s, $title, $slideContent);
			} else {
//				$ms = explode( SLIDE_PAGE_BREAK, $slide['content'] );
				$ms = preg_split( '/'.SLIDE_PAGE_BREAK.'$/mi', $slide['content'] );
				$sc = count($ms);
				foreach( $ms as $i=>$ss ){
					$title = $slide['title'] . " (".($i+1)."/$sc)";
					$output =& $fileParser->parse($ss."\n__NOTOC__\n__NOEDITSECTION__", $nt, $options);
					$slideContent = $output->getText();
					if(strpos($title, SLIDE_INC_MARK)){
						$slideContent = str_replace('<ul>', '<ul class="incremental">', $slideContent);
						$slideContent = str_replace('<ol>', '<ol class="incremental">', $slideContent);
						$title = str_replace(SLIDE_INC_MARK, '', $title);
					}
					$fc .= sprintf($s, $title, $slideContent);
				}
			} //<--} else {
		} //<--foreach( $this->mSlides as $slide ){
		
		$output =& $fileParser->parse($this->desc."\n__NOTOC__\n__NOEDITSECTION__", $nt, $options);
		$desc = $output->getText();

		#write to file
		$ce_page_search = array( '[desc]', '[slideContent]', '[slideTitle]', '[slideStyle]' );
		$ce_page_replace= array( $desc, $fc, $this->sTitle, $this->style );
		$fileContent = str_replace($ce_page_search, $ce_page_replace, $ce_slide_tpl);
		$fileContent = $wgContLang->Convert($fileContent);

		$wgOut->disable();
		echo($fileContent);
		exit();
	}

}

function ceFakeSlide(){}

?>