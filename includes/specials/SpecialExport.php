<?php
# Copyright (C) 2003-2008 Brion Vibber <brion@pobox.com>
#           (C) 2010-2011 Vitaliy Filippov <vitalif@mail.ru>
# http://www.mediawiki.org/
# http://wiki.4intra.net/MW_Import_Export
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
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html

/**
 * @file
 * @ingroup SpecialPage
 */

class SpecialExport extends SpecialPage {

	private $curonly, $doExport, $linkDepth, $templates;
	private $images;

	public function __construct() {
		parent::__construct( 'Export' );
	}

	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgSitename, $wgExportAllowListContributors;
		global $wgExportAllowHistory, $wgExportMaxHistory, $wgExportMaxLinkDepth;
		global $wgExportFromNamespaces;
		
		$this->setHeaders();
		$this->outputHeader();
		
		// Set some variables
		$this->curonly = true;
		$this->doExport = false;
		$this->templates = $wgRequest->getCheck( 'templates' );
		$this->images = $wgRequest->getCheck( 'images' ); // Doesn't do anything yet
		$this->linkDepth = $this->validateLinkDepth(
			$wgRequest->getIntOrNull( 'link-depth' ) );
		$nsindex = '';
		
		$state = $wgRequest->getValues();
		$state['errors'] = array();
		if ($state['addcat'])
		{
			self::addPagesExec($state);
			$page = $state['pages'];
		}
		else if( $wgRequest->wasPosted() && $par == '' ) {
			$page = $wgRequest->getText( 'pages' );
			$this->curonly = $wgRequest->getCheck( 'curonly' );
			$rawOffset = $wgRequest->getVal( 'offset' );
			if( $rawOffset ) {
				$offset = wfTimestamp( TS_MW, $rawOffset );
			} else {
				$offset = null;
			}
			$limit = $wgRequest->getInt( 'limit' );
			$dir = $wgRequest->getVal( 'dir' );
			$history = array(
				'dir' => 'asc',
				'offset' => false,
				'limit' => $wgExportMaxHistory,
			);
			$historyCheck = $wgRequest->getCheck( 'history' );
			if ( $this->curonly ) {
				$history = WikiExporter::CURRENT;
			} elseif ( !$historyCheck ) {
				if ( $limit > 0 && ($wgExportMaxHistory == 0 || $limit < $wgExportMaxHistory ) ) {
					$history['limit'] = $limit;
				}
				if ( !is_null( $offset ) ) {
					$history['offset'] = $offset;
				}
				if ( strtolower( $dir ) == 'desc' ) {
					$history['dir'] = 'desc';
				}
			}
			
			if( $page != '' ) $this->doExport = true;
		} else {
			// Default to current-only for GET requests
			$page = $wgRequest->getText( 'pages', $par );
			$historyCheck = $wgRequest->getCheck( 'history' );
			if( $historyCheck ) {
				$history = WikiExporter::FULL;
			} else {
				$history = WikiExporter::CURRENT;
			}
			
			if( $page != '' ) $this->doExport = true;
		}
		
		if( !$wgExportAllowHistory ) {
			// Override
			$history = WikiExporter::CURRENT;
		}
		
		$list_authors = $wgRequest->getCheck( 'listauthors' );
		if ( !$this->curonly || !$wgExportAllowListContributors ) $list_authors = false ;
		
		if ( $this->doExport ) {
			$wgOut->disable();
			// Cancel output buffering and gzipping if set
			// This should provide safer streaming for pages with history
			wfResetOutputBuffers();
			header( "Content-type: application/xml; charset=utf-8" );
			if( $wgRequest->getCheck( 'wpDownload' ) ) {
				// Provide a sane filename suggestion
				$filename = urlencode( $wgSitename . '-' . wfTimestampNow() . '.xml' );
				$wgRequest->response()->header( "Content-disposition: attachment;filename={$filename}" );
			}
			$this->doExport( $page, $history, $list_authors );
			return;
		}
		
		$wgOut->addWikiMsg( 'exporttext' );
		
		$form = Xml::openElement( 'form', array( 'method' => 'post',
			'action' => $this->getTitle()->getLocalUrl( 'action=submit' ) ) );
		foreach ( $state['errors'] as $e )
			$form .= wfMsgExt( $e[0], array('parse'), $e[1] );
		
		$form .= self::addPagesForm($state);
		
		$form .= Xml::element( 'textarea', array( 'name' => 'pages', 'cols' => 40, 'rows' => 10 ), $page, false );
		$form .= '<br />';
		
		if( $wgExportAllowHistory ) {
			$form .= Xml::checkLabel( wfMsg( 'exportcuronly' ), 'curonly', 'curonly', $wgRequest->getCheck('curonly') ? true : false ) . '<br />';
		} else {
			$wgOut->addHTML( wfMsgExt( 'exportnohistory', 'parse' ) );
		}
		$form .= Xml::checkLabel( wfMsg( 'export-include-images' ), 'images', 'wpExportImages', $wgRequest->getCheck('images') ? true : false ) . '<br />';
		$form .= Xml::checkLabel( wfMsg( 'export-download' ), 'wpDownload', 'wpDownload', true ) . '<br />';
		$form .= Xml::checkLabel( wfMsg( 'export-selfcontained' ), 'selfcontained', 'wpSelfContained', $wgRequest->getCheck('selfcontained') ? true : false ) . '<br />';
		if( $wgExportMaxLinkDepth || $this->userCanOverrideExportDepth() ) {
			$form .= Xml::inputLabel( wfMsg( 'export-link-depth' ), 'link-depth', 'link-depth', 20, $wgRequest->getVal('link-depth') ) . '<br />';
		}
		
		$form .= Xml::submitButton( wfMsg( 'export-submit' ), array( 'accesskey' => 's' ) );
		$form .= Xml::closeElement( 'form' );
		$wgOut->addHTML( $form );
	}

	private function userCanOverrideExportDepth() {
		global $wgUser;
		
		return $wgUser->isAllowed( 'override-export-depth' );
	}

	/**
	 * Do the actual page exporting
	 * @param string $page User input on what page(s) to export
	 * @param mixed  $history one of the WikiExporter history export constants
	 */
	private function doExport( $page, $history, $list_authors ) {
		global $wgExportMaxHistory;
		global $wgRequest;
		
		// Split up and normalize input
		$pages = array();
		foreach( explode( "\n", $page ) as $pageName )
		{
			$pageName = trim( $pageName );
			$title = Title::newFromText( $pageName );
			if( $title && $title->getInterwiki() == '' && $title->getText() !== '' &&
			    $title->userCanRead() )
			{
				// Only record each page once!
				$pages[ $title->getPrefixedText() ] = $title;
			}
		}
		$pages = array_values( $pages );
		
		/* Ok, let's get to it... */
		if( $history == WikiExporter::CURRENT ) {
			$lb = false;
			$db = wfGetDB( DB_SLAVE );
			$buffer = WikiExporter::BUFFER;
		} else {
			// Use an unbuffered query; histories may be very long!
			$lb = wfGetLBFactory()->newMainLB();
			$db = $lb->getConnection( DB_SLAVE );
			$buffer = WikiExporter::STREAM;
			
			// This might take a while... :D
			wfSuppressWarnings();
			set_time_limit(0);
			wfRestoreWarnings();
		}
		$exporter = new WikiExporter( $db, $history, $buffer );
		$exporter->list_authors = $list_authors;
		$exporter->dumpUploads = $wgRequest->getCheck('images') ? true : false;
		$exporter->selfContained = $wgRequest->getCheck('selfcontained') ? true : false;
		$exporter->openStream();
		foreach( $pages as $title ) {
			$exporter->pageByTitle( $title );
		}
		
		$exporter->closeStream();
		if( $lb ) {
			$lb->closeAll();
		}
	}

	// Execute page selection form, save page list to $state['pages'] and errors to $state['errors']
	static function addPagesExec( &$state )
	{
		// Split up and normalize input
		$pageSet = array();
		foreach( explode( "\n", $state['pages'] ) as $pageName )
		{
			$pageName = trim( $pageName );
			$title = Title::newFromText( $pageName );
			if( $title && $title->getInterwiki() == '' && $title->getText() !== '' )
			{
				// Only record each page once!
				$pageSet[ $title->getPrefixedText() ] = $title;
			}
		}
		// Add pages from requested category
		$catname = $state['catname'];
		$modifydate = $state['modifydate'];
		$namespace = $state['namespace'];
		$catpages = self::getPagesFromCategory( $catname, $modifydate, $namespace, $state['closure'] );
		if ( $catpages )
			foreach ( $catpages as $title )
				$pageSet[ $title->getPrefixedText() ] = $title;
		// Look up any linked pages if asked...
		$t = $state[ 'templates' ] ? 1 : 0;
		$p = $state[ 'pagelinks' ] ? 1 : 0;
		$i = $state[ 'images' ] ? 1 : 0;
		$step = 0;
		do
		{
			// Loop as there may be more than one closure type
			$added = 0;
			if( $t ) $added += self::getTemplates( $pageSet );
			if( $p ) $added += self::getPagelinks( $pageSet );
			if( $i ) $added += self::getImages( $pageSet );
			$step++;
		} while( $t+$p+$i > 1 && $added > 0 && ( !$this->linkDepth || $step < $this->linkDepth ) );
		// Filter user-readable pages (also MW Bug 8824)
		foreach ( $pageSet as $key => $title )
			if ( !$title->userCanRead() )
				unset( $pageSet[ $key ] );
		// Filter pages from requested NOT-category
		$notcategory = $state['notcategory'];
		$nc = NULL;
		$notlist = self::getPagesFromCategory( $notcategory, $nc, $nc, $nc );
		if ( $notlist )
			foreach ( $notlist as $title )
				unset( $pageSet[ $title->getPrefixedText() ] );
		// Save resulting page list
		$pages = array_keys( $pageSet );
		sort( $pages );
		$state['pages'] = implode( "\n", $pages );
		// Save errors
		$state['errors'] = array();
		if ( !$catname && strlen( $state['catname'] ) )
			$state['errors'][] = array( 'export-invalid-catname', $state['catname'] );
		if ( !$notcategory && strlen( $state['notcategory'] ) )
			$state['errors'][] = array( 'export-invalid-notcategory', $state['notcategory'] );
		if ( $modifydate )
			$state['modifydate'] = wfTimestamp(TS_DB, $modifydate);
		elseif ( $state['modifydate'] )
			$state['errors'][] = array( 'export-invalid-modifydate', $state['modifydate'] );
		if ( !$namespace && strlen( $state['namespace'] ) )
			$state['errors'][] = array( 'export-invalid-namespace', $state['namespace'] );
	}

	// Display page selection form, enclosed into a <fieldset>
	static function addPagesForm( $state )
	{
		$form .= '<fieldset class="addpages">';
		$form .= '<legend>' . wfMsgExt( 'export-addpages', 'parse' ) . '</legend>';
		$textboxes = array(
			'catname'     => 20,
			'namespace'   => 20,
			'modifydate'  => 18,
			'notcategory' => 20,
		);
		// Textboxes:
		foreach ( $textboxes as $k => $size )
			$form .= '<div class="ap_'.$k.'">' .
				Xml::inputLabel( wfMsg( "export-$k" ), $k, "ap-$k", $size, $state[ $k ] ) . '</div>';
		// Checkboxes:
		foreach ( array( 'closure', 'templates', 'images', 'pagelinks' ) as $k )
		{
			$form .= '<div class="ap_'.$k.'">' . Xml::checkLabel(
				wfMsg( "export-$k" ), $k, "ap-$k", $state[ $k ] && true,
				array( 'style' => 'vertical-align: middle' )
			) . '</div>';
		}
		// Submit button:
		$form .= '<div class="ap_submit">' . Xml::submitButton( wfMsg( 'export-addcat' ), array( 'name' => 'addcat' ) ) . '</div>';
		$form .= '</fieldset>';
		return $form;
	}

	// Get pages that meet following conditions:
	// - from category $catname, and from its subcategories if $closure is true
	// - modified after $modifydate
	// - from namespace $namespace
	// Conditions are optional, but an empty list is returned if no rule is specified
	// You can still request full page list explicitly with $modifydate = '1970-01-01 00:00:00'
	static function getPagesFromCategory( &$catname, &$modifydate, &$namespace, $closure )
	{
		if ( !strlen($catname) || !( $catname = Title::makeTitleSafe( NS_CATEGORY, $catname ) ) )
			$catname = NULL;
		else
			$catname = $catname->getDbKey();
		if (!strlen( $modifydate ) || !( $modifydate = wfTimestampOrNull( TS_MW, $modifydate ) ) )
			$modifydate = NULL;
		if (!strlen( $namespace ) || !( $namespace = Title::newFromText( "$namespace:Dummy", NS_MAIN ) ) )
			$namespace = NULL;
		else
			$namespace = $namespace->getNamespace();
		// Prevent full listings (you can still request it with modifydate=1970-01-01 00:00:00)
		if ( $catname === NULL && $modifydate === NULL && $namespace === NULL )
			return array();
		$pages = array();
		self::rgetPagesFromCategory( $catname, $modifydate, $namespace, $closure, $pages );
		return array_values( $pages );
	}

	// Recursive function which gets pages from category and/or namespace
	// and/or modified after the specified date
	static function rgetPagesFromCategory( $catname, $modifydate, $namespace, $closure, &$pages )
	{
		global $wgContLang;
		$dbr = wfGetDB( DB_SLAVE );
		$from = array( 'page' );
		$where = array();
		
		if ( !is_null( $catname ) )
		{
			$from[] = 'categorylinks';
			$where[] = 'cl_from=page_id';
			$where['cl_to'] = $catname;
		}
		
		if ( !is_null( $modifydate ) )
			$where[] = "page_touched>$modifydate";
		
		if ( !is_null( $namespace ) )
			$where['page_namespace'] = $namespace;
		
		$res = $dbr->select( $from, array( 'page_namespace', 'page_title' ), $where, __METHOD__ );
		while ( $row = $dbr->fetchRow( $res ) )
		{
			$row = Title::makeTitleSafe( $row['page_namespace'], $row['page_title'] );
			if ( $row && !$pages[ $row->getArticleId() ] )
			{
				$pages[ $row->getArticleId() ] = $row;
				if ( $closure && $row->getNamespace() == NS_CATEGORY )
					self::rgetPagesFromCategory( $row->getDbKey(), $modifydate, $namespace, $closure, $pages );
			}
		}
		
		return $pages;
	}

	/**
	 * Validate link depth setting, if available.
	 */
	private function validateLinkDepth( $depth ) {
		global $wgExportMaxLinkDepth, $wgExportMaxLinkDepthLimit;
		if( $depth <= 0 ) {
			return 0;
		}
		if ( !$this->userCanOverrideExportDepth() ) {
			if( $depth > $wgExportMaxLinkDepth ) {
				return $wgExportMaxLinkDepth;
			}
		}
		return $depth;
	}

	/**
	 * Expand a list of pages to include templates used in those pages.
	 * @param $inputPages array, list of titles to look up
	 * @param $pageSet array, associative array indexed by titles for output
	 * @return array associative array index by titles
	 */
	private static function getTemplates( &$pageSet )
	{
		return self::getLinks(
			$pageSet,
			'templatelinks',
			array( 'tl_namespace AS namespace', 'tl_title AS title' ),
			array( 'page_id=tl_from' ) );
	}

	/** Expand a list of pages to include pages linked to from that page. */
	private static function getPageLinks( &$pageSet )
	{
		return self::getLinks(
			$pageSet,
			'pagelinks',
			array( 'pl_namespace AS namespace', 'pl_title AS title' ),
			array( 'page_id=pl_from' )
		);
	}

	/**
	 * Expand a list of pages to include images used in those pages.
	 * @param $inputPages array, list of titles to look up
	 * @param $pageSet array, associative array indexed by titles for output
	 * @return array associative array index by titles
	 */
	private static function getImages( &$pageSet )
	{
		return self::getLinks(
			$pageSet,
			'imagelinks',
			array( NS_FILE . ' AS namespace', 'il_to AS title' ),
			array( 'page_id=il_from' ) );
	}

	/**
	 * Expand a list of pages to include items used in those pages.
	 * @private
	 */
	private static function getLinks( &$pageSet, $table, $fields, $join )
	{
		$dbr = wfGetDB( DB_SLAVE );
		$byns = array();
		foreach( $pageSet as $title )
			$byns[ $title->getNamespace() ][] = $title->getDBkey();
		$added = 0;
		foreach( $byns as $ns => $titles )
		{
			$result = $dbr->select(
				array( 'page', $table ),
				$fields,
				array_merge( $join, array(
					'page_namespace' => $ns,
					'page_title' => $titles ) ),
				__METHOD__ );
			foreach( $result as $row )
			{
				$add = Title::makeTitle( $row->namespace, $row->title );
				if( $add && !$pageSet[ $add->getPrefixedText() ] )
				{
					$pageSet[ $add->getPrefixedText() ] = $add;
					$added++;
				}
			}
		}
		return $added;
	}
}
