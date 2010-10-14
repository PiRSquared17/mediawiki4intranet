<?php
/**
* Multi-Category Search 1.5
* This MediaWiki extension represents a [[Special:MultiCategorySearch|special page]],
* 	that allows to find pages, included in several specified categories at once.
* File with extension main source code.
* Requires MediaWiki 1.8 or higher and MySQL 4.1 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:Multi-Category_Search
*
* Copyright (c) Moscow, 2008-2010, Iaroslav Vassiliev  <codedriller@gmail.com>
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/


class MultiCategorySearch extends IncludableSpecialPage
{
	// Configuration settings

	/** Number of categories to search. */
	var $inCategoriesNumber = 5;
	/** Number of categories to exclude from search. */
	var $exCategoriesNumber = 3;
	/** Method of passing parameters. Change it to 'get', if you need that method. */
	var $paramsPassMethod = 'post';
	/**
	* EditTools insertion.
	* Change it to true to insert EditTools (http://www.mediawiki.org/wiki/MediaWiki:Edittools) at the bottom of the form.
	* EditTools require CharInsert extension (http://www.mediawiki.org/wiki/Extension:CharInsert) to be installed.
	* EditTools also require AJAX to be enabled (global $wgUseAjax variable must be set to true in LocalSettings.php).
	*/
	var $insertEditTools = false;
	/**
	* Drop-down lists insertion. Allows to use custom drop-down lists for category selection.
	* Values for drop-down lists must be manually set in the body of showDropdownLists() function in the end of this script.
	*/
	var $useDropdownLists = false;

	// End of configuration settings

	var $inCategories = array();
	var $exCategories = array();

	static private $memcached = NULL;
	static function getMCache() {
		global $wgMemCachedServers;
		if( self::$memcached == NULL ) {
			self::$memcached = new Memcache;
			self::$memcached->connect( $wgMemCachedServers[0] )
			or die ( "The memcached server" );
		}
		return self::$memcached;
	}

	function __construct( $name = 'MultiCategorySearch' ) {
		global $wgRequest;

		parent::__construct( $name );
		list( $this->limit, $this->offset ) = $wgRequest->getLimitOffset( 100, 'searchlimit' );
	}

	function MultiCategorySearch() {
		global $wgRequest;

		SpecialPage::SpecialPage( 'MultiCategorySearch' );
		list( $this->limit, $this->offset ) = $wgRequest->getLimitOffset( 100, 'searchlimit' );
	}

	function execute( $par ) {
		global $wgRequest, $wgOut, $wgVersion;

		if( version_compare( $wgVersion, '1.8', '<' ) === true ) {
			$wgOut->showErrorPage( "Error: Upgrade required", "The MultiCategorySearch extension " .
				"can't work on MediaWiki version older than 1.8. Please, upgrade." );
			return;
		}

		$gotres = false;
		if( strlen($par) > 0 ) {
			$par = preg_replace( '/\/(..)clude=/', '|\1clude=', $par );
			$bits = explode( '|', trim( $par ) );
			$in = 1;
			$ex = 1;
			// clear out old parameters...wonkified behavior results if not
			$this->inCategories = array();
			$this->exCategories = array();
			foreach ( $bits as $bit ) {
				$bit = trim( $bit );
				$type = substr( $bit, 0, 8);
				if ( 'include=' == $type ) {
					$this->inCategories[$in] = substr( $bit, 8 );
					$in++;
				} else if ( 'exclude=' == $type ) {
					$this->exCategories[$ex] = substr( $bit, 8 );
					$ex++;
				}
			}
		} else if( !is_null( $wgRequest->getVal( 'wpSubmitSearchParams' ) ) ||
			stripos( $wgRequest->getRequestURL(), 'wpInCategory' ) !== false ||
			stripos( $wgRequest->getRequestURL(), 'wpExCategory' ) !== false) {
				for( $i = 1; $i <= $this->inCategoriesNumber; $i++ )
					$this->inCategories[$i] = $wgRequest->getVal( 'wpInCategory' . $i );
				for( $i = 1; $i <= $this->exCategoriesNumber; $i++ )
					$this->exCategories[$i] = $wgRequest->getVal( 'wpExCategory' . $i );
		}
		$this->showResults();
		if ( !$this->including() ) $this->showForm();
	}

	function showResults() {
		global $wgRequest, $wgOut, $wgDBtype, $wgDBprefix;
		global $wgMultiCatSearchMemCachePrefix, $wgMultiCatSearchMemCacheTimeout;

		if( !isset( $wgMultiCatSearchMemCachePrefix ) ) {
			$wgMultiCatSearchMemCachePrefix = 'mcs:';
		}
		if( !isset( $wgMultiCatSearchMemCacheTimeout ) ) {
			$wgMultiCatSearchMemCacheTimeout = 900;
		}
		$memcache_loaded = extension_loaded( 'memcache' );
		// make sure included saves are differentiated from non-included saves
		if( $this->including() ) {
			$wgMultiCatSearchMemCachePrefix .= 'including:';
		} else {
			$wgMultiCatSearchMemCachePrefix .= $this->limit . ':' . $this->offset . ':';
		}

		wfProfileIn( 'MultiCategorySearch::showResults' );

		$dbr =& wfGetDB( DB_SLAVE );
/*
		if( $wgDBtype != 'mysql' ||
			version_compare( $dbr->getServerVersion(), '4.1', '<' ) === true ) {
				$wgOut->showErrorPage( 'Error: Upgrade Required', 'The Multi-Category Search ' .
					'extension requires MySQL database engine 4.1 or higher. Please, upgrade.' );
			return;
		}
*/
		$inCategoriesStr = '';
		$inCategoriesCount = 0;
		for( $i = 1; $i <= $this->inCategoriesNumber; $i++ ) {
			if( array_key_exists( $i, $this->inCategories ) &&
				$this->inCategories[$i] != null && $this->inCategories[$i] != '' ) {
					$inCategoriesStr .=
						$dbr->addQuotes(
							str_replace( ' ', '_', ucfirst( $this->inCategories[$i] ) ) ) . ',';
					$inCategoriesCount++;
			}
		}
		if( strlen( $inCategoriesStr ) > 0 )
			$inCategoriesStr = substr( $inCategoriesStr, 0, -1 );

		$exCategoriesStr = '';
		$exCategoriesCount = 0;
		for( $i = 1; $i <= $this->exCategoriesNumber; $i++ ) {
			if( array_key_exists( $i, $this->exCategories ) &&
				$this->exCategories[$i] != null && $this->exCategories[$i] != '' ) {
					$exCategoriesStr .=
						$dbr->addQuotes(
							str_replace( ' ', '_', ucfirst( $this->exCategories[$i] ) ) ) . ',';
					$exCategoriesCount++;
			}
		}

		if( $inCategoriesCount == 0 && $exCategoriesCount == 0 ) {
			$wgOut->addHTML( '<h3>' . wfMsg( 'multicatsearch_no_params' ) . '</h3>' );
			return;
		}

		if( $exCategoriesCount > 0 ) {
			$exCategoriesStr = substr( $exCategoriesStr, 0, -1 );
			$exSqlQueryStr = "AND cl_from NOT IN " .
				"(SELECT cl_from " .
				"FROM {$wgDBprefix}categorylinks " .
				"WHERE cl_to IN({$exCategoriesStr}))";
		}
		else
			$exSqlQueryStr = '';

		$pageTableName = $dbr->tableName( 'page' );
		$catlinksTableName = $dbr->tableName( 'categorylinks' );

		if( $inCategoriesCount > 0 ) {
			$sqlQueryStr =
				"SELECT {$pageTableName}.page_namespace AS ns, " .
					"{$pageTableName}.page_title AS title " .
				"FROM {$pageTableName}, " .
					"(SELECT cl_from, COUNT(*) AS match_count " .
					"FROM {$catlinksTableName} " .
					"WHERE cl_to IN({$inCategoriesStr}) {$exSqlQueryStr} " .
					"GROUP BY cl_from) AS matches " .
				"WHERE matches.match_count = {$inCategoriesCount} " .
					"AND {$pageTableName}.page_id = matches.cl_from " .
				"ORDER BY {$pageTableName}.page_namespace DESC, {$pageTableName}.page_title";
		}
		else {
			$sqlQueryStr =
				"SELECT {$pageTableName}.page_namespace AS ns, " .
					"{$pageTableName}.page_title AS title " .
				"FROM {$pageTableName}, " .
					"(SELECT cl_from " .
					"FROM {$catlinksTableName} " .
					"WHERE cl_to IN({$exCategoriesStr}) " .
					"GROUP BY cl_from) AS matches " .
				"WHERE {$pageTableName}.page_id NOT IN(matches.cl_from) " .
					"AND {$pageTableName}.page_namespace <> 8 " .	// exclude MediaWiki namespace
				"ORDER BY {$pageTableName}.page_namespace DESC, {$pageTableName}.page_title";
		}

		// check the cache and query the database if necessary
		if( $memcache_loaded == 0 || !( $htresults =
			MultiCategorySearch::getMCache()->get( $wgMultiCatSearchMemCachePrefix . base64_encode(
			'in:' . $inCategoriesStr . ':ex:' . $exCategoriesStr ) ) ) ) {
				$res = $dbr->query( $sqlQueryStr, 'MultiCategorySearch::showResults', false );
				$htresults = "";
				if( $dbr->numRows($res) == 0 ) {
					$htresults .= '<h3>' . wfMsg( 'multicatsearch_no_result' ) . '</h3>';
					$wgOut->addHTML($htresults);
					if( $memcache_loaded == 1 ) {
						MultiCategorySearch::getMCache()->set(
							$wgMultiCatSearchMemCachePrefix . base64_encode(
								'in:' . $inCategoriesStr . ':ex:' . $exCategoriesStr ),
							$htresults, false, $wgMultiCatSearchMemCacheTimeout);
					}
					return;
			}

			if ( !$this->including() ) {
				if( $dbr->numRows( $res ) >= $this->limit ) {
					$reportStr = wfShowingResults( $this->offset, $this->limit );
				} else {
					$reportStr = wfShowingResultsNum( $this->offset, $this->limit, $dbr->numRows( $res ) );
				}
				$htresults .= "<p>{$reportStr}</p>\n";
			}
	
			$queryStr = '';
			foreach( $wgRequest->getValues() as $requestKey => $requestVal ) {
				if( $requestVal != '' && $requestVal != null && 
					(stripos( $requestKey, 'wpInCategory' ) !== false ||
					stripos( $requestKey, 'wpExCategory' ) !== false) )
						$queryStr .= '&' . $requestKey . '=' . $requestVal;
			}
			if( $dbr->numRows($res) >= $this->limit && !$this->including() ) {
				$jumpLinks = wfViewPrevNext( $this->offset, $this->limit, 'Special:MultiCategorySearch',
					$queryStr, ($dbr->numRows($res) <= $this->offset + $this->limit) ? true : false );
				$htresults .= "<p>{$jumpLinks}</p>\n";
			}
	
			$i = 0;
			$j = 0;
			$catView = new CategoryViewer( Title::makeTitle( '-1', wfMsg( 'multicategorysearch' ) ) );
			while( $row = $dbr->fetchObject( $res ) ) {
				if( $i++ < $this->offset && !$this->including() )
					continue;
				if( $j++ == $this->limit && !$this->including() )
					break;
				$titleObj = Title::makeTitle( $row->ns, $row->title );
				$startChar = $titleObj->getText();
				$catView->AddPage( $titleObj, $startChar, 10000 );
			}
			$htresults .= $catView->formatList( $catView->articles, $catView->articles_start_char );
	
			if( $dbr->numRows( $res ) >= $this->limit && !$this->including() ) {
				$htresults .= "<br />{$jumpLinks}\n";
			}
	
			$dbr->freeResult( $res );

			if( $memcache_loaded == 1 ) {
				MultiCategorySearch::getMCache()->set(
					$wgMultiCatSearchMemCachePrefix . base64_encode(
						'in:' . $inCategoriesStr . ':ex:' . $exCategoriesStr ),
					$htresults, false, $wgMultiCatSearchMemCacheTimeout );
			}
		}

		$wgOut->addHTML( $htresults . '<hr />' );

		wfProfileOut( 'MultiCategorySearch::showResults' );
	}

	function showForm() {
		global $wgOut, $wgUser, $wgRequest;
		global $wgScriptPath, $wgUseAjax, $wgJsMimeType;

		$wgOut->setPagetitle( wfMsg( 'multicategorysearch' ) );
		$titleObj = Title::makeTitle( NS_SPECIAL, 'MultiCategorySearch' );
		$action = $titleObj->escapeLocalURL( '' );
		$token = htmlspecialchars( $wgUser->editToken() );

		$msgComment = wfMsgHtml( 'multicatsearch_comment' );
		$msgInCategories = wfMsgHtml( 'multicatsearch_include' );
		$msgExCategories = wfMsgHtml( 'multicatsearch_exclude' );
		$msgSubmitButton = wfMsgHtml( 'multicatsearch_submit_button' );

		$dropdownLists = $this->showDropdownLists();
		$wgOut->addWikiText( $msgComment );
		$wgOut->addHTML("
	<form id=\"MultiCategorySearch\" method=\"{$this->paramsPassMethod}\" action=\"{$action}\">
	{$dropdownLists}
	<table border=\"0\">
		<tr>
			<th align=\"left\" style=\"padding-right: 2em\">{$msgInCategories}</th>
			<th align=\"left\">{$msgExCategories}</th>
		</tr>");

		$rows = array();
		for ($k = 0, $i = substr_count( $dropdownLists, '<select' ) + 1;
			$i <= $this->inCategoriesNumber; $i++, $k++)
		{
			$categoryTitle = $this->inCategories[$i];
			$rows[$k][0] = Xml::input("wpInCategory$i", "40", $categoryTitle, array('tabindex' => $i, 'style' => 'width: 99%'));
		}
		for ($i = 1; $i <= $this->exCategoriesNumber; $i++)
		{
			$j = $this->inCategoriesNumber + $i;
			$categoryTitle = $this->exCategories[$i];
			$rows[$i-1][1] = Xml::input("wpExCategory$i", "40", $categoryTitle, array('tabindex' => $j, 'style' => 'width: 99%'));
		}

		for ($i = 0; $i < count($rows); $i++)
			$wgOut->addHTML("<tr><td>".implode("</td><td>", $rows[$i])."</td></tr>");

		$j = $this->inCategoriesNumber + $this->exCategoriesNumber + 1;
		$wgOut->addHTML("
		<tr>
			<td colspan=\"2\" style=\"padding-top: 1em\" align=\"center\">
				<input tabindex=\"{$j}\" type=\"submit\" name=\"wpSubmitSearchParams\" " .
					"value=\"{$msgSubmitButton}\" />
			</td>
		</tr>
	</table>
	</form>\n");

		if( $this->insertEditTools == true && $wgUseAjax == true &&
			function_exists( 'charInsert' ) ) {
			$wgOut->addHtml( '<div class="mw-editTools">' );
			$wgOut->addWikiText( wfMsg('edittools') );
			$wgOut->addHtml( '</div>' );
		}
	}

	// This function gets a list of sub-categories in the specified category
	function getSubCategories( $categoryTitle, $subCategoriesLimit = 100 ) {
		global $wgDBprefix;

		$dbr =& wfGetDB( DB_SLAVE );
		$categoryTitle = $dbr->addQuotes( str_replace( ' ', '_', ucfirst( $categoryTitle ) ) );
		$subCategories = array( '*' => '' );

		$sqlQueryStr = "SELECT {$wgDBprefix}page.page_title " .
			"FROM {$wgDBprefix}page, {$wgDBprefix}categorylinks " .
			"WHERE {$wgDBprefix}categorylinks.cl_to = {$categoryTitle} " .
				"AND {$wgDBprefix}page.page_namespace = 14 " .
				"AND {$wgDBprefix}categorylinks.cl_from = {$wgDBprefix}page.page_id " .
			"ORDER BY {$wgDBprefix}categorylinks.cl_sortkey " .
			"LIMIT {$subCategoriesLimit}";
		$res = $dbr->query( $sqlQueryStr, 'MultiCategorySearch::getSubCategories', false );

		if( $dbr->numRows( $res ) == 0 )
			return false;
		while( $subCategory = $dbr->fetchObject( $res ) )
			$subCategories[ str_replace( '_', ' ', $subCategory->page_title ) ] =
				$subCategory->page_title;
		return $subCategories;
	}

	// FIXME totally ugly and unusable at the moment:
	// This function inserts drop-down lists for category selection (instead of simple text
	// input fields)
	function showDropdownLists() {
		global $wgRequest;

		if( $this->useDropdownLists == false )
			return '';

		$outputMarkup = '';
		$listOptions = array();

		// Array of captions for drop-down lists, each caption will appear to the left of the
		// corresponding list, change it to suit your needs
		$listCaptions = array(
			'1' => 'List1Caption:',
			'2' => 'List2Caption:',
			'3' => 'List3Caption:',
		);

		// Set exact titles of categories here, if you would like options for some drop-down lists
		// to be automatically filled with titles of sub-categories of specified categories.
		// Otherwise lists options must be set manually below.
		$listCategories = array(
			'1' => 'CategoryName1',
		);

		// The following arrays need to be manually set only if options for all necessary
		// drop-down lists were not set in $listCategories array above.
		// Select lists by the numbers.

		// Associative array of drop-down list 1 shown options and their corresponding category
		// names to search, change it to suit your needs
		$listOptions['1'] = array(
			'*' => '',
			'ShownOption1InList1' => 'CategoryName1',
			'ShownOption2InList1' => 'CategoryName2',
			'ShownOption3InList1' => 'CategoryName3',
		);
		// Associative array of drop-down list 2 shown options and their corresponding category
		// names to search, change it to suit your needs
		$listOptions['2'] = array(
			'*' => '',
			'ShownOption1InList2' => 'CategoryName4',
			'ShownOption2InList2' => 'CategoryName5',
			'ShownOption3InList2' => 'CategoryName6',
		);
		// Associative array of drop-down list 3 shown options and their corresponding category
		// names to search, change it to suit your needs
		$listOptions['3'] = array(
			'*' => '',
			'ShownOption1InList3' => 'CategoryName7',
			'ShownOption2InList3' => 'CategoryName8',
			'ShownOption3InList3' => 'CategoryName9',
		);
		// End of configuration settings, don't change the script below

		$listsNumber = count( $listCaptions );

		for( $i = 1; $i <= $listsNumber; $i++ ) {
			if( isset( $listCategories ) && is_array( $listCategories ) &&
				array_key_exists( $i, $listCategories ) &&
				strpos( $listCategories[$i], 'CategoryName' ) === false ) {
					$subCategories = $this->getSubCategories( $listCategories[$i] );
					if( $subCategories !== false )
						$listOptions[$i] = $subCategories;
			}

			$outputMarkup .= "
		<tr>
			<td>{$listCaptions[$i]}</td>
			<td>
				<select tabindex=\"{$i}\" name=\"wpInCategory{$i}\" id=\"wpInCategory{$i}\">\n";
			foreach( $listOptions[$i] as $optionName => $optionValue ) {
				$optionName = htmlspecialchars( $optionName );
				$optionValue = htmlspecialchars( $optionValue );
				$selected = '';
				if( $wgRequest->getVal( 'wpInCategory' . $i ) !== null &&
					$wgRequest->getVal( 'wpInCategory' . $i ) == $optionValue ) {
						$selected = ' selected="selected"';
					}
				$outputMarkup .= "\t\t\t\t\t<option value=\"{$optionValue}\"{$selected}>" .
					"{$optionName}</option>\n";
			}
			$outputMarkup .= "
				</select>
			</td>
		</tr>";
		}
		$outputMarkup .= '<tr><td colspan="2"><br /></td></tr>';

		return $outputMarkup;
	}
}
?>