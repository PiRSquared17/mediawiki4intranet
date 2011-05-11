<?php

/**
 * implements Special:Newpages
 * @ingroup SpecialPage
 */
class SpecialNewpages extends SpecialPage {

	// Stored objects
	protected $opts, $skin;

	// Some internal settings
	protected $showNavigation = false;

	// Default item format
	var $format = '$date $time $dm$plink $hist $dm[$length] $dm$ulink $utlink $comment $ctags';

	public function __construct() {
		parent::__construct( 'Newpages' );
		$this->includable( true );
	}

	protected function setup( $par ) {
		global $wgRequest, $wgUser, $wgEnableNewpagesUserFilter;

		// Options
		$opts = new FormOptions();
		$this->opts = $opts; // bind
		$opts->add( 'hideliu', false );
		$opts->add( 'hidepatrolled', $wgUser->getBoolOption( 'newpageshidepatrolled' ) );
		$opts->add( 'hidebots', false );
		$opts->add( 'hideredirs', true );
		$opts->add( 'limit', (int)$wgUser->getOption( 'rclimit' ) );
		$opts->add( 'offset', '' );
		$opts->add( 'namespace', '0' );
		$opts->add( 'username', '' );
		$opts->add( 'category', '' );
		$opts->add( 'feed', '' );
		$opts->add( 'tagfilter', '' );
		$opts->add( 'format', $this->format );

		// Set values
		$opts->fetchValuesFromRequest( $wgRequest );
		if ( $par ) $this->parseParams( $par );

		// Validate
		$opts->validateIntBounds( 'limit', 0, 5000 );
		if( !$wgEnableNewpagesUserFilter ) {
			$opts->setValue( 'username', '' );
		}

		// Store some objects
		$this->skin = $wgUser->getSkin();
	}

	protected function parseParams( $par )
	{
		global $wgLang;
		$bits = preg_match_all(
			'/(shownav|hide(?:liu|patrolled|bots|redirs))|'.
			'(limit|offset|username|category|namespace|format)\s*=\s*(?:([^,]+)|"([^"]*)"|\'([^\']*)\')/is',
			$par, $m, PREG_SET_ORDER );
		foreach ( $m as $bit )
		{
			if ( $bit[1] == 'shownav' )
				$this->showNavigation = true;
			elseif ( $bit[1] )
				$this->opts->setValue( $bit[1], true );
			elseif ( $bit[2] == 'namespace' )
			{
				$ns = $wgLang->getNsIndex( $bit[3] ? $bit[3] : ( $bit[4] ? $bit[4] : $bit[5] ) );
				if( $ns !== false )
					$this->opts->setValue( 'namespace', $ns );
			}
			else
				$this->opts->setValue( $bit[2], $bit[3] ? $bit[3] : ( $bit[4] ? $bit[4] : $bit[5] ) );
		}
	}

	/**
	 * Show a form for filtering namespace and username
	 *
	 * @param string $par
	 * @return string
	 */
	public function execute( $par ) {
		global $wgLang, $wgOut;

		$this->setHeaders();
		$this->outputHeader();

		$this->showNavigation = !$this->including(); // Maybe changed in setup
		$this->setup( $par );

		if( !$this->including() ) {
			// Settings
			$this->form();

			$this->setSyndicated();
			$feedType = $this->opts->getValue( 'feed' );
			if( $feedType ) {
				return $this->feed( $feedType );
			}
		}

		$pager = new NewPagesPager( $this, $this->opts );
		$pager->mLimit = $this->opts->getValue( 'limit' );
		$pager->mOffset = $this->opts->getValue( 'offset' );
		$this->format = $this->opts->getValue( 'format' );

		if( $pager->getNumRows() ) {
			$navigation = '';
			if ( $this->showNavigation ) $navigation = $pager->getNavigationBar();
			$wgOut->addHTML( $navigation . $pager->getBody() . $navigation );
		} else {
			$wgOut->addWikiMsg( 'specialpage-empty' );
		}
	}

	protected function filterLinks() {
		global $wgGroupPermissions, $wgUser, $wgLang;

		// show/hide links
		$showhide = array( wfMsgHtml( 'show' ), wfMsgHtml( 'hide' ) );

		// Option value -> message mapping
		$filters = array(
			'hideliu' => 'rcshowhideliu',
			'hidepatrolled' => 'rcshowhidepatr',
			'hidebots' => 'rcshowhidebots',
			'hideredirs' => 'whatlinkshere-hideredirs'
		);

		// Disable some if needed
		if ( $wgGroupPermissions['*']['createpage'] !== true )
			unset($filters['hideliu']);

		if ( !$wgUser->useNPPatrol() )
			unset($filters['hidepatrolled']);

		$links = array();
		$changed = $this->opts->getChangedValues();
		unset($changed['offset']); // Reset offset if query type changes

		$self = $this->getTitle();
		foreach ( $filters as $key => $msg ) {
			$onoff = 1 - $this->opts->getValue($key);
			$link = $this->skin->link( $self, $showhide[$onoff], array(),
				 array( $key => $onoff ) + $changed
			);
			$links[$key] = wfMsgHtml( $msg, $link );
		}

		return $wgLang->pipeList( $links );
	}

	protected function form() {
		global $wgOut, $wgEnableNewpagesUserFilter, $wgDisableNewpagesCategoryFilter, $wgScript;

		// Consume values
		$this->opts->consumeValue( 'offset' ); // don't carry offset, DWIW
		$namespace = $this->opts->consumeValue( 'namespace' );
		$username = $this->opts->consumeValue( 'username' );
		$tagFilterVal = $this->opts->consumeValue( 'tagfilter' );

		// Check username input validity
		$ut = Title::makeTitleSafe( NS_USER, $username );
		$userText = $ut ? $ut->getText() : '';

		$category = $this->opts->consumeValue( 'category' );

		// Store query values in hidden fields so that form submission doesn't lose them
		$hidden = array();
		foreach ( $this->opts->getUnconsumedValues() as $key => $value ) {
			$hidden[] = Xml::hidden( $key, $value );
		}
		$hidden = implode( "\n", $hidden );

		$tagFilter = ChangeTags::buildTagFilterSelector( $tagFilterVal );
		if ($tagFilter)
			list( $tagFilterLabel, $tagFilterSelector ) = $tagFilter;

		$form = Xml::openElement( 'form', array( 'action' => $wgScript ) ) .
			Xml::hidden( 'title', $this->getTitle()->getPrefixedDBkey() ) .
			Xml::fieldset( wfMsg( 'newpages' ) ) .
			Xml::openElement( 'table', array( 'id' => 'mw-newpages-table' ) ) .
			"<tr>
				<td class='mw-label'>" .
					Xml::label( wfMsg( 'namespace' ), 'namespace' ) .
				"</td>
				<td class='mw-input'>" .
					Xml::namespaceSelector( $namespace, 'all' ) .
				"</td>
			</tr>" . ( $tagFilter ? (
			"<tr>
				<td class='mw-label'>" .
					$tagFilterLabel .
				"</td>
				<td class='mw-input'>" .
					$tagFilterSelector .
				"</td>
			</tr>" ) : '' ) .
			($wgEnableNewpagesUserFilter ?
			"<tr>
				<td class='mw-label'>" .
					Xml::label( wfMsg( 'newpages-username' ), 'mw-np-username' ) .
				"</td>
				<td class='mw-input'>" .
					Xml::input( 'username', 30, $userText, array( 'id' => 'mw-np-username' ) ) .
				"</td>
			</tr>" : "" ) .
			($wgDisableNewpagesCategoryFilter ? "" :
			"<tr>
				<td class='mw-label'>" .
					Xml::label( wfMsg( 'newpages-category' ), 'mw-np-category' ) .
				"</td>
				<td class='mw-input'>" .
					Xml::input( 'category', 30, $category, array( 'id' => 'mw-np-category' ) ) .
				"</td>
			</tr>") .
			"<tr> <td></td>
				<td class='mw-submit'>" .
					Xml::submitButton( wfMsg( 'allpagessubmit' ) ) .
				"</td>
			</tr>" .
			"<tr>
				<td></td>
				<td class='mw-input'>" .
					$this->filterLinks() .
				"</td>
			</tr>" .
			Xml::closeElement( 'table' ) .
			Xml::closeElement( 'fieldset' ) .
			$hidden .
			Xml::closeElement( 'form' );

		$wgOut->addHTML( $form );
	}

	protected function setSyndicated() {
		global $wgOut;
		$wgOut->setSyndicated( true );
		$wgOut->setFeedAppendQuery( wfArrayToCGI( $this->opts->getAllValues() ) );
	}

	/**
	 * Format a row, providing the timestamp, links to the page/history, size, user links, and a comment
	 *
	 * @param $skin Skin to use
	 * @param $result Result row
	 * @return string
	 */
	public function formatRow( $result ) {
		global $wgLang, $wgContLang;

		$title = Title::makeTitleSafe( $result->rc_namespace, $result->rc_title );
/*patch|2011-05-11|IntraACL|start*/
		if (!$title->userCanReadEx())
			return '';
/*patch|2011-05-11|IntraACL|end*/

		$dm = $wgContLang->getDirMark();
		$length = wfMsgExt( 'nbytes', array( 'parsemag', 'escape' ),
			$wgLang->formatNum( $result->length ) );
		$comment = $this->skin->commentBlock( $result->rc_comment );

		$query = array( 'redirect' => 'no' );
		$classes = array();

		if( $this->patrollable( $result ) )
		{
			$query['rcid'] = $result->rc_id;
			$classes[] = 'not-patrolled';
		}

		# Tags, if any.
		list( $tagDisplay, $newClasses ) = ChangeTags::formatSummaryRow( $result->ts_tags, 'newpages' );
		$classes = array_merge( $classes, $newClasses );

		$params = array(
			'$dm'       => $dm,
			'$date'     => htmlspecialchars( $wgLang->date( $result->rc_timestamp, true ) ),
			'$time'     => htmlspecialchars( $wgLang->time( $result->rc_timestamp, true ) ),
			'$plink'    => $this->skin->linkKnown($title, null, array(), $query),
			'$ulink'    => $this->skin->userLink( $result->rc_user, $result->rc_user_text ),
			'$utlink'   => $this->skin->userToolLinks( $result->rc_user, $result->rc_user_text ),
			'$hist'     => $this->skin->linkKnown($title, wfMsgHtml('hist'), array(), array('action' => 'history')),
			'$length'   => $length,
			'$comment'  => $comment,
			'$ctags'    => $tagDisplay,
		);

		$css = count($classes) ? ' class="'.implode( " ", $classes).'"' : '';

		return "<li$css>".str_replace( array_keys( $params ), array_values( $params ), $this->format )."</li>\n";
	}

	/**
	 * Should a specific result row provide "patrollable" links?
	 *
	 * @param $result Result row
	 * @return bool
	 */
	protected function patrollable( $result ) {
		global $wgUser;
		return ( $wgUser->useNPPatrol() && !$result->rc_patrolled );
	}

	/**
	 * Output a subscription feed listing recent edits to this page.
	 * @param string $type
	 */
	protected function feed( $type ) {
		global $wgFeed, $wgFeedClasses, $wgFeedLimit;

		if ( !$wgFeed ) {
			global $wgOut;
			$wgOut->addWikiMsg( 'feed-unavailable' );
			return;
		}

		if( !isset( $wgFeedClasses[$type] ) ) {
			global $wgOut;
			$wgOut->addWikiMsg( 'feed-invalid' );
			return;
		}

		$feed = new $wgFeedClasses[$type](
			$this->feedTitle(),
			wfMsgExt( 'tagline', 'parsemag' ),
			$this->getTitle()->getFullUrl() );

		$pager = new NewPagesPager( $this, $this->opts );
		$limit = $this->opts->getValue( 'limit' );
		$pager->mLimit = min( $limit, $wgFeedLimit );

		$feed->outHeader();
		if( $pager->getNumRows() > 0 ) {
			while( $row = $pager->mResult->fetchObject() ) {
				$feed->outItem( $this->feedItem( $row ) );
			}
		}
		$feed->outFooter();
	}

	protected function feedTitle() {
		global $wgContLanguageCode, $wgSitename;
		$page = SpecialPage::getPage( 'Newpages' );
		$desc = $page->getDescription();
		return "$wgSitename - $desc [$wgContLanguageCode]";
	}

	protected function feedItem( $row ) {
		$title = Title::MakeTitle( intval( $row->rc_namespace ), $row->rc_title );
		if( $title ) {
			$date = $row->rc_timestamp;
			$comments = $title->getTalkPage()->getFullURL();

			return new FeedItem(
				$title->getPrefixedText(),
				$this->feedItemDesc( $row ),
				$title->getFullURL(),
				$date,
				$this->feedItemAuthor( $row ),
				$comments);
		} else {
			return null;
		}
	}

	protected function feedItemAuthor( $row ) {
		return isset( $row->rc_user_text ) ? $row->rc_user_text : '';
	}

	protected function feedItemDesc( $row ) {
		$revision = Revision::newFromId( $row->rev_id );
		if( $revision ) {
			return '<p>' . htmlspecialchars( $revision->getUserText() ) . wfMsgForContent( 'colon-separator' ) .
				htmlspecialchars( FeedItem::stripComment( $revision->getComment() ) ) . 
				"</p>\n<hr />\n<div>" .
				nl2br( htmlspecialchars( $revision->getText() ) ) . "</div>";
		}
		return '';
	}
}

/**
 * @ingroup SpecialPage Pager
 */
class NewPagesPager extends ReverseChronologicalPager {
	// Stored opts
	protected $opts, $mForm;

	function __construct( $form, FormOptions $opts ) {
		parent::__construct();
		$this->mForm = $form;
		$this->opts = $opts;
	}

	function getTitle() {
		static $title = null;
		if ( $title === null )
			$title = $this->mForm->getTitle();
		return $title;
	}

	function getQueryInfo() {
		global $wgEnableNewpagesUserFilter, $wgGroupPermissions, $wgUser;
		$conds = array();
		$conds['rc_new'] = 1;

		$namespace = $this->opts->getValue( 'namespace' );
		$namespace = ( $namespace === 'all' ) ? false : intval( $namespace );

		$username = $this->opts->getValue( 'username' );
		$user = Title::makeTitleSafe( NS_USER, $username );

		$category = $this->opts->getValue( 'category' );
		$categoryTitle = $category ? Title::newFromText( $category, NS_CATEGORY ) : NULL;
		$category = $categoryTitle && $categoryTitle->exists() ? $categoryTitle->getDBkey() : '';

		if( $namespace !== false ) {
			$conds['rc_namespace'] = $namespace;
			$rcIndexes = array( 'new_name_timestamp' );
		} else {
			$rcIndexes = array( 'rc_timestamp' );
		}

		# $wgEnableNewpagesUserFilter - temp WMF hack
		if( $wgEnableNewpagesUserFilter && $user ) {
			$conds['rc_user_text'] = $user->getText();
			$rcIndexes = 'rc_user_text';
		# If anons cannot make new pages, don't "exclude logged in users"!
		} elseif( $wgGroupPermissions['*']['createpage'] && $this->opts->getValue( 'hideliu' ) ) {
			$conds['rc_user'] = 0;
		}
		# If this user cannot see patrolled edits or they are off, don't do dumb queries!
		if( $this->opts->getValue( 'hidepatrolled' ) && $wgUser->useNPPatrol() ) {
			$conds['rc_patrolled'] = 0;
		}
		if( $this->opts->getValue( 'hidebots' ) ) {
			$conds['rc_bot'] = 0;
		}

		if ( $this->opts->getValue( 'hideredirs' ) ) {
			$conds['page_is_redirect'] = 0;
		}

		$info = array(
			'tables' => array( 'recentchanges', 'page' ),
			'fields' => 'rc_namespace,rc_title, rc_cur_id, rc_user,rc_user_text,rc_comment,
				rc_timestamp,rc_patrolled,rc_id,page_len as length, page_latest as rev_id, ts_tags',
			'conds' => $conds,
			'options' => array( 'USE INDEX' => array('recentchanges' => $rcIndexes) ),
			'join_conds' => array(
				'page' => array('INNER JOIN', 'page_id=rc_cur_id'),
			),
		);

		if ( $category !== NULL )
		{
			$info['tables'][] = 'categorylinks';
			$info['join_conds']['categorylinks'] = array('INNER JOIN', 'cl_from = page_id');
			$info['conds']['cl_to'] = $category;
		}

		## Empty array for fields, it'll be set by us anyway.
		$fields = array();

		## Modify query for tags
		ChangeTags::modifyDisplayQuery( $info['tables'],
										$fields,
										$info['conds'],
										$info['join_conds'],
										$info['options'],
										$this->opts['tagfilter'] );

		return $info;
	}

	function getIndexField() {
		return 'rc_timestamp';
	}

	function formatRow( $row ) {
		return $this->mForm->formatRow( $row );
	}

	function getStartBody() {
		# Do a batch existence check on pages
		$linkBatch = new LinkBatch();
		while( $row = $this->mResult->fetchObject() ) {
			$linkBatch->add( NS_USER, $row->rc_user_text );
			$linkBatch->add( NS_USER_TALK, $row->rc_user_text );
			$linkBatch->add( $row->rc_namespace, $row->rc_title );
		}
		$linkBatch->execute();
		return "<ul>";
	}

	function getEndBody() {
		return "</ul>";
	}
}
