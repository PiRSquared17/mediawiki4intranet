<?php

class CategoryTreeCategoryPage extends CategoryPage {
	function closeShowCategory() {
		global $wgOut, $wgRequest;
		$from = $wgRequest->getVal( 'from' );
		$until = $wgRequest->getVal( 'until' );

		$viewer = new CategoryTreeCategoryViewer( $this->mTitle, $from, $until );
		$wgOut->addHTML( $viewer->getHTML() );
	}
}

class CategoryTreeCategoryViewer extends CategoryViewer {
	var $child_cats;

	function getCategoryTree() {
		global $wgOut, $wgCategoryTreeCategoryPageOptions, $wgCategoryTreeForceHeaders;

		if ( ! isset($this->categorytree) ) {
			if ( !$wgCategoryTreeForceHeaders ) CategoryTree::setHeaders( $wgOut );

			$this->categorytree = new CategoryTree( $wgCategoryTreeCategoryPageOptions );
		}

		return $this->categorytree;
	}

	/**
	 * Add a subcategory to the internal lists
	 */
	function addSubcategoryObject( $cat, $sortkey, $pageLength ) {
		global $wgContLang, $wgOut, $wgRequest;

		$title = $cat->getTitle();

		if ( $wgRequest->getCheck( 'notree' ) ) {
			return parent::addSubcategoryObject( $cat, $sortkey, $pageLength );
		}

		/*if ( ! $GLOBALS['wgCategoryTreeUnifiedView'] ) {
			$this->child_cats[] = $cat;
			return parent::addSubcategory( $cat, $sortkey, $pageLength );
		}*/

		$tree = $this->getCategoryTree();

		$this->children[] = $tree->renderNodeInfo( $title, $cat );

		$this->children_start_char[] = $this->getSubcategorySortChar( $title, $sortkey );
	}

	/* 
	# this is a pain to keep this consistent, and no one should be using wgCategoryTreeUnifiedView = false anyway.
	function getSubcategorySection() {
		global $wgOut, $wgRequest, $wgCookiePrefix;

		if ( $wgRequest->getCheck( 'notree' ) ) {
			return parent::getSubcategorySection();
		}

		if ( $GLOBALS['wgCategoryTreeUnifiedView'] ) {
			return parent::getSubcategorySection();
		}

		if( count( $this->children ) == 0 ) {
			return '';
		}

		$r = '<h2>' . wfMsg( 'subcategories' ) . "</h2>\n" .
			wfMsgExt( 'subcategorycount', array( 'parse' ), count( $this->children) );

		# Use a cookie to save the user's last selection, so that AJAX doesn't
		# keep coming back to haunt them.
		#
		# FIXME: This doesn't work very well with IMS handling in
		# OutputPage::checkLastModified, because when the cookie changes, the
		# category pages are not, at present, invalidated.
		$cookieName = $wgCookiePrefix.'ShowSubcatAs';
		$cookieVal = @$_COOKIE[$cookieName];
		$reqShowAs = $wgRequest->getVal( 'showas' );
		if ( $reqShowAs == 'list' ) {
			$showAs = 'list';
		} elseif ( $reqShowAs == 'tree' ) {
			$showAs = 'tree';
		} elseif ( $cookieVal == 'list' || $cookieVal == 'tree' ) {
			$showAs = $cookieVal;
		} else {
			$showAs = 'tree';
		}

		if ( !is_null( $reqShowAs ) ) {
			global $wgCookieExpiration, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
			$exp = time() + $wgCookieExpiration;
			setcookie( $cookieName, $showAs, $exp, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
		}

		if ( $showAs == 'tree' && count( $this->children ) > $this->limit ) {
			# Tree doesn't page properly
			$showAs = 'list';
			$r .= self::msg( 'too-many-subcats' );
		} else {
			$sk = $this->getSkin();
			$r .= '<p>' .
				$this->makeShowAsLink( 'tree', $showAs ) .
				' | ' .
				$this->makeShowAsLink( 'list', $showAs ) .
				'</p>';
		}

		if ( $showAs == 'list' ) {
			$r .= $this->formatList( $this->children, $this->children_start_char );
		} else {
			$ct = $this->getCategoryTree();

			foreach ( $this->child_cats as $cat ) {
				$r .= $ct->renderNodeInfo( $cat->getTitle(), $cat );
			}
		}
		return $r;
	}

	function makeShowAsLink( $targetValue, $currentValue ) {
		$msg = htmlspecialchars( CategoryTree::msg( "show-$targetValue" ) );

		if ( $targetValue == $currentValue ) {
			return "<strong>$msg</strong>";
		} else {
			return $this->getSkin()->makeKnownLinkObj( $this->title, $msg, "showas=$targetValue" );
		}
	} */

	function clearCategoryState() {
		$this->child_cats = array();
		parent::clearCategoryState();
	}

	function finaliseCategoryState() {
		if( $this->flip ) {
			$this->child_cats = array_reverse( $this->child_cats );
		}
		parent::finaliseCategoryState();
	}

    function addPage($title, $sortkey, $pageLength, $isRedirect = false)
    {
        $this->titles[] = $title;
        parent::addPage($title, $sortkey, $pageLength, $isRedirect);
    }

    function getAllParentCategories($dbr, $title)
    {
        $supercats = array($title->getDBkey());
        $sch = array($title->getDBkey() => true);
        while ($supercats)
        {
            $res = $dbr->select(array('categorylinks', 'page'), 'cl_to', array(
                'cl_from=page_id',
                'page_namespace' => NS_CATEGORY,
                'page_title' => $supercats,
            ), __METHOD__, array('GROUP BY' => 'cl_to'));
            $supercats = array();
            while ($row = $dbr->fetchRow($res))
            {
                if (!$sch[$row[0]])
                {
                    $supercats[] = $row[0];
                    $sch[$row[0]] = true;
                }
            }
            $dbr->freeResult($res);
        }
        return array_keys($sch);
    }

    function getPagesSection()
    {
        global $wgMinUncatPagesAlphaList;
        global $wgCategorySubcategorizedList;
        global $wgOut;
        if (!$this->articles || !$wgCategorySubcategorizedList && !$wgOut->useSubcategorizedList ||
            $wgCategorySubcategorizedList && !is_null($wgOut->useSubcategorizedList) &&
            !$wgOut->useSubcategorizedList)
            return parent::getPagesSection();
        $ids = array();
        foreach ($this->titles as $t)
            $ids[] = $t->getArticleID();
        $dbr = wfGetDB(DB_SLAVE);
        $supercats = $this->getAllParentCategories($dbr, $this->title);
        $where = array('cl_from' => $ids);
        foreach ($supercats as $k)
            $where[] = 'cl_to!='.$dbr->addQuotes($k);
        $res = $dbr->select('categorylinks', '*', $where, __METHOD__,
            array('ORDER BY' => 'cl_sortkey'));
        $cl = array();
        while ($row = $dbr->fetchRow($res))
            $cl[$row['cl_to']][] = $row['cl_from'];
        $dbr->freeResult($res);
        $new = array();
        $newkey = array();
        $done = array();
        $ids = array_flip($ids);
        foreach ($cl as $cat => $list)
        {
            $cat = str_replace('_', ' ', $cat);
            foreach ($list as $a)
            {
                $new[] = $this->articles[$ids[$a]];
                $newkey[] = $cat;
                $done[$ids[$a]] = true;
            }
        }
        $count_undone = 0;
        for ($i = count($this->articles)-1; $i >= 0; $i--)
            if (!$done[$i])
                $count_undone++;
        $cutoff = $wgMinUncatPagesAlphaList;
        if (!$cutoff || $cutoff < 0)
            $cutoff = 10;
        for ($i = count($this->articles)-1; $i >= 0; $i--)
        {
            if (!$done[$i])
            {
                array_unshift($new, $this->articles[$i]);
                if ($count_undone > $cutoff)
                    array_unshift($newkey, $this->articles_start_char[$i]);
                else
                    array_unshift($newkey, $this->title->getText());
            }
        }
        $this->articles = $new;
        $this->articles_start_char = $newkey;
        $this->nonplanar_short_list = true;
        $html = parent::getPagesSection();
        $this->nonplanar_short_list = false;
        return $html;
    }

    function shortList($articles, $articles_start_char)
    {
        if ($this->nonplanar_short_list)
            return parent::shortList($articles, $articles_start_char);
        $r = '<ul>';
        foreach ($articles as $a)
            $r .= "<li>$a</li>";
        $r .= '</ul>';
        return $r;
    }
}
