<?php

/**
 * Add a <subpages> tag which produces a templated list of all subpages of the current page
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @author based @ Martin Schallnahs <myself@schaelle.de>, Rob Church <robchur@gmail.com>
 * @copyright (c) 2009 Vitaliy Filippov, original Martin Schallnahs, Rob Church
 * @licence GNU General Public Licence 2.0 or later
 * @link http://yourcmc.ru/wiki/SubPageList_(MediaWiki)
 * @todo usage of the attributes category, namespace and parent like ignore
 * @todo save all <subpages> occurrences into the DB, save templatelinks, flush pages on page edits
 *
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
$wgExtensionCredits['parserhook'][] = array(
    'name'   => 'Subpage List 3',
    'author' => 'Vitaliy Filippov, Martin Schallnahs, Rob Church'
);

/**
 * Hook in function
 */
function efSubpageList()
{
    global $wgParser;
    $wgParser->setHook('subpages', 'efRenderSubpageList');
}

/**
 * Function called by the Hook, returns the wiki text
 */
function efRenderSubpageList($input, $args, &$parser)
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
    var $title;
    var $language;

    /* Page set specification */
    var $namespace = NULL;
    var $parent    = NULL;
    var $category  = NULL;
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
        $dbr =& wfGetDB(DB_SLAVE);
        $exists = $dbr->selectField('categorylinks', '1', array('cl_to' => $category), __METHOD__);
        return intval($exists) > 0;
    }

    /* Parse <subpagelist> options */
    function options($options)
    {
        if (($c = str_replace(' ', '_', $options['category'])) && $c != '-1')
        {
            if ($this->checkCat($c))
                $this->category = $c;
            else
                $this->error("Category '$c' is undefined.");
        }
        if ($t = $options['template'])
            $this->template = $t;
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
            if ($o == 'title' || $o == 'lastedit' || $o == 'pagecounter')
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
        $dbr =& wfGetDB(DB_SLAVE);

        $conditions = array();
        $deepness = '';
        $options = array();
        $order = strtoupper($this->order);
        $parent = '';
        $page = $dbr->tableName('page');

        if ($this->count)
            $options['LIMIT'] = $this->count;
        if ($this->offset)
            $options['OFFSET'] = $this->offset;
        if ($this->deepmax || $this->deepmin)
            $deepness = '(/?[^/]+){' . $this->deepmin . ',' . $this->deepmax . '}$';
        if (!is_null($this->namespace))
            $conditions['page_namespace'] = $this->namespace;
        if ($this->ordermethod == 'title')
            $options['ORDER BY'] = '`page_namespace`, UPPER(`page_title`) ' . $order;
        else if ($this->ordermethod == 'pagecounter')
            $options['ORDER BY'] = '`page_counter` ' . $order;
        else // if ($this->ordermethod == 'lastedit')
            $options['ORDER BY'] = '`page_touched` ' . $order;
        if ($this->parent || !is_null($this->parent) && ($this->category || $deepness))
            $parent = $this->parent;
        else
            $parent = $this->title->getDBkey() . '/';
        if ($this->ignore)
            foreach ($this->ignore as $aignore)
                $conditions[] = '`page_title` NOT LIKE ' . $dbr->addQuotes($parent . $aignore);

        $tables = array('page');
        $conditions['page_is_redirect'] = 0;
        if ($parent || $deepness)
            $conditions[] = '`page_title` REGEXP ' . $dbr->addQuotes('^' . preg_quote($parent) . $deepness);

        if ($this->category)
        {
            $tables[] = 'categorylinks';
            $conditions[] = 'page_id=cl_from AND cl_to='.$dbr->addQuotes($this->category);
        }

        $content = array();
        $res = $dbr->select($tables, array('page_namespace', 'page_title'), $conditions, __METHOD__, $options);
        while($row = $dbr->fetchObject($res))
        {
            $title = Title::makeTitleSafe($row->page_namespace, $row->page_title);
            if(is_object($title))
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
        $xml = '<root>';
        foreach ($pages as $i => $article)
        {
            $args = array();
            $t = $article->getTitle()->getText();
            $c = $this->preprocess($article);
            $args['index']         = $i;
            $args['number']        = $i+1;
            $args['odd']           = $i&1 ? 0 : 1;
            $args['title_full']    = $article->getTitle()->getPrefixedText();
            $args['ns_'.$article->getTitle()->getNamespace()] = 1;
            $args['title']         = $t;
            $args['title_rel']     = substr($t, strlen($this->parent));
            $args['title_last']    = strrpos($t, '/') !== false ? substr($t, strrpos($t, '/')+1) : $t;
            $args['title_last']    = preg_replace('#\.jpg#is', '', $args['title_last']);
            $args['content']       = $c;
            $args['first_section'] = $this->parser->getSection($c, 0);
            $args['has_more']      = $this->parser->getSection($c, 1) ? 1 : 0;
            $xml .= '<template><title>'.$this->template.'</title>';
            foreach ($args as $k => $v)
                $xml .= '<part><name>'.htmlspecialchars($k).'</name>=<value>'.htmlspecialchars($v).'</value></part>';
            $xml .= '</template>';
        }
        $xml .= '</root>';
        $dom = new DOMDocument;
        $result = $dom->loadXML($xml);
        if (!$result)
        {
            $this->error('Cannot build templatized list.');
            return '';
        }
        $this->parser->setOutputType(OT_HTML);
        $d = $this->parser->getPreprocessor()->newFrame()->expand($dom);
        return $d;
    }

    /**
     * Wrapper function parse, calls parser function parse
     * @param string $text the content
     * @return string the parsed output
     */
    function parse($text)
    {
        wfProfileIn(__METHOD__);
        $options = $this->parser->mOptions;
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
    function preprocess($article)
    {
        wfProfileIn(__METHOD__);
        $this->parser->clearState();
        $this->parser->setOutputType(Parser::OT_PREPROCESS);
        $this->parser->setTitle($article->getTitle());
        $this->parser->mRevisionId = $article->getRevIdFetched();
        $this->parser->mRevisionTimestamp = $article->getTimestamp();
        $text = $article->getContent();
        wfRunHooks('ParserBeforeStrip', array(&$this->parser, &$text, &$this->parser->mStripState));
        wfRunHooks('ParserAfterStrip', array(&$this->parser, &$text, &$this->parser->mStripState));
        $frame = $this->parser->getPreprocessor()->newFrame();
        $dom = $this->parser->preprocessToDom($text, Parser::PTD_FOR_INCLUSION);
        $text = $frame->expand($dom);
        $text = $this->parser->mStripState->unstripBoth($text);
        wfProfileOut(__METHOD__);
        return $text;
    }
}

?>
