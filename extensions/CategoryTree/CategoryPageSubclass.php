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

    function columnList($items, $start_char)
    {
        global $wgCategoryGroupCharacters;
        /* If all $start_char's are more than 1-character strings,
           or if grouping is disabled through config, return normal list */
        if (!$items || mb_strlen($start_char[0]) > 1 || !$wgCategoryGroupCharacters)
            return parent::columnList($items, $start_char);
        $n = count($items);
        for ($i = 0; $i < $n-1 && mb_strlen($start_char[$i+1]) == 1; $i++)
        {
            /* Group adjacent 1-char subtitles having only 1 item
               with first subtitle having more than 1 item */
            $s = $i;
            while ($i < $n-1 && mb_strlen($start_char[$i+1]) == 1 &&
                $start_char[$i] != $start_char[$i+1] &&
                /* Don't group characters of different length */
                strlen($start_char[$i]) == strlen($start_char[$i+1]))
                $i++;
            $e = $i;
            while ($i < $n-1 && $start_char[$i] == $start_char[$i+1])
                $i++;
            /* Group last 1-char subtitle also */
            if ($e == $s && mb_strlen($start_char[$i+1]) == 1 &&
                ($i == $n-2 || mb_strlen($start_char[$i+2]) > 1))
                $e = ++$i;
            if ($e > $s)
            {
                $key = $start_char[$s] . '-' . $start_char[$e];
                for ($j = $s; $j <= $i; $j++)
                    $start_char[$j] = $key;
            }
        }
        return parent::columnList($items, $start_char);
    }

    function getPagesSection()
    {
        global $wgMinUncatPagesAlphaList;
        global $wgCategorySubcategorizedList;
        global $wgSubcategorizedAlwaysExclude;
        global $wgOut;
        /* If there is no articles, or if we are forced to show normal list - show it */
        if (!$this->articles || !$wgCategorySubcategorizedList && !$wgOut->useSubcategorizedList ||
            $wgCategorySubcategorizedList && !is_null($wgOut->useSubcategorizedList) &&
            !$wgOut->useSubcategorizedList)
            return parent::getPagesSection();
        $ids = array();
        foreach ($this->titles as $t)
            $ids[] = $t->getArticleID();
        $dbr = wfGetDB(DB_SLAVE);
        /* Exclude all parent categories */
        $supercats = $this->getAllParentCategories($dbr, $this->title);
        /* Always exclude "special" categories, marked with
           one of $wgSubcategorizedAlwaysExclude. */
        if (is_array($wgSubcategorizedAlwaysExclude))
            foreach ($wgSubcategorizedAlwaysExclude as $v)
                $supercats[] = str_replace(' ', '_', $v);
        $where = array('cl_from' => $ids);
        foreach ($supercats as $k)
            $where[] = 'cl_to!='.$dbr->addQuotes($k);
        $res = $dbr->select('categorylinks', '*', $where, __METHOD__, array('ORDER BY' => 'cl_sortkey'));
        $cl = array();
        while ($row = $dbr->fetchRow($res))
            $cl[$row['cl_to']][] = $row['cl_from'];
        $dbr->freeResult($res);
        /* Make subcategorized article and subtitle list */
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
        /* Count unsubcategorized articles */
        $count_undone = 0;
        for ($i = count($this->articles)-1; $i >= 0; $i--)
            if (!$done[$i])
                $count_undone++;
        $cutoff = $wgMinUncatPagesAlphaList;
        if (!$cutoff || $cutoff < 0)
            $cutoff = 10;
        /* If there is less than $cutoff, show them all with
           current category subtitle, else show normal alpha-list. */
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
        /* Replace article and subtitle list and call parent */
        $this->articles = $new;
        $this->articles_start_char = $newkey;
        $this->nonplanar_short_list = true;
        $html = parent::getPagesSection();
        $this->nonplanar_short_list = false;
        return $html;
    }

    /* Short list without subtitles, if not called from $this->getPagesSection() */
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
