<?php
# Copyright (C) 2003-2008 Brion Vibber <brion@pobox.com>
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
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html
/**
 * @file
 * @ingroup SpecialPage
 */

function wfExportGetPagesFromCategory(&$catname, &$modifydate, &$namespace, $closure)
{
	if (!strlen($catname) || !($catname = Title::makeTitleSafe(NS_CATEGORY, $catname)))
		$catname = NULL;
	else
		$catname = $catname->getDbKey();
	if (!strlen($modifydate) || !($modifydate = wfTimestampOrNull(TS_MW, $modifydate)))
		$modifydate = NULL;
	if (!strlen($namespace) || !($namespace = Title::newFromText("$namespace:A", NS_MAIN)))
		$namespace = NULL;
	else
		$namespace = $namespace->getNamespace();
	$pages = array();
	wfExportGetPagesFromCategoryR($catname, $modifydate, $namespace, $closure, $pages);
	return array_values($pages);
}

function wfExportGetPagesFromCategoryR($catname, $modifydate, $namespace, $closure, &$pages)
{
	global $wgContLang;

	$dbr = wfGetDB(DB_SLAVE);
	$from = array('page');
	$where = array();

	if (!is_null($catname))
	{
		$from[] = 'categorylinks';
		$where[] = 'cl_from=page_id';
		$where['cl_to'] = $catname;
	}

	if (!is_null($modifydate))
		$where[] = "page_touched>$modifydate";

	if (!is_null($namespace))
		$where['page_namespace'] = $namespace;

	$res = $dbr->select($from, array('page_namespace', 'page_title'), $where, __METHOD__);
	while ($row = $dbr->fetchRow($res))
	{
		$row = Title::makeTitleSafe($row['page_namespace'], $row['page_title']);
		if ($row && !$pages[$row->getArticleId()])
		{
			$pages[$row->getArticleId()] = $row;
			if ($closure && $row->getNamespace() == NS_CATEGORY)
				wfExportGetPagesFromCategoryR($row->getDbKey(), $modifydate, $namespace, $closure, $pages);
		}
	}
	$dbr->freeResult($res);

	return $pages;
}

/**
 * Expand a list of pages to include templates used in those pages.
 * @param $inputPages array, list of titles to look up
 * @param $pageSet array, associative array indexed by titles for output
 * @return array associative array index by titles
 */
function wfExportGetTemplates( &$inputPages, &$pageSet ) {
	return wfExportGetLinks( $inputPages, $pageSet,
		'templatelinks',
	 	array( 'tl_namespace AS namespace', 'tl_title AS title' ),
		array( 'page_id=tl_from' ) );
}

/**
 * Expand a list of pages to include linked pages.
 * @param $inputPages array, list of titles to look up
 * @param $pageSet array, associative array indexed by titles for output
 * @return array associative array index by titles
 */
function wfExportGetPagelinks( &$inputPages, &$pageSet ) {
	return wfExportGetLinks( $inputPages, $pageSet,
		'pagelinks',
		array( 'pl_namespace AS namespace', 'pl_title AS title' ),
		array( 'page_id=pl_from' ) );
}

/**
 * Expand a list of pages to include images used in those pages.
 * @param $inputPages array, list of titles to look up
 * @param $pageSet array, associative array indexed by titles for output
 * @return array associative array index by titles
 */
function wfExportGetImages( &$inputPages, &$pageSet ) {
	return wfExportGetLinks( $inputPages, $pageSet,
		'imagelinks',
		array( NS_FILE . ' AS namespace', 'il_to AS title' ),
		array( 'page_id=il_from' ) );
}

/**
 * Expand a list of pages to include items used in those pages.
 * @private
 */
function wfExportGetLinks( &$inputPages, &$pageSet, $table, $fields, $join )
{
	$dbr = wfGetDB( DB_SLAVE );
	$byns = array();
	foreach( $inputPages as $title )
		$byns[$title->getNamespace()][] = $title->getDBkey();
	$added = 0;
	foreach( $byns as $ns => $titles )
	{
		$result = $dbr->select(
			array( 'page', $table ),
			$fields,
			array_merge( $join,
				array(
					'page_namespace' => $ns,
					'page_title' => $titles ) ),
			__METHOD__ );
		foreach( $result as $row )
		{
			$add = Title::makeTitle( $row->namespace, $row->title );
			if( !$pageSet[$add->getPrefixedText()] )
			{
				$pageSet[$add->getPrefixedText()] = true;
				$inputPages[] = $add;
				$added++;
			}
		}
	}
	return $added;
}

function wfExportAddPagesExec(&$state)
{
	$catname = $state['catname'];
	$modifydate = $state['modifydate'];
	$namespace = $state['namespace'];
	$closure = $state['closure'];
	$catpages = wfExportGetPagesFromCategory($catname, $modifydate, $namespace, $closure);
	if ($catpages)
	{
		foreach ($catpages as $title)
		{
/*op-patch|TS|2010-04-26|HaloACL|SafeTitle|start*/
			if (!method_exists($title, 'userCanReadEx') || $title->userCanReadEx())
/*op-patch|TS|2010-04-26|end*/
				$state['pages'] .= "\n" . $title->getPrefixedText();
		}
	}
	if (!$catname && strlen($state['catname']))
		$state['errors'][] = array('export-invalid-catname', $state['catname']);
	if ($modifydate)
		$state['modifydate'] = wfTimestamp(TS_DB, $modifydate);
	else if ($state['modifydate'])
		$state['errors'][] = array('export-invalid-modifydate', $state['modifydate']);
	if (!$namespace && strlen($state['namespace']))
		$state['errors'][] = array('export-invalid-namespace', $state['namespace']);
}

function wfExportAddPagesForm($state)
{
	$form .= '<fieldset class="addpages">';
	$form .= '<legend>' . wfMsgExt('export-addpages', 'parse') . '</legend>';// style="display: inline-block; text-align: right; vertical-align: top">
	$form .= '<div class="ap_catname">' . Xml::inputLabel(wfMsg('export-catname'), 'catname', 'catname', 40, $state['catname']) .
	         '<br />' . Xml::checkLabel(wfMsg('export-closure'), 'closure', 'wpExportClosure', $state['closure'] ? true : false) . '</div>';
	$form .= '<div class="ap_namespace">' . Xml::inputLabel(wfMsg('export-namespace'), 'namespace', 'namespace', 20, $state['namespace']) . '</div>';
	$form .= '<div class="ap_modifydate">' . Xml::inputLabel(wfMsg('export-modifydate'), 'modifydate', 'modifydate', 20, $state['modifydate']) . '</div>';
	$form .= '<div class="ap_submit">' . Xml::submitButton(wfMsg('export-addcat'), array('name' => 'addcat')) . '</div>';
	$form .= '</fieldset>';
	return $form;
}

/**
 * Special page itself
 */
function wfSpecialExport( $page = '' ) {
	global $wgOut, $wgRequest, $wgSitename, $wgExportAllowListContributors;
	global $wgExportAllowHistory, $wgExportMaxHistory;

	$curonly = true;
	$doexport = false;
	$errors = array();

	# FIXME OO approach (as in trunk) would probably be better here,
	#       but I'm too lazy to backport it into 1.14.
	$state = $_REQUEST;
	if ($state['addcat'])
	{
		wfExportAddPagesExec($state);
		$page = $state['pages'];
	}
	else if( $wgRequest->wasPosted() && $page == '' ) {
		$page = $wgRequest->getText( 'pages' );
		$curonly = $wgRequest->getCheck( 'curonly' );
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
		if ( $curonly ) {
			$history = WikiExporter::CURRENT;
		} elseif ( !$historyCheck ) {
			if ( $limit > 0 && $limit < $wgExportMaxHistory ) {
				$history['limit'] = $limit;
			}
			if ( !is_null( $offset ) ) {
				$history['offset'] = $offset;
			}
			if ( strtolower( $dir ) == 'desc' ) {
				$history['dir'] = 'desc';
			}
		}

		if( $page != '' ) $doexport = true;
	} else {
		// Default to current-only for GET requests
		$page = $wgRequest->getText( 'pages', $page );
		$historyCheck = $wgRequest->getCheck( 'history' );
		if( $historyCheck ) {
			$history = WikiExporter::FULL;
		} else {
			$history = WikiExporter::CURRENT;
		}

		if( $page != '' ) $doexport = true;
	}

	if( !$wgExportAllowHistory ) {
		// Override
		$history = WikiExporter::CURRENT;
	}

	$list_authors = $wgRequest->getCheck( 'listauthors' );
	if ( !$curonly || !$wgExportAllowListContributors ) $list_authors = false ;

	if ( $doexport ) {
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

		/* Split up the input and look up linked pages */
		$inputPages = array();
		$pageSet = array();
		foreach (explode("\n", $page) as $p)
		{
			if ($p !== '' && $p !== null && ($p = Title::newFromText($p)))
			{
				$inputPages[] = $p;
				$pageSet[$p->getPrefixedText()] = true;
			}
		}

		$t = $wgRequest->getCheck( 'templates' ) ? 1 : 0;
		$p = $wgRequest->getCheck( 'pagelinks' ) ? 1 : 0;
		$i = $wgRequest->getCheck( 'images' ) ? 1 : 0;
		do
		{
			$added = 0;
			if( $t ) $added += wfExportGetTemplates( $inputPages, $pageSet );
			if( $p ) $added += wfExportGetPagelinks( $inputPages, $pageSet );
			if( $i ) $added += wfExportGetImages( $inputPages, $pageSet );
		} while( $t+$p+$i > 1 && $added > 0 );

/*op-patch|TS|2010-04-26|HaloACL|SafeTitle|start*/
		foreach ($inputPages as $title)
			if (method_exists($title, 'userCanReadEx') && !$title->userCanReadEx())
				unset($pageSet[$title->getPrefixedText()]);
/*op-patch|TS|2010-04-26|end*/

		$pages = array_keys( $pageSet );

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
		$exporter->list_authors = $list_authors ;
		$exporter->dumpUploads = $wgRequest->getCheck('images') ? true : false;
		$exporter->selfContained = $wgRequest->getCheck('selfcontained') ? true : false;
		$exporter->openStream();

		foreach( $pages as $page ) {
			/*
			if( $wgExportMaxHistory && !$curonly ) {
				$title = Title::newFromText( $page );
				if( $title ) {
					$count = Revision::countByTitle( $db, $title );
					if( $count > $wgExportMaxHistory ) {
						wfDebug( __FUNCTION__ .
							": Skipped $page, $count revisions too big\n" );
						continue;
					}
				}
			}*/

			#Bug 8824: Only export pages the user can read
			$title = Title::newFromText( $page );
			if( is_null( $title ) ) continue; #TODO: perhaps output an <error> tag or something.
			if( !$title->userCanRead() ) continue; #TODO: perhaps output an <error> tag or something.

			$exporter->pageByTitle( $title );
		}

		$exporter->closeStream();
		if( $lb ) {
			$lb->closeAll();
		}
		return;
	}

	$self = SpecialPage::getTitleFor( 'Export' );
	$wgOut->addHTML( wfMsgExt( 'exporttext', 'parse' ) );

	$form = Xml::openElement( 'form', array( 'method' => 'post',
		'action' => $self->getLocalUrl( 'action=submit' ) ) );

	foreach ($errors as $e)
		$form .= wfMsgExt($e[0], array('parse'), $e[1]);

	$form .= wfExportAddPagesForm($state);

	$form .= Xml::openElement( 'textarea', array( 'name' => 'pages', 'cols' => 40, 'rows' => 10 ) );
	$form .= htmlspecialchars( $page );
	$form .= Xml::closeElement( 'textarea' );
	$form .= '<br />';

	if( $wgExportAllowHistory )
		$form .= Xml::checkLabel( wfMsg( 'exportcuronly' ), 'curonly', 'curonly', $wgRequest->getCheck('curonly') ? true : false ) . '<br />';
	else
		$wgOut->addHTML( wfMsgExt( 'exportnohistory', 'parse' ) );

	$form .= Xml::checkLabel( wfMsg( 'export-templates' ), 'templates', 'wpExportTemplates', $wgRequest->getCheck('templates') ? true : false ) . '<br />';
	$form .= Xml::checkLabel( wfMsg( 'export-pagelinks' ), 'pagelinks', 'wpExportPagelinks', $wgRequest->getCheck('pagelinks') ? true : false ) . '<br />';
	// Enable this when we can do something useful exporting/importing image information. :)
	$form .= Xml::checkLabel( wfMsg( 'export-images' ), 'images', 'wpExportImages', $wgRequest->getCheck('images') ? true : false ) . '<br />';
	$form .= Xml::checkLabel( wfMsg( 'export-download' ), 'wpDownload', 'wpDownload', true ) . '<br />';
	$form .= Xml::checkLabel( wfMsg( 'export-selfcontained' ), 'selfcontained', 'wpSelfContained', $wgRequest->getCheck('selfcontained') ? true : false ) . '<br />';

	$form .= Xml::submitButton( wfMsg( 'export-submit' ), array( 'accesskey' => 's' ) );
	$form .= Xml::closeElement( 'form' );
	$wgOut->addHTML( $form );
}
