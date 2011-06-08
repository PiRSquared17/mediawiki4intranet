<?php

/**
 * Features:
 * - <subpages> tag produces a templated list of all subpages of the current page
 * - {{#getsection|Title|section number}} parser function for extracting page sections
 * - $egSubpagelistAjaxNamespaces(NS_MAIN => true) setting to display subpage list
 *   using AJAX on every page with subpages from these namespaces.
 *   $egSubpagelistAjaxDisableRE is a regexp which is additionally checked and if matched,
 *   AJAX lister is hidden.
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @author based @ Martin Schallnahs <myself@schaelle.de>, Rob Church <robchur@gmail.com>
 * @copyright (c) 2009+ Vitaliy Filippov, original Martin Schallnahs, Rob Church
 * @licence GNU General Public Licence 2.0 or later
 * @link http://wiki.4intra.net/SubPageList_(MediaWiki)
 * @todo usage of the attributes category, namespace and parent like ignore
 *
 * @TODO caching save all <subpages> occurrences into the DB, save templatelinks, flush pages on page edits
 */

if (!defined('MEDIAWIKI'))
{
    echo "This file is an extension to the MediaWiki software and cannot be used standalone.\n";
    die(1);
}

if (file_exists('extensions/SubPageList.php'))
{
    echo "<strong>Fatal Error [Subpage List 2]:</strong> The orginal version of the SubpageList extension <em>(SubPageList.php)</em> is found in the extension dir, that may can produce a fatal error while using the <subpages> element.\n";
    die(1);
}

$wgExtensionFunctions[] = 'efSubpageList';
$wgExtensionMessagesFiles['SubPageList2'] = dirname(__FILE__).'/SubPageList2.i18n.php';
$wgHooks['LanguageGetMagic'][] = 'efSubpageListLanguageGetMagic';
$wgExtensionCredits['parserhook'][] = array(
    'name'   => 'Subpage List 3',
    'author' => 'Vitaliy Filippov, Martin Schallnahs, Rob Church'
);
$wgAjaxExportList[] = 'efAjaxSubpageList';

/* $egSubpagelistDefaultTemplate = "Template:SubPageList_Default"; */

/**
 * Hook in function
 */
function efSubpageList()
{
    global $wgParser, $wgHooks, $egSubpagelistAjaxNamespaces;
    $wgParser->setHook('subpages', 'efRenderSubpageList');
    $wgParser->setFunctionHook('getsection', 'efFunctionHookGetSection');
    if ($egSubpagelistAjaxNamespaces)
        $wgHooks['ArticleViewHeader'][] = 'efSubpageListAddLister';
}

function efSubpageListLanguageGetMagic(&$magicWords, $langCode = "en")
{
    $magicWords['getsection'] = array(0, 'getsection');
    return true;
}

/**
 * Parser function returning numbered section of article text
 */
function efFunctionHookGetSection($parser, $num)
{
    $args = func_get_args();
    array_shift($args);
    array_shift($args);
    $args = implode('|', $args);
    $st = $parser->mStripState;
    $text = $parser->getSection($args, $num);
    $parser->mStripState = $st;
    return $text;
}

/**
 * This function outputs nested html list with all subpages of a specific page
 */
function efAjaxSubpageList($pagename)
{
    global $wgUser;
    $title = Title::newFromText($pagename);
    if (!$title)
        return '';
    $dbr = wfGetDB(DB_SLAVE);
    $res = $dbr->select(
        'page', '*', array(
            'page_namespace' => $title->getNamespace(),
            'page_title '.$dbr->buildLike($title->getDBkey().'/', $dbr->anyString()),
        ), __METHOD__,
        array('ORDER BY' => 'page_title')
    );
    $pagelevel = substr_count($title->getText(), '/');
    $rows = array();
    foreach ($res as $row)
    {
        $row->title = Title::newFromRow($row);
        // TODO IntraACL: batch right checking (probably LinkBatch)
        if (!$row->title->userCanRead())
            continue;
        $parts = explode('/', $row->page_title);
        $row->level = count($parts)-1;
        $row->last_part = $parts[$row->level];
        $s = $sp = '';
        for ($i = 0; $i < count($parts)-1; $i++)
        {
            $s .= $sp.$parts[$i];
            $sp = '/';
            if (!$rows[$s] && $i > $pagelevel)
                $rows[$s] = (object)array('page_title' => $s, 'level' => $i);
        }
        $rows[$row->page_title] = $row;
    }
    $res = NULL;
    if (!$rows)
        return '';
    $html = '';
    $stack = array($pagelevel);
    foreach ($rows as $row)
    {
        if ($row->title)
            $link = $wgUser->getSkin()->link($row->title, $row->title->getSubpageText());
        else
        {
            $link = str_replace('_', ' ', $row->page_title);
            if (($p = strrpos($link, '/')) !== false)
                $link = substr($link, $p+1);
        }
        if ($row->level > $stack[0])
        {
            $html .= '<ul><li>'.$link;
            array_unshift($stack, $row->level);
        }
        else
        {
            while ($row->level < $stack[0])
            {
                $html .= '</li></ul>';
                array_shift($stack);
            }
            $html .= '</li><li>'.$link;
        }
    }
    while ($stack[0] > $pagelevel)
    {
        $html .= '</li></ul>';
        array_shift($stack);
    }
    return $html;
}

/**
 * Set to ArticleViewHeader hook, outputs JS subpage lister, if there are any subpages available.
 */
function efSubpageListAddLister($article, &$outputDone, &$useParserCache)
{
    global $egSubpagelistAjaxNamespaces, $egSubpagelistAjaxDisableRE;
    $title = $article->getTitle();
    // Filter pages based on namespace and title regexp
    if (!$egSubpagelistAjaxNamespaces ||
        !array_key_exists($title->getNamespace(), $egSubpagelistAjaxNamespaces) ||
        $egSubpagelistAjaxDisableRE && preg_match($egSubpagelistAjaxDisableRE, $title->getPrefixedText()))
        return true;
    $dbr = wfGetDB(DB_SLAVE);
    $subpagecount = $dbr->selectField('page', 'COUNT(*)', array(
        'page_namespace' => $title->getNamespace(),
        'page_title '.$dbr->buildLike($title->getDBkey().'/', $dbr->anyString()),
    ), __METHOD__);
    if ($subpagecount > 0)
    {
        // Add AJAX lister
        global $wgOut;
        wfLoadExtensionMessages('SubPageList2');
        $wgOut->addHTML(
            '<div id="subpagelist_ajax" class="catlinks" style="margin-top: 0"><a href="javascript:void(0)"'.
            ' onclick="sajax_do_call(\'efAjaxSubpageList\', [wgPageName], function(request){'.
            ' if (request.status != 200) return; var s = document.getElementById(\'subpagelist_ajax\');'.
            ' s.innerHTML = s.childNodes[0].innerHTML+request.responseText; })">'.
            wfMsgNoTrans('subpagelist-view', $subpagecount).
            '</a></div>'
        );
    }
    return true;
}

/**
 * Function called by the Hook, returns the wiki text
 */
function efRenderSubpageList($input, $args, $parser)
{
    global $egInSubpageList;
    if (!$egInSubpageList)
        $egInSubpageList = array();
    $key = array();
    ksort($args);
    foreach ($args as $k => $v)
    {
        $key[] = addslashes($k);
        $key[] = addslashes($v);
    }
    $key = implode('"', $key);
    /* An ugly hack for diff display: does it hook Article::getContent() ?!!! */
    if ($egInSubpageList[$key])
        return '';
    $egInSubpageList[$key] = 1;
    $list = new SubpageList($parser);
    $list->options($args);
    $r = $list->render();
    unset($egInSubpageList[$key]);
    return $r;
}

class SubpageList
{
    /* MediaWiki objects */
    var $parser;
    var $oldParser;
    var $title;
    var $language;

    /* Page set specification */
    var $namespace = NULL;
    var $parent    = NULL;
    var $category  = array();
    var $ignore    = NULL;
    var $deepmin   = '';
    var $deepmax   = '';

    /* Title of template used for transformation */
    var $template  = NULL;

    /* Order and limit specification */
    var $ordermethod = 'title';     /* title, lastedit, pagecounter */
    var $order = 'ASC';
    var $count = NULL;
    var $offset = 0;

    /* Debug mode variables */
    var $debug = 0;
    var $errors = array();

    /**
     * Constructor function of the class
     * @param object $parser the parser object
     * @global object $wgContLang
     * @see SubpageList
     * @private
     */
    function SubpageList(&$parser)
    {
        global $wgContLang;
        $parser->disableCache();
        $this->oldParser = $parser;
        $this->parser = clone $parser;
        $this->title = $parser->mTitle;
        $this->language = $wgContLang;
    }

    function error($message)
    {
        $this->errors[] = "<strong>Error [Subpage List 2]:</strong> $message";
    }

    function getErrors()
    {
        return implode("\n", $this->errors);
    }

    /**
     * check if there is any link to this cat, this is a check if there is a cat.
     * @param string $category the category title
     * @return boolean if there is a cat with this title
     * @todo Anyone in #mediawiki means this way isn't the best
     */
    function checkCat($category)
    {
        $dbr = wfGetDB(DB_SLAVE);
        $exists = $dbr->selectField('categorylinks', '1', array('cl_to' => $category), __METHOD__);
        return intval($exists) > 0;
    }

    /* Parse <subpagelist> options */
    function options($options)
    {
        global $egSubpagelistDefaultTemplate, $wgTitle;
        foreach ($options as $k => &$v)
            if (trim($v))
                $v = trim($this->preprocess($wgTitle, $v));
        unset($v);
        if (($c = str_replace(' ', '_', $options['category'])) && $c != '-1')
        {
            $cats = explode('|', $c);
            foreach ($cats as $c)
            {
                if ($this->checkCat($c))
                    $this->category[] = $c;
                else
                    $this->error("Category '$c' is undefined.");
            }
        }
        if ($t = $options['template'])
            $this->template = $t;
        elseif ($egSubpagelistDefaultTemplate)
            $this->template = $egSubpagelistDefaultTemplate;
        else
            $this->template = $this->language->getNsText(NS_TEMPLATE).':'.$this->title->getPrefixedText();
        if (($n = intval($options['count'])) > 0)
            $this->count = $n;
        if (($o = intval($options['offset'])) > 0)
            $this->offset = $o;
        if (($o = $options['debug']) && strtolower($o) != 'false' && strtolower($o) != 'no')
            $this->debug = 1;
        if ($d = $options['deepness'])
        {
            list ($min, $max) = split('\\.\\.', $d, 2);
            if (($min = intval($min)) > 0)
                $this->deepmin = $min;
            if (($max = intval($max)) > 0 && $max >= $min)
                $this->deepmax = $max;
        }
        if ($options['ignore'] && $options['ignore'] != '-1')
            $this->ignore = preg_split('/[\s\|]*\|[\s\|]*/', trim($options['ignore']));
        if ($o = strtolower($options['order']))
        {
            if ($o == 'asc' || $o == 'desc')
                $this->order = $o;
            else
                $this->error("Value '$o' is invalid for option order, valid values are ASC and DESC.");
        }
        if ($o = strtolower($options['ordermethod']))
        {
            if ($o == 'title' || $o == 'lastedit' || $o == 'pagecounter' || $o == 'creation')
                $this->ordermethod = $o;
            else
                $this->error("Value '$o' is invalid for option order, valid values are TITLE, LASTEDIT, and PAGECOUNTER.");
        }
        if (isset($options['namespace']) && ($o = $options['namespace']) != '-1')
        {
            if (!$o)
                $this->namespace = NULL;
            else if ($i = $this->language->getNsIndex($o))
                $this->namespace = $i;
            else
            {
                $this->namespace = $this->title->getNamespace();
                $this->error("Namespace $o is undefined.");
            }
        }
        if (isset($options['parent']) && ($o = $options['parent']) != '-1')
            $this->parent = $o;
    }

    /**
     * Produce the templatized page list
     * @return string html output
     */
    function render()
    {
        wfProfileIn(__METHOD__);
        $pages = $this->getPages();
        if (count($pages) > 0)
        {
            $list = $this->makeList($pages);
            $html = $this->parse($list);
            $html = preg_replace('#^<p>(.*)</p>$#is', '\1', $html);
        }
        else
            $html = '';
        if ($this->debug)
            $html = $this->getErrors() . $html;
        wfProfileOut(__METHOD__);
        return $html;
    }

    /**
     * Get article objects from the DB
     * @return array of Article objects
     */
    function getPages()
    {
        wfProfileIn(__METHOD__);
        $dbr = wfGetDB(DB_SLAVE);

        $conditions = array();
        $deepness = '';
        $options = array();
        $order = strtoupper($this->order);
        $parent = '';
        $tables = array('page');

        if ($this->count)
            $options['LIMIT'] = $this->count;
        if ($this->offset)
            $options['OFFSET'] = $this->offset;
        if ($this->deepmax || $this->deepmin)
            $deepness = '/?([^/]+(/|$)){' . $this->deepmin . ',' . $this->deepmax . '}$';
        if (!is_null($this->namespace))
            $conditions['page_namespace'] = $this->namespace;
        if ($this->ordermethod == 'title')
            $options['ORDER BY'] = '`page_namespace`, UPPER(`page_title`) ' . $order;
        else if ($this->ordermethod == 'pagecounter')
            $options['ORDER BY'] = '`page_counter` ' . $order;
        else if ($this->ordermethod == 'creation')
            $options['ORDER BY'] = '(SELECT rev_timestamp FROM `revision` WHERE rev_page=page_id ORDER BY rev_timestamp LIMIT 1) ' . $order;
        else // if ($this->ordermethod == 'lastedit')
            $options['ORDER BY'] = '`page_touched` ' . $order;
        if ($this->parent || !is_null($this->parent) && ($this->category || $deepness))
            $parent = str_replace(' ', '_', $this->parent);
        elseif (is_null($this->parent))
            $parent = $this->title->getDBkey() . '/';
        if ($this->ignore)
            foreach ($this->ignore as $aignore)
                $conditions[] = '`page_title` NOT LIKE ' . $dbr->addQuotes($parent . $aignore);

        $conditions['page_is_redirect'] = 0;
        if ($parent || $deepness)
            $conditions[] = '`page_title` REGEXP ' . $dbr->addQuotes('^' . preg_quote($parent) . $deepness);

        if ($this->category)
        {
            $tables[] = 'categorylinks';
            $conditions[] = 'page_id=cl_from';
            $conditions['cl_to'] = $this->category;
        }

        $content = array();
        $res = $dbr->select($tables, array('page_namespace', 'page_title'), $conditions, __METHOD__, $options);
        while ($row = $dbr->fetchObject($res))
        {
            $title = Title::makeTitleSafe($row->page_namespace, $row->page_title);
            if (is_object($title) && (!method_exists($title, 'userCanReadEx') || $title->userCanReadEx()))
            {
                $article = new Article($title);
                $content[] = $article;
            }
        }
        $dbr->freeResult($res);

        wfProfileOut(__METHOD__);
        return $content;
    }

    /**
     * Process $template using each article in $pages as params
     * and return concatenated output.
     * @param Array $pages Article objects
     * @param string $template Standard MediaWiki template-like source
     * @return string the parsed output
     */
    function makeList($pages)
    {
        $text = '';
        foreach ($pages as $i => $article)
        {
            $args = array();
            $t = $article->getTitle()->getPrefixedText();
            $args['index']         = $i;
            $args['number']        = $i+1;
            $args['odd']           = $i&1 ? 0 : 1;
            $args['title_full']    = $article->getTitle()->getPrefixedText();
            $args['ns_'.$article->getTitle()->getNamespace()] = 1;
            $args['title']         = $t;
            $args['title_rel']     = mb_substr($t, mb_strlen($this->parent));
            $args['title_last']    = mb_strrpos($t, '/') !== false ? mb_substr($t, mb_strrpos($t, '/')+1) : $t;
            $args['title_last']    = preg_replace('#\.jpg#is', '', $args['title_last']);
            $xml = '<root>';
            $xml .= '<template><title>'.$this->template.'</title>';
            foreach ($args as $k => $v)
                $xml .= '<part><name>'.htmlspecialchars($k).'</name>=<value>'.htmlspecialchars($v).'</value></part>';
            $xml .= '</template>';
            $xml .= '</root>';
            $dom = new DOMDocument;
            $result = $dom->loadXML($xml);
            if (!$result)
            {
                $this->error('Cannot build templatized list.');
                return '';
            }
            $text .= $this->preprocess($article, $dom);
        }
        return $text;
    }

    /**
     * Wrapper function parse, calls parser function parse
     * @param string $text the content
     * @return string the parsed output
     */
    function parse($text)
    {
        wfProfileIn(__METHOD__);
        $options = $this->oldParser->mOptions;
        $output = $this->parser->parse($text, $this->title, $options, true, false);
        wfProfileOut(__METHOD__);
        return $output->getText();
    }

    /**
     * Copy-pasted from Parser::preprocess and Parser::replaceVariables,
     * except that it also sets mRevisionTimestamp and passes PTD_FOR_INCLUSION.
     * @param string $article the article
     * @return string preprocessed article text
     */
    function preprocess($article, $dom = NULL)
    {
        wfProfileIn(__METHOD__);
        $this->parser->clearState();
        $this->parser->setOutputType(Parser::OT_PREPROCESS);
        if ($article instanceof Title)
        {
            $title = $article;
            $article = new Article($title);
        }
        else
            $title = $article->getTitle();
        $this->parser->setTitle($title);
        if ($article)
        {
            $this->parser->mRevisionId = $article->getRevIdFetched();
            $this->parser->mRevisionTimestamp = $article->getTimestamp();
            if ($dom === NULL)
                $dom = $article->getContent();
        }
        if ($dom === NULL)
            return '';
        if (!is_object($dom))
        {
            wfRunHooks('ParserBeforeStrip', array(&$this->parser, &$dom, &$this->parser->mStripState));
            wfRunHooks('ParserAfterStrip', array(&$this->parser, &$dom, &$this->parser->mStripState));
            $dom = $this->parser->preprocessToDom($dom, Parser::PTD_FOR_INCLUSION);
        }
        $frame = $this->parser->getPreprocessor()->newFrame();
        $text = $frame->expand($dom);
        $text = $this->parser->mStripState->unstripBoth($text);
        wfProfileOut(__METHOD__);
        return $text;
    }
}
