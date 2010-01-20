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
	return array_keys($pages);
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
		if ($row && !$pages[$row->getPrefixedText()])
		{
			$pages[$row->getPrefixedText()] = 1;
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
function wfExportGetTemplates( $inputPages, $pageSet ) {
	return wfExportGetLinks( $inputPages, $pageSet,
		'templatelinks',
	 	array( 'tl_namespace AS namespace', 'tl_title AS title' ),
		array( 'page_id=tl_from' ) );
}

/**
 * Expand a list of pages to include images used in those pages.
 * @param $inputPages array, list of titles to look up
 * @param $pageSet array, associative array indexed by titles for output
 * @return array associative array index by titles
 */
function wfExportGetImages( $inputPages, $pageSet ) {
	return wfExportGetLinks( $inputPages, $pageSet,
		'imagelinks',
		array( NS_FILE . ' AS namespace', 'il_to AS title' ),
		array( 'page_id=il_from' ) );
}

/**
 * Expand a list of pages to include items used in those pages.
 * @private
 */
function wfExportGetLinks( $inputPages, $pageSet, $table, $fields, $join ) {
	$dbr = wfGetDB( DB_SLAVE );
	foreach( $inputPages as $page ) {
		$title = Title::newFromText( $page );
		if( $title ) {
			$pageSet[$title->getPrefixedText()] = true;
			/// @fixme May or may not be more efficient to batch these
			///        by namespace when given multiple input pages.
			$result = $dbr->select(
				array( 'page', $table ),
				$fields,
				array_merge( $join,
					array(
						'page_namespace' => $title->getNamespace(),
						'page_title' => $title->getDBKey() ) ),
				__METHOD__ );
			foreach( $result as $row ) {
				$template = Title::makeTitle( $row->namespace, $row->title );
				$pageSet[$template->getPrefixedText()] = true;
			}
		}
	}
	return $pageSet;
}

/**
 *
 */
function wfSpecialExport( $page = '' ) {
	global $wgOut, $wgRequest, $wgSitename, $wgExportAllowListContributors;
	global $wgExportAllowHistory, $wgExportMaxHistory;

	$curonly = true;
	$doexport = false;
	$errors = array();

	if ($wgRequest->getCheck('addcat'))
	{
		$page = $wgRequest->getText('pages');
		$catname = $wgRequest->getText('catname');
		$modifydate = $wgRequest->getText('modifydate');
		$namespace = $wgRequest->getText('namespace');
		$closure = $wgRequest->getCheck('closure');
		$catpages = wfExportGetPagesFromCategory($catname, $modifydate, $namespace, $closure);
		if ($catpages)
			$page .= "\n" . implode("\n", $catpages);
		if (!$catname && strlen($catname = $wgRequest->getText('catname')))
			$errors[] = array('export-invalid-catname', $catname);
		if ($modifydate)
			$modifydate = wfTimestamp(TS_DB, $modifydate);
		else if ($modifydate = $wgRequest->getText('modifydate'))
			$errors[] = array('export-invalid-modifydate', $modifydate);
		if (!$namespace && strlen($namespace = $wgRequest->getText('namespace')))
			$errors[] = array('export-invalid-namespace', $namespace);
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
		foreach (explode("\n", $page) as $p)
			if ($p !== '' && $p !== null)
				$inputPages[] = Title::newFromText($p)->getPrefixedText();
		$pageSet = array_flip( $inputPages );

		if( $wgRequest->getCheck( 'templates' ) ) {
			$pageSet = wfExportGetTemplates( $inputPages, $pageSet );
		}

		// Enable this when we can do something useful exporting/importing image information. :)
		if( $wgRequest->getCheck( 'images' ) ) {
			$pageSet = wfExportGetImages( $inputPages, $pageSet );
		}

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

	$form .= wfMsgExt( 'export-addpages', 'parse' );
	$form .= '<p>';
	$form .= '<div style="display: inline-block; text-align: right; vertical-align: top">';
	$form .= Xml::inputLabel( wfMsg( 'export-catname' ), 'catname', 'catname', 40, $catname );
	$form .= '<br />' . Xml::checkLabel( wfMsg( 'export-closure' ), 'closure', 'wpExportClosure', $wgRequest->getCheck('closure') ? true : false ) . '</div>&nbsp; ';
	$form .= Xml::inputLabel( wfMsg( 'export-namespace' ), 'namespace', 'namespace', 20, $namespace ) . '&nbsp; ';
	$form .= Xml::inputLabel( wfMsg( 'export-modifydate' ), 'modifydate', 'modifydate', 20, $modifydate ) . '&nbsp; ';
	$form .= Xml::submitButton( wfMsg( 'export-addcat' ), array( 'name' => 'addcat' ) );
	$form .= '</p>';

	$form .= Xml::openElement( 'textarea', array( 'name' => 'pages', 'cols' => 40, 'rows' => 10 ) );
	$form .= htmlspecialchars( $page );
	$form .= Xml::closeElement( 'textarea' );
	$form .= '<br />';

	if( $wgExportAllowHistory )
		$form .= Xml::checkLabel( wfMsg( 'exportcuronly' ), 'curonly', 'curonly', $wgRequest->getCheck('curonly') ? true : false ) . '<br />';
	else
		$wgOut->addHTML( wfMsgExt( 'exportnohistory', 'parse' ) );

	$form .= Xml::checkLabel( wfMsg( 'export-templates' ), 'templates', 'wpExportTemplates', $wgRequest->getCheck('templates') ? true : false ) . '<br />';
	// Enable this when we can do something useful exporting/importing image information. :)
	$form .= Xml::checkLabel( wfMsg( 'export-images' ), 'images', 'wpExportImages', $wgRequest->getCheck('images') ? true : false ) . '<br />';
	$form .= Xml::checkLabel( wfMsg( 'export-download' ), 'wpDownload', 'wpDownload', true ) . '<br />';
	$form .= Xml::checkLabel( wfMsg( 'export-selfcontained' ), 'selfcontained', 'wpSelfContained', $wgRequest->getCheck('selfcontained') ? true : false ) . '<br />';

	$form .= Xml::submitButton( wfMsg( 'export-submit' ), array( 'accesskey' => 's' ) );
	$form .= Xml::closeElement( 'form' );
	$wgOut->addHTML( $form );
}
