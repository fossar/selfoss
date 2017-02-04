<?php

namespace Readability;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Arc90's Readability ported to PHP for FiveFilters.org
 * Based on readability.js version 1.7.1 (without multi-page support)
 * ------------------------------------------------------
 * Original URL: http://lab.arc90.com/experiments/readability/js/readability.js
 * Arc90's project URL: http://lab.arc90.com/experiments/readability/
 * JS Source: http://code.google.com/p/arc90labs-readability
 * Ported by: Keyvan Minoukadeh, http://www.keyvan.net
 * Modded by: Dither, https://dithersky.wordpress.com
 * More information: http://fivefilters.org/content-only/
 * License: Apache License, Version 2.0
 * Requires: PHP version 5.2.0+
 * Date: 2013-08-02.
 *
 * Differences between the PHP port and the original
 * ------------------------------------------------------
 * Arc90's Readability is designed to run in the browser. It works on the DOM
 * tree (the parsed HTML) after the page's CSS styles have been applied and
 * Javascript code executed. This PHP port does not run inside a browser.
 * We use PHP's ability to parse HTML to build our DOM tree, but we cannot
 * rely on CSS or Javascript support. As such, the results will not always
 * match Arc90's Readability. (For example, if a web page contains CSS style
 * rules or Javascript code which hide certain HTML elements from display,
 * Arc90's Readability will dismiss those from consideration but our PHP port,
 * unable to understand CSS or Javascript, will not know any better.)
 *
 * Another significant difference is that the aim of Arc90's Readability is
 * to re-present the main content block of a given web page so users can
 * read it more easily in their browsers. Correct identification, clean up,
 * and separation of the content block is only a part of this process.
 * This PHP port is only concerned with this part, it does not include code
 * that relates to presentation in the browser - Arc90 already do
 * that extremely well, and for PDF output there's FiveFilters.org's
 * PDF Newspaper: http://fivefilters.org/pdf-newspaper/.
 *
 * Finally, this class contains methods that might be useful for developers
 * working on HTML document fragments. So without deviating too much from
 * the original code (which I don't want to do because it makes debugging
 * and updating more difficult), I've tried to make it a little more
 * developer friendly. You should be able to use the methods here on
 * existing DOMElement objects without passing an entire HTML document to
 * be parsed.
 */
class Readability implements LoggerAwareInterface
{
    public $convertLinksToFootnotes = false;
    public $revertForcedParagraphElements = true;
    public $articleTitle;
    public $articleContent;
    public $original_html;
    public $dom;
    // optional - URL where HTML was retrieved
    public $url = null;
    // preserves more content (experimental)
    public $lightClean = true;
    // no more used, keept to avoid BC
    public $debug = false;
    public $tidied = false;
    // article domain regexp for calibration
    protected $domainRegExp = null;
    protected $body = null;
    // Cache the body HTML in case we need to re-use it later
    protected $bodyCache = null;
    // 1 | 2 | 4;   // Start with all processing flags set.
    protected $flags = 7;
    // indicates whether we were able to extract or not
    protected $success = false;
    protected $logger;
    protected $parser;
    protected $html;
    protected $useTidy;

    /**
     * All of the regular expressions in use within readability.
     * Defined up here so we don't instantiate them repeatedly in loops.
     */
    public $regexps = array(
        'unlikelyCandidates' => '/display\s*:\s*none|ignore|\binfos?\b|annoy|clock|date|time|author|intro|links|hidd?e|about|archive|\bprint|bookmark|tags|tag-list|share|search|social|robot|published|combx|comment|mast(?:head)|subscri|community|category|disqus|extra|head|head(?:er|note)|floor|foot(?:er|note)|menu|tool\b|function|nav|remark|rss|shoutbox|widget|meta|banner|sponsor|adsense|inner-?ad|ad-|sponsor|\badv\b|\bads\b|agr?egate?|pager|sidebar|popup|tweet|twitter/i',
        'okMaybeItsACandidate' => '/article\b|contain|\bcontent|column|general|detail|shadow|lightbox|blog|body|entry|main|page/i',
        'positive' => '/read|full|article|body|\bcontent|contain|entry|main|markdown|page|attach|pagination|post|text|blog|story/i',
        'negative' => '/bottom|stat|info|discuss|e[\-]?mail|comment|reply|log.{2}(n|ed)|sign|single|combx|com-|contact|_nav|link|media|\bout|promo|\bad-|related|scroll|shoutbox|sidebar|sponsor|shopping|teaser|recommend/i',
        'divToPElements' => '/<(?:blockquote|header|section|code|div|article|footer|aside|img|p|pre|dl|ol|ul)/mi',
        'killBreaks' => '/(<br\s*\/?>([ \r\n\s]|&nbsp;?)*)+/',
        'media' => '!//(?:[^\.\?/]+\.)?(?:youtu(?:be)?|soundcloud|dailymotion|vimeo|pornhub|xvideos|twitvid|rutube|viddler)\.(?:com|be|org|net)/!i',
        'skipFootnoteLink' => '/^\s*(\[?[a-z0-9]{1,2}\]?|^|edit|citation needed)\s*$/i',
    );
    public $tidy_config = array(
        'tidy-mark' => false,
        'vertical-space' => false,
        'doctype' => 'omit',
        'numeric-entities' => false,
        // 'preserve-entities' => true,
        'break-before-br' => false,
        'clean' => true,
        'output-xhtml' => true,
        'logical-emphasis' => true,
        'show-body-only' => false,
        'new-blocklevel-tags' => 'article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
        'new-empty-tags' => 'command embed keygen source track wbr',
        'new-inline-tags' => 'audio command datalist embed keygen mark menuitem meter output progress source time video wbr',
        'wrap' => 0,
        'drop-empty-paras' => true,
        'drop-proprietary-attributes' => false,
        'enclose-text' => true,
        'enclose-block-text' => true,
        'merge-divs' => true,
        // 'merge-spans' => true,
        'input-encoding' => '????',
        'output-encoding' => 'utf8',
        'hide-comments' => true,
    );
    // raw HTML filters
    protected $pre_filters = array(
        // remove obvious scripts
        '!<script[^>]*>(.*?)</script>!is' => '',
        // remove obvious styles
        '!<style[^>]*>(.*?)</style>!is' => '',
        // remove spans as we redefine styles and they're probably special-styled
        '!</?span[^>]*>!is' => '',
        // HACK: firewall-filtered content
        '!<font[^>]*>\s*\[AD\]\s*</font>!is' => '',
        // HACK: replace linebreaks plus br's with p's
        '!(<br[^>]*>[ \r\n\s]*){2,}!i' => '</p><p>',
        // replace noscripts
        //'!</?noscript>!is' => '',
        // replace fonts to spans
        '!<(/?)font[^>]*>!is' => '<\\1span>',
    );
    // output HTML filters
    protected $post_filters = array(
        // replace excessive br's
        '/<br\s*\/?>\s*<p/i' => '<p',
        // replace empty tags that break layouts
        '!<(?:a|div|p)[^>]+/>!is' => '',
        // remove all attributes on text tags
        //'!<(\s*/?\s*(?:blockquote|br|hr|code|div|article|span|footer|aside|p|pre|dl|li|ul|ol)) [^>]+>!is' => "<\\1>",
        //single newlines cleanup
        "/\n+/" => "\n",
        // modern web...
        '!<pre[^>]*>\s*<code!is' => '<pre',
        '!</code>\s*</pre>!is' => '</pre>',
        '!<[hb]r>!is' => '<\\1 />',
    );

    // flags
    const FLAG_STRIP_UNLIKELYS = 1;
    const FLAG_WEIGHT_ATTRIBUTES = 2;
    const FLAG_CLEAN_CONDITIONALLY = 4;
    const FLAG_DISABLE_PREFILTER = 8;
    const FLAG_DISABLE_POSTFILTER = 16;
    // constants
    const SCORE_CHARS_IN_PARAGRAPH = 100;
    const SCORE_WORDS_IN_PARAGRAPH = 20;
    const GRANDPARENT_SCORE_DIVISOR = 2.2;
    const MIN_PARAGRAPH_LENGTH = 20;
    const MIN_COMMAS_IN_PARAGRAPH = 6;
    const MIN_ARTICLE_LENGTH = 200;
    const MIN_NODE_LENGTH = 80;
    const MAX_LINK_DENSITY = 0.25;

    /**
     * Create instance of Readability.
     *
     * @param string UTF-8 encoded string
     * @param string (optional) URL associated with HTML (for footnotes)
     * @param string (optional) Which parser to use for turning raw HTML into a DOMDocument
     * @param bool (optional) Use tidy
     */
    public function __construct($html, $url = null, $parser = 'libxml', $use_tidy = true)
    {
        $this->url = $url;
        $this->html = $html;
        $this->parser = $parser;
        $this->useTidy = $use_tidy && function_exists('tidy_parse_string');

        $this->logger = new NullLogger();
        $this->loadHtml();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get article title element.
     *
     * @return \DOMElement
     */
    public function getTitle()
    {
        return $this->articleTitle;
    }

    /**
     * Get article content element.
     *
     * @return \DOMElement
     */
    public function getContent()
    {
        return $this->articleContent;
    }

    /**
     * Add pre filter for raw input HTML processing.
     *
     * @param string RegExp for replace
     * @param string (optional) Replacer
     */
    public function addPreFilter($filter, $replacer = '')
    {
        $this->pre_filters[$filter] = $replacer;
    }

    /**
     * Add post filter for raw output HTML processing.
     *
     * @param string RegExp for replace
     * @param string (optional) Replacer
     */
    public function addPostFilter($filter, $replacer = '')
    {
        $this->post_filters[$filter] = $replacer;
    }

    /**
     * Load HTML in a DOMDocument.
     * Apply Pre filters
     * Cleanup HTML using Tidy (or not).
     *
     * @todo This should be called in init() instead of from __construct
     */
    private function loadHtml()
    {
        $this->original_html = $this->html;

        $this->logger->debug('Parsing URL: ' . $this->url);

        if ($this->url) {
            $this->domainRegExp = '/' . strtr(preg_replace('/www\d*\./', '', parse_url($this->url, PHP_URL_HOST)), array('.' => '\.')) . '/';
        }

        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');
        mb_regex_encoding('UTF-8');

        // HACK: dirty cleanup to replace some stuff; shouldn't use regexps with HTML but well...
        if (!$this->flagIsActive(self::FLAG_DISABLE_PREFILTER)) {
            foreach ($this->pre_filters as $search => $replace) {
                $this->html = preg_replace($search, $replace, $this->html);
            }
            unset($search, $replace);
        }

        if (trim($this->html) === '') {
            $this->html = '<html></html>';
        }

        /*
         * Use tidy (if it exists).
         * This fixes problems with some sites which would otherwise trouble DOMDocument's HTML parsing.
         * Although sometimes it makes matters worse, which is why there is an option to disable it.
         */
        if ($this->useTidy) {
            $this->logger->debug('Tidying document');

            $tidy = tidy_parse_string($this->html, $this->tidy_config, 'UTF8');
            if (tidy_clean_repair($tidy)) {
                $this->tidied = true;
                $this->html = $tidy->value;
                $this->html = preg_replace('/[\r\n]+/is', "\n", $this->html);
            }
            unset($tidy);
        }

        $this->html = mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8');

        if (!($this->parser === 'html5lib' && ($this->dom = \HTML5_Parser::parse($this->html)))) {
            libxml_use_internal_errors(true);

            $this->dom = new \DOMDocument();
            $this->dom->preserveWhiteSpace = false;

            if (PHP_VERSION_ID >= 50400) {
                $this->dom->loadHTML($this->html, LIBXML_NOBLANKS | LIBXML_COMPACT | LIBXML_NOERROR);
            } else {
                $this->dom->loadHTML($this->html);
            }

            libxml_use_internal_errors(false);
        }

        $this->dom->registerNodeClass('DOMElement', 'Readability\JSLikeHTMLElement');
    }

    /**
     * Runs readability.
     *
     * Workflow:
     *  1. Prep the document by removing script tags, css, etc.
     *  2. Build readability's DOM tree.
     *  3. Grab the article content from the current dom tree.
     *  4. Replace the current DOM tree with the new one.
     *  5. Read peacefully.
     *
     * @return bool true if we found content, false otherwise
     */
    public function init()
    {
        if (!isset($this->dom->documentElement)) {
            return false;
        }

        // Assume successful outcome
        $this->success = true;
        $bodyElems = $this->dom->getElementsByTagName('body');

        // WTF multiple body nodes?
        if ($this->bodyCache === null) {
            $this->bodyCache = '';
            foreach ($bodyElems as $bodyNode) {
                $this->bodyCache .= trim($bodyNode->innerHTML);
            }
        }

        if ($bodyElems->length > 0 && $this->body === null) {
            $this->body = $bodyElems->item(0);
        }

        $this->prepDocument();

        // Build readability's DOM tree.
        $overlay = $this->dom->createElement('div');
        $innerDiv = $this->dom->createElement('div');
        $articleTitle = $this->getArticleTitle();
        $articleContent = $this->grabArticle();

        if (!$articleContent) {
            $this->success = false;
            $articleContent = $this->dom->createElement('div');
            $articleContent->setAttribute('class', 'readability-content');
            $articleContent->innerHTML = '<p>Sorry, Readability was unable to parse this page for content.</p>';
        }

        $overlay->setAttribute('class', 'readOverlay');
        $innerDiv->setAttribute('class', 'readInner');

        // Glue the structure of our document together.
        $innerDiv->appendChild($articleTitle);
        $innerDiv->appendChild($articleContent);
        $overlay->appendChild($innerDiv);

        // Clear the old HTML, insert the new content.
        $this->body->innerHTML = '';
        $this->body->appendChild($overlay);
        $this->body->removeAttribute('style');
        $this->postProcessContent($articleContent);

        // Set title and content instance variables.
        $this->articleTitle = $articleTitle;
        $this->articleContent = $articleContent;

        return $this->success;
    }

    /**
     * Debug.
     *
     * @deprecated use $this->logger->debug() instead
     * @codeCoverageIgnore
     */
    protected function dbg($msg)
    {
        $this->logger->debug($msg);
    }

    /**
     * Dump debug info.
     *
     * @deprecated since Monolog gather log, we don't need it
     * @codeCoverageIgnore
     */
    protected function dump_dbg()
    {
    }

    /**
     * Run any post-process modifications to article content as necessary.
     *
     * @param \DOMElement $articleContent
     */
    public function postProcessContent($articleContent)
    {
        if ($this->convertLinksToFootnotes && !preg_match('/\bwiki/', $this->url)) {
            $this->addFootnotes($articleContent);
        }
    }

    /**
     * Get the article title as an H1.
     *
     * @return \DOMElement
     */
    protected function getArticleTitle()
    {
        try {
            $curTitle = $origTitle = $this->getInnerText($this->dom->getElementsByTagName('title')->item(0));
        } catch (\Exception $e) {
            $curTitle = '';
            $origTitle = '';
        }

        if (preg_match('/ [\|\-] /', $curTitle)) {
            $curTitle = preg_replace('/(.*)[\|\-] .*/i', '$1', $origTitle);
            if (count(explode(' ', $curTitle)) < 3) {
                $curTitle = preg_replace('/[^\|\-]*[\|\-](.*)/i', '$1', $origTitle);
            }
        } elseif (strpos($curTitle, ': ') !== false) {
            $curTitle = preg_replace('/.*:(.*)/i', '$1', $origTitle);
            if (count(explode(' ', $curTitle)) < 3) {
                $curTitle = preg_replace('/[^:]*[:](.*)/i', '$1', $origTitle);
            }
        } elseif (mb_strlen($curTitle) > 150 || mb_strlen($curTitle) < 15) {
            $hOnes = $this->dom->getElementsByTagName('h1');
            if ($hOnes->length === 1) {
                $curTitle = $this->getInnerText($hOnes->item(0));
            }
        }

        $curTitle = trim($curTitle);
        if (count(explode(' ', $curTitle)) <= 4) {
            $curTitle = $origTitle;
        }

        $articleTitle = $this->dom->createElement('h1');
        $articleTitle->innerHTML = $curTitle;

        return $articleTitle;
    }

    /**
     * Prepare the HTML document for readability to scrape it.
     * This includes things like stripping javascript, CSS, and handling terrible markup.
     */
    protected function prepDocument()
    {
        /*
         * In some cases a body element can't be found (if the HTML is totally hosed for example)
         * so we create a new body node and append it to the document.
         */
        if ($this->body === null) {
            $this->body = $this->dom->createElement('body');
            $this->dom->documentElement->appendChild($this->body);
        }

        $this->body->setAttribute('class', 'readabilityBody');

        // Remove all style tags in head.
        $styleTags = $this->dom->getElementsByTagName('style');
        for ($i = $styleTags->length - 1; $i >= 0; --$i) {
            $styleTags->item($i)->parentNode->removeChild($styleTags->item($i));
        }

        $linkTags = $this->dom->getElementsByTagName('link');
        for ($i = $linkTags->length - 1; $i >= 0; --$i) {
            $linkTags->item($i)->parentNode->removeChild($linkTags->item($i));
        }
    }

    /**
     * For easier reading, convert this document to have footnotes at the bottom rather than inline links.
     *
     * @see http://www.roughtype.com/archives/2010/05/experiments_in.php
     *
     * @param \DOMElement $articleContent
     */
    public function addFootnotes($articleContent)
    {
        $footnotesWrapper = $this->dom->createElement('footer');
        $footnotesWrapper->setAttribute('class', 'readability-footnotes');
        $footnotesWrapper->innerHTML = '<h3>References</h3>';
        $articleFootnotes = $this->dom->createElement('ol');
        $articleFootnotes->setAttribute('class', 'readability-footnotes-list');
        $footnotesWrapper->appendChild($articleFootnotes);
        $articleLinks = $articleContent->getElementsByTagName('a');
        $linkCount = 0;

        for ($i = 0; $i < $articleLinks->length; ++$i) {
            $articleLink = $articleLinks->item($i);
            $footnoteLink = $articleLink->cloneNode(true);
            $refLink = $this->dom->createElement('a');
            $footnote = $this->dom->createElement('li');
            $linkDomain = @parse_url($footnoteLink->getAttribute('href'), PHP_URL_HOST);
            if (!$linkDomain && isset($this->url)) {
                $linkDomain = @parse_url($this->url, PHP_URL_HOST);
            }

            $linkText = $this->getInnerText($articleLink);
            if ((strpos($articleLink->getAttribute('class'), 'readability-DoNotFootnote') !== false) || preg_match($this->regexps['skipFootnoteLink'], $linkText)) {
                continue;
            }

            ++$linkCount;

            // Add a superscript reference after the article link.
            $refLink->setAttribute('href', '#readabilityFootnoteLink-' . $linkCount);
            $refLink->innerHTML = '<small><sup>[' . $linkCount . ']</sup></small>';
            $refLink->setAttribute('class', 'readability-DoNotFootnote');
            $refLink->setAttribute('style', 'color: inherit;');

            if ($articleLink->parentNode->lastChild->isSameNode($articleLink)) {
                $articleLink->parentNode->appendChild($refLink);
            } else {
                $articleLink->parentNode->insertBefore($refLink, $articleLink->nextSibling);
            }

            $articleLink->setAttribute('style', 'color: inherit; text-decoration: none;');
            $articleLink->setAttribute('name', 'readabilityLink-' . $linkCount);
            $footnote->innerHTML = '<small><sup><a href="#readabilityLink-' . $linkCount . '" title="Jump to Link in Article">^</a></sup></small> ';
            $footnoteLink->innerHTML = ($footnoteLink->getAttribute('title') !== '' ? $footnoteLink->getAttribute('title') : $linkText);
            $footnoteLink->setAttribute('name', 'readabilityFootnoteLink-' . $linkCount);
            $footnote->appendChild($footnoteLink);

            if ($linkDomain) {
                $footnote->innerHTML = $footnote->innerHTML . '<small> (' . $linkDomain . ')</small>';
            }
            $articleFootnotes->appendChild($footnote);
        }

        if ($linkCount > 0) {
            $articleContent->appendChild($footnotesWrapper);
        }
    }

    /**
     * Prepare the article node for display. Clean out any inline styles,
     * iframes, forms, strip extraneous <p> tags, etc.
     *
     * @param \DOMElement $articleContent
     */
    public function prepArticle($articleContent)
    {
        $this->logger->debug($this->lightClean ? 'Light clean enabled.' : 'Standard clean enabled.');

        $this->cleanStyles($articleContent);
        $this->killBreaks($articleContent);

        $xpath = new \DOMXPath($articleContent->ownerDocument);

        if ($this->revertForcedParagraphElements) {
            /*
             * Reverts P elements with class 'readability-styled' to text nodes:
             * which is what they were before.
             */
            $elems = $xpath->query('.//p[@data-readability-styled]', $articleContent);
            for ($i = $elems->length - 1; $i >= 0; --$i) {
                $e = $elems->item($i);
                $e->parentNode->replaceChild($articleContent->ownerDocument->createTextNode($e->textContent), $e);
            }
        }

        // Remove service data-candidate attribute.
        $elems = $xpath->query('.//*[@data-candidate]', $articleContent);
        for ($i = $elems->length - 1; $i >= 0; --$i) {
            $elems->item($i)->removeAttribute('data-candidate');
        }

        // Clean out junk from the article content.
        $this->clean($articleContent, 'input');
        $this->clean($articleContent, 'button');
        $this->clean($articleContent, 'nav');
        $this->clean($articleContent, 'object');
        $this->clean($articleContent, 'iframe');
        $this->clean($articleContent, 'canvas');
        $this->clean($articleContent, 'h1');

        /*
         * If there is only one h2, they are probably using it as a main header, so remove it since we
         *  already have a header.
         */
        $h2s = $articleContent->getElementsByTagName('h2');
        if ($h2s->length === 1 && mb_strlen($this->getInnerText($h2s->item(0), true, true)) < 100) {
            $this->clean($articleContent, 'h2');
        }

        $this->cleanHeaders($articleContent);

        // Do these last as the previous stuff may have removed junk that will affect these.
        $this->cleanConditionally($articleContent, 'form');
        $this->cleanConditionally($articleContent, 'table');
        $this->cleanConditionally($articleContent, 'ul');
        $this->cleanConditionally($articleContent, 'div');

        // Remove extra paragraphs.
        $articleParagraphs = $articleContent->getElementsByTagName('p');

        for ($i = $articleParagraphs->length - 1; $i >= 0; --$i) {
            $item = $articleParagraphs->item($i);

            $imgCount = $item->getElementsByTagName('img')->length;
            $embedCount = $item->getElementsByTagName('embed')->length;
            $objectCount = $item->getElementsByTagName('object')->length;
            $videoCount = $item->getElementsByTagName('video')->length;
            $audioCount = $item->getElementsByTagName('audio')->length;
            $iframeCount = $item->getElementsByTagName('iframe')->length;

            if ($iframeCount === 0 && $imgCount === 0 && $embedCount === 0 && $objectCount === 0 && $videoCount === 0 && $audioCount === 0 && mb_strlen(preg_replace('/\s+/is', '', $this->getInnerText($item, false, false))) === 0) {
                $item->parentNode->removeChild($item);
            }

            // add extra text to iframe tag to avoid an auto-closing iframe and then break the html code
            if ($iframeCount) {
                $iframe = $item->getElementsByTagName('iframe');
                $iframe->item(0)->nodeValue = ' ';

                $item->parentNode->replaceChild($iframe->item(0), $item);
            }
        }

        if (!$this->flagIsActive(self::FLAG_DISABLE_POSTFILTER)) {
            try {
                foreach ($this->post_filters as $search => $replace) {
                    $articleContent->innerHTML = preg_replace($search, $replace, $articleContent->innerHTML);
                }
                unset($search, $replace);
            } catch (\Exception $e) {
                $this->logger->error('Cleaning output HTML failed. Ignoring: ' . $e->getMessage());
            }
        }
    }

    /**
     * Initialize a node with the readability object. Also checks the
     * className/id for special names to add to its score.
     *
     * @param \DOMElement $node
     */
    protected function initializeNode($node)
    {
        if (!isset($node->tagName)) {
            return;
        }

        $readability = $this->dom->createAttribute('readability');
        // this is our contentScore
        $readability->value = 0;
        $node->setAttributeNode($readability);

        // using strtoupper just in case
        switch (strtoupper($node->tagName)) {
            case 'ARTICLE':
                $readability->value += 15;
            case 'DIV':
                $readability->value += 5;
                break;
            case 'PRE':
            case 'CODE':
            case 'TD':
            case 'BLOCKQUOTE':
            case 'FIGURE':
                $readability->value += 3;
                break;
            case 'SECTION':
                // often misused
                // $readability->value += 2;
                break;
            case 'OL':
            case 'UL':
            case 'DL':
            case 'DD':
            case 'DT':
            case 'LI':
                $readability->value -= 2 * round($this->getLinkDensity($node), 0, PHP_ROUND_HALF_UP);
                break;
            case 'ASIDE':
            case 'FOOTER':
            case 'HEADER':
            case 'ADDRESS':
            case 'FORM':
            case 'BUTTON':
            case 'TEXTAREA':
            case 'INPUT':
            case 'NAV':
                $readability->value -= 3;
                break;
            case 'H1':
            case 'H2':
            case 'H3':
            case 'H4':
            case 'H5':
            case 'H6':
            case 'TH':
            case 'HGROUP':
                $readability->value -= 5;
                break;
        }

        $readability->value += $this->getWeight($node);
    }

    /**
     * Using a variety of metrics (content score, classname, element types), find the content that is
     * most likely to be the stuff a user wants to read. Then return it wrapped up in a div.
     *
     * @param \DOMElement $page
     *
     * @return \DOMElement|bool
     */
    protected function grabArticle($page = null)
    {
        if (!$page) {
            $page = $this->dom;
        }

        $xpath = null;
        $nodesToScore = array();

        if ($page instanceof \DOMDocument && isset($page->documentElement)) {
            $xpath = new \DOMXPath($page);
        }

        $allElements = $page->getElementsByTagName('*');

        for ($nodeIndex = 0; ($node = $allElements->item($nodeIndex)); ++$nodeIndex) {
            $tagName = $node->tagName;

            // Some well known site uses sections as paragraphs.
            if (strcasecmp($tagName, 'p') === 0 || strcasecmp($tagName, 'td') === 0 || strcasecmp($tagName, 'pre') === 0 || strcasecmp($tagName, 'section') === 0) {
                $nodesToScore[] = $node;
            }

            // Turn divs into P tags where they have been used inappropriately
            //  (as in, where they contain no other block level elements).
            if (strcasecmp($tagName, 'div') === 0 || strcasecmp($tagName, 'article') === 0 || strcasecmp($tagName, 'section') === 0) {
                if (!preg_match($this->regexps['divToPElements'], $node->innerHTML)) {
                    $newNode = $this->dom->createElement('p');

                    try {
                        $newNode->innerHTML = $node->innerHTML;

                        $node->parentNode->replaceChild($newNode, $node);
                        --$nodeIndex;
                        $nodesToScore[] = $newNode;
                    } catch (\Exception $e) {
                        $this->logger->error('Could not alter div/article to p, reverting back to div: ' . $e->getMessage());
                    }
                } else {
                    // Will change these P elements back to text nodes after processing.
                    for ($i = 0, $il = $node->childNodes->length; $i < $il; ++$i) {
                        $childNode = $node->childNodes->item($i);

                        // it looks like sometimes the loop is going too far and we are retrieving a non-existant child
                        if (null === $childNode) {
                            continue;
                        }

                        // executable tags (<?php or <?xml) warning
                        if (is_object($childNode) && get_class($childNode) === 'DOMProcessingInstruction') {
                            $childNode->parentNode->removeChild($childNode);

                            continue;
                        }

                        if ($childNode->nodeType === XML_TEXT_NODE) {
                            $p = $this->dom->createElement('p');
                            $p->innerHTML = $childNode->nodeValue;
                            $p->setAttribute('data-readability-styled', 'true');
                            $childNode->parentNode->replaceChild($p, $childNode);
                        }
                    }
                }
            }
        }

        /*
         * Loop through all paragraphs, and assign a score to them based on how content-y they look.
         * Then add their score to their parent node.
         *
         * A score is determined by things like number of commas, class names, etc.
         * Maybe eventually link density.
         */
        for ($pt = 0, $scored = count($nodesToScore); $pt < $scored; ++$pt) {
            $parentNode = $nodesToScore[$pt]->parentNode;

            // No parent node? Move on...
            if (!$parentNode) {
                continue;
            }

            $grandParentNode = $parentNode->parentNode instanceof \DOMElement ? $parentNode->parentNode : null;
            $innerText = $this->getInnerText($nodesToScore[$pt]);

            // If this paragraph is less than MIN_PARAGRAPH_LENGTH (default:20) characters, don't even count it.
            if (mb_strlen($innerText) < self::MIN_PARAGRAPH_LENGTH) {
                continue;
            }

            // Initialize readability data for the parent.
            if (!$parentNode->hasAttribute('readability')) {
                $this->initializeNode($parentNode);
                $parentNode->setAttribute('data-candidate', 'true');
            }

            // Initialize readability data for the grandparent.
            if ($grandParentNode && !$grandParentNode->hasAttribute('readability') && isset($grandParentNode->tagName)) {
                $this->initializeNode($grandParentNode);
                $grandParentNode->setAttribute('data-candidate', 'true');
            }
            // Add a point for the paragraph itself as a base.
            $contentScore = 1;
            // Add points for any commas within this paragraph.
            $contentScore += $this->getCommaCount($innerText);
            // For every SCORE_CHARS_IN_PARAGRAPH (default:100) characters in this paragraph, add another point. Up to 3 points.
            $contentScore += min(floor(mb_strlen($innerText) / self::SCORE_CHARS_IN_PARAGRAPH), 3);
            // For every SCORE_WORDS_IN_PARAGRAPH (default:20) words in this paragraph, add another point. Up to 3 points.
            $contentScore += min(floor($this->getWordCount($innerText) / self::SCORE_WORDS_IN_PARAGRAPH), 3);
            /* TEST: For every positive/negative parent tag, add/substract half point. Up to 3 points. *\/
            $up = $nodesToScore[$pt];
            $score = 0;
            while ($up->parentNode instanceof \DOMElement) {
                $up = $up->parentNode;
                if (preg_match($this->regexps['positive'], $up->getAttribute('class') . ' ' . $up->getAttribute('id'))) {
                    $score += 0.5;
                } elseif (preg_match($this->regexps['negative'], $up->getAttribute('class') . ' ' . $up->getAttribute('id'))) {
                    $score -= 0.5;
                }
            }
            $score = floor($score);
            $contentScore += max(min($score, 3), -3);/**/

            // Add the score to the parent. The grandparent gets half.
            $parentNode->getAttributeNode('readability')->value += $contentScore;
            if ($grandParentNode) {
                $grandParentNode->getAttributeNode('readability')->value += $contentScore / self::GRANDPARENT_SCORE_DIVISOR;
            }
        }

        /*
         * Node prepping: trash nodes that look cruddy (like ones with the class name "comment", etc).
         * This is faster to do before scoring but safer after.
         */
        if ($this->flagIsActive(self::FLAG_STRIP_UNLIKELYS) && $xpath) {
            $candidates = $xpath->query('.//*[(self::footer and count(//footer)<2) or (self::aside and count(//aside)<2)]', $page->documentElement);
            $node = null;

            for ($c = $candidates->length - 1; $c >= 0; --$c) {
                $node = $candidates->item($c);
                // node should be readable but not inside of an article otherwise it's probably non-readable block
                if ($node->hasAttribute('readability') && (int) $node->getAttributeNode('readability')->value < 40 && ($node->parentNode ? strcasecmp($node->parentNode->tagName, 'article') !== 0 : true)) {
                    $this->logger->debug('Removing unlikely candidate (using note) ' . $node->getNodePath() . ' by "' . $node->tagName . '" with readability ' . ($node->hasAttribute('readability') ? (int) $node->getAttributeNode('readability')->value : 0));
                    $node->parentNode->removeChild($node);
                }
            }

            $candidates = $xpath->query('.//*[not(self::body) and (@class or @id or @style) and ((number(@readability) < 40) or not(@readability))]', $page->documentElement);
            $node = null;

            for ($c = $candidates->length - 1; $c >= 0; --$c) {
                $node = $candidates->item($c);

                // Remove unlikely candidates
                $unlikelyMatchString = $node->getAttribute('class') . ' ' . $node->getAttribute('id') . ' ' . $node->getAttribute('style');

                if (mb_strlen($unlikelyMatchString) > 3 && // don't process "empty" strings
                    preg_match($this->regexps['unlikelyCandidates'], $unlikelyMatchString) &&
                    !preg_match($this->regexps['okMaybeItsACandidate'], $unlikelyMatchString)
                ) {
                    $this->logger->debug('Removing unlikely candidate (using conf) ' . $node->getNodePath() . ' by "' . $unlikelyMatchString . '" with readability ' . ($node->hasAttribute('readability') ? (int) $node->getAttributeNode('readability')->value : 0));
                    $node->parentNode->removeChild($node);
                    --$nodeIndex;
                }
            }
            unset($candidates);
        }

        /*
         * After we've calculated scores, loop through all of the possible candidate nodes we found
         * and find the one with the highest score.
         */
        $topCandidate = null;
        if ($xpath) {
            // Using array of DOMElements after deletion is a path to DOOMElement.
            $candidates = $xpath->query('.//*[@data-candidate]', $page->documentElement);

            for ($c = $candidates->length - 1; $c >= 0; --$c) {
                $item = $candidates->item($c);

                // Scale the final candidates score based on link density. Good content should have a
                // relatively small link density (5% or less) and be mostly unaffected by this operation.
                // If not for this we would have used XPath to find maximum @readability.
                $readability = $item->getAttributeNode('readability');
                $readability->value = round($readability->value * (1 - $this->getLinkDensity($item)), 0, PHP_ROUND_HALF_UP);

                if (!$topCandidate || $readability->value > (int) $topCandidate->getAttribute('readability')) {
                    $this->logger->debug('Candidate: ' . $item->getNodePath() . ' (' . $item->getAttribute('class') . ':' . $item->getAttribute('id') . ') with score ' . $readability->value);
                    $topCandidate = $item;
                }
            }

            unset($candidates);
        }

        /*
         * If we still have no top candidate, just use the body as a last resort.
         * We also have to copy the body node so it is something we can modify.
         */
        if ($topCandidate === null || strcasecmp($topCandidate->tagName, 'body') === 0) {
            $topCandidate = $this->dom->createElement('div');

            if ($page instanceof \DOMDocument) {
                if (!isset($page->documentElement)) {
                    // we don't have a body either? what a mess! :)
                    $this->logger->debug('The page has no body!');
                } else {
                    $this->logger->debug('Setting body to a raw HTML of original page!');
                    $topCandidate->innerHTML = $page->documentElement->innerHTML;
                    $page->documentElement->innerHTML = '';
                    $this->reinitBody();
                    $page->documentElement->appendChild($topCandidate);
                }
            } else {
                $topCandidate->innerHTML = $page->innerHTML;
                $page->innerHTML = '';
                $page->appendChild($topCandidate);
            }

            $this->initializeNode($topCandidate);
        }

        // Set table as the main node if resulted data is table element.
        $tagName = $topCandidate->tagName;
        if (strcasecmp($tagName, 'td') === 0 || strcasecmp($tagName, 'tr') === 0) {
            $up = $topCandidate;

            if ($up->parentNode instanceof \DOMElement) {
                $up = $up->parentNode;

                if (strcasecmp($up->tagName, 'table') === 0) {
                    $topCandidate = $up;
                }
            }
        }

        $this->logger->debug('Top candidate: ' . $topCandidate->getNodePath());

        /*
         * Now that we have the top candidate, look through its siblings for content that might also be related.
         * Things like preambles, content split by ads that we removed, etc.
         */
        $articleContent = $this->dom->createElement('div');
        $articleContent->setAttribute('class', 'readability-content');
        $siblingScoreThreshold = max(10, ((int) $topCandidate->getAttribute('readability')) * 0.2);
        $siblingNodes = $topCandidate->parentNode->childNodes;

        if (!isset($siblingNodes)) {
            $siblingNodes = new stdClass();
            $siblingNodes->length = 0;
        }

        for ($s = 0, $sl = $siblingNodes->length; $s < $sl; ++$s) {
            $siblingNode = $siblingNodes->item($s);
            $siblingNodeName = $siblingNode->nodeName;
            $append = false;
            $this->logger->debug('Looking at sibling node: ' . $siblingNode->getNodePath() . (($siblingNode->nodeType === XML_ELEMENT_NODE && $siblingNode->hasAttribute('readability')) ? (' with score ' . $siblingNode->getAttribute('readability')) : ''));

            if ($siblingNode->isSameNode($topCandidate)) {
                $append = true;
            }

            $contentBonus = 0;

            // Give a bonus if sibling nodes and top candidates have the same classname.
            if ($siblingNode->nodeType === XML_ELEMENT_NODE && $siblingNode->getAttribute('class') === $topCandidate->getAttribute('class') && $topCandidate->getAttribute('class') !== '') {
                $contentBonus += ((int) $topCandidate->getAttribute('readability')) * 0.2;
            }

            if ($siblingNode->nodeType === XML_ELEMENT_NODE && $siblingNode->hasAttribute('readability') && (((int) $siblingNode->getAttribute('readability')) + $contentBonus) >= $siblingScoreThreshold) {
                $append = true;
            }

            if (strcasecmp($siblingNodeName, 'p') === 0) {
                $linkDensity = $this->getLinkDensity($siblingNode);
                $nodeContent = $this->getInnerText($siblingNode, true, true);
                $nodeLength = mb_strlen($nodeContent);

                if (($nodeLength > self::MIN_NODE_LENGTH && $linkDensity < self::MAX_LINK_DENSITY)
                    || ($nodeLength < self::MIN_NODE_LENGTH && $linkDensity === 0 && preg_match('/\.( |$)/', $nodeContent))) {
                    $append = true;
                }
            }

            if ($append) {
                $this->logger->debug('Appending node: ' . $siblingNode->getNodePath());

                if (strcasecmp($siblingNodeName, 'div') !== 0 && strcasecmp($siblingNodeName, 'p') !== 0) {
                    // We have a node that isn't a common block level element, like a form or td tag. Turn it into a div so it doesn't get filtered out later by accident.
                    $this->logger->debug('Altering siblingNode "' . $siblingNodeName . '" to "div".');
                    $nodeToAppend = $this->dom->createElement('div');

                    try {
                        $nodeToAppend->setAttribute('alt', $siblingNodeName);
                        $nodeToAppend->innerHTML = $siblingNode->innerHTML;
                    } catch (\Exception $e) {
                        $this->logger->debug('Could not alter siblingNode "' . $siblingNodeName . '" to "div", reverting to original.');
                        $nodeToAppend = $siblingNode;
                        --$s;
                        --$sl;
                    }
                } else {
                    $nodeToAppend = $siblingNode;
                    --$s;
                    --$sl;
                }

                // To ensure a node does not interfere with readability styles, remove its classnames & ids.
                // Now done via RegExp post_filter.
                //$nodeToAppend->removeAttribute('class');
                //$nodeToAppend->removeAttribute('id');
                // Append sibling and subtract from our list as appending removes a node.
                $articleContent->appendChild($nodeToAppend);
            }
        }

        unset($xpath);

        // So we have all of the content that we need. Now we clean it up for presentation.
        $this->prepArticle($articleContent);

        /*
         * Now that we've gone through the full algorithm, check to see if we got any meaningful content.
         * If we didn't, we may need to re-run grabArticle with different flags set. This gives us a higher
         * likelihood of finding the content, and the sieve approach gives us a higher likelihood of
         * finding the -right- content.
         */
        if (mb_strlen($this->getInnerText($articleContent, false)) < self::MIN_ARTICLE_LENGTH) {
            $this->reinitBody();

            if ($this->flagIsActive(self::FLAG_STRIP_UNLIKELYS)) {
                $this->removeFlag(self::FLAG_STRIP_UNLIKELYS);
                $this->logger->debug('...content is shorter than ' . self::MIN_ARTICLE_LENGTH . " letters, trying not to strip unlikely content.\n");

                return $this->grabArticle($this->body);
            } elseif ($this->flagIsActive(self::FLAG_WEIGHT_ATTRIBUTES)) {
                $this->removeFlag(self::FLAG_WEIGHT_ATTRIBUTES);
                $this->logger->debug('...content is shorter than ' . self::MIN_ARTICLE_LENGTH . " letters, trying not to weight attributes.\n");

                return $this->grabArticle($this->body);
            } elseif ($this->flagIsActive(self::FLAG_CLEAN_CONDITIONALLY)) {
                $this->removeFlag(self::FLAG_CLEAN_CONDITIONALLY);
                $this->logger->debug('...content is shorter than ' . self::MIN_ARTICLE_LENGTH . " letters, trying not to clean at all.\n");

                return $this->grabArticle($this->body);
            }

            return false;
        }

        return $articleContent;
    }

    /**
     * Get the inner text of a node.
     * This also strips out any excess whitespace to be found.
     *
     * @param \DOMElement $e
     * @param bool        $normalizeSpaces (default: true)
     * @param bool        $flattenLines    (default: false)
     *
     * @return string
     */
    public function getInnerText($e, $normalizeSpaces = true, $flattenLines = false)
    {
        if (null === $e || !isset($e->textContent) || $e->textContent === '') {
            return '';
        }

        $textContent = trim($e->textContent);

        if ($flattenLines) {
            $textContent = mb_ereg_replace('(?:[\r\n](?:\s|&nbsp;)*)+', '', $textContent);
        } elseif ($normalizeSpaces) {
            $textContent = mb_ereg_replace('\s\s+', ' ', $textContent);
        }

        return $textContent;
    }

    /**
     * Remove the style attribute on every $e and under.
     *
     * @param \DOMElement $e
     */
    public function cleanStyles($e)
    {
        if (!is_object($e)) {
            return;
        }

        $elems = $e->getElementsByTagName('*');

        foreach ($elems as $elem) {
            $elem->removeAttribute('style');
        }
    }

    /**
     * Get comma number for a given text.
     *
     * @param string $text
     *
     * @return int
     */
    public function getCommaCount($text)
    {
        return substr_count($text, ',');
    }

    /**
     * Get words number for a given text if words separated by a space.
     * Input string should be normalized.
     *
     * @param string $text
     *
     * @return int
     */
    public function getWordCount($text)
    {
        return substr_count($text, ' ');
    }

    /**
     * Get the density of links as a percentage of the content
     * This is the amount of text that is inside a link divided by the total text in the node.
     * Can exclude external references to differentiate between simple text and menus/infoblocks.
     *
     * @param \DOMElement $e
     * @param string      $excludeExternal
     *
     * @return int
     */
    public function getLinkDensity($e, $excludeExternal = false)
    {
        $links = $e->getElementsByTagName('a');
        $textLength = mb_strlen($this->getInnerText($e, true, true));
        $linkLength = 0;

        for ($dRe = $this->domainRegExp, $i = 0, $il = $links->length; $i < $il; ++$i) {
            if ($excludeExternal && $dRe && !preg_match($dRe, $links->item($i)->getAttribute('href'))) {
                continue;
            }
            $linkLength += mb_strlen($this->getInnerText($links->item($i)));
        }

        if ($textLength > 0 && $linkLength > 0) {
            return $linkLength / $textLength;
        }

        return 0;
    }

    /**
     * Get an element weight by attribute.
     * Uses regular expressions to tell if this element looks good or bad.
     *
     * @param \DOMElement $element
     * @param string      $attribute
     *
     * @return int
     */
    protected function weightAttribute($element, $attribute)
    {
        if (!$element->hasAttribute($attribute)) {
            return 0;
        }
        $weight = 0;

        // $attributeValue = trim($element->getAttribute('class')." ".$element->getAttribute('id'));
        $attributeValue = trim($element->getAttribute($attribute));

        if ($attributeValue !== '') {
            if (preg_match($this->regexps['negative'], $attributeValue)) {
                $weight -= 25;
            }
            if (preg_match($this->regexps['positive'], $attributeValue)) {
                $weight += 25;
            }
            if (preg_match($this->regexps['unlikelyCandidates'], $attributeValue)) {
                $weight -= 5;
            }
            if (preg_match($this->regexps['okMaybeItsACandidate'], $attributeValue)) {
                $weight += 5;
            }
        }

        return $weight;
    }

    /**
     * Get an element relative weight.
     *
     * @param \DOMElement $e
     *
     * @return int
     */
    public function getWeight($e)
    {
        if (!$this->flagIsActive(self::FLAG_WEIGHT_ATTRIBUTES)) {
            return 0;
        }

        $weight = 0;
        // Look for a special classname
        $weight += $this->weightAttribute($e, 'class');
        // Look for a special ID
        $weight += $this->weightAttribute($e, 'id');

        return $weight;
    }

    /**
     * Remove extraneous break tags from a node.
     *
     * @param \DOMElement $node
     */
    public function killBreaks($node)
    {
        $html = $node->innerHTML;
        $html = preg_replace($this->regexps['killBreaks'], '<br />', $html);
        $node->innerHTML = $html;
    }

    /**
     * Clean a node of all elements of type "tag".
     * (Unless it's a youtube/vimeo video. People love movies.).
     *
     * Updated 2012-09-18 to preserve youtube/vimeo iframes
     *
     * @param \DOMElement $e
     * @param string      $tag
     */
    public function clean($e, $tag)
    {
        $currentItem = null;
        $targetList = $e->getElementsByTagName($tag);
        $isEmbed = ($tag === 'audio' || $tag === 'video' || $tag === 'iframe' || $tag === 'object' || $tag === 'embed');

        for ($y = $targetList->length - 1; $y >= 0; --$y) {
            // Allow youtube and vimeo videos through as people usually want to see those.
            $currentItem = $targetList->item($y);

            if ($isEmbed) {
                $attributeValues = $currentItem->getAttribute('src') . ' ' . $currentItem->getAttribute('href');

                // First, check the elements attributes to see if any of them contain known media hosts
                if (preg_match($this->regexps['media'], $attributeValues)) {
                    continue;
                }

                // Then check the elements inside this element for the same.
                if (preg_match($this->regexps['media'], $targetList->item($y)->innerHTML)) {
                    continue;
                }
            }

            $currentItem->parentNode->removeChild($currentItem);
        }
    }

    /**
     * Clean an element of all tags of type "tag" if they look fishy.
     * "Fishy" is an algorithm based on content length, classnames,
     * link density, number of images & embeds, etc.
     *
     * @param \DOMElement $e
     * @param string      $tag
     */
    public function cleanConditionally($e, $tag)
    {
        if (!$this->flagIsActive(self::FLAG_CLEAN_CONDITIONALLY)) {
            return;
        }

        $tagsList = $e->getElementsByTagName($tag);
        $curTagsLength = $tagsList->length;
        $node = null;

        /*
         * Gather counts for other typical elements embedded within.
         * Traverse backwards so we can remove nodes at the same time without effecting the traversal.
         *
         * TODO: Consider taking into account original contentScore here.
         */
        for ($i = $curTagsLength - 1; $i >= 0; --$i) {
            $node = $tagsList->item($i);
            $weight = $this->getWeight($node);
            $contentScore = ($node->hasAttribute('readability')) ? (int) $node->getAttribute('readability') : 0;
            $this->logger->debug('Start conditional cleaning of ' . $node->getNodePath() . ' (class=' . $node->getAttribute('class') . '; id=' . $node->getAttribute('id') . ')' . (($node->hasAttribute('readability')) ? (' with score ' . $node->getAttribute('readability')) : ''));

            if ($weight + $contentScore < 0) {
                $this->logger->debug('Removing...');
                $node->parentNode->removeChild($node);
            } elseif ($this->getCommaCount($this->getInnerText($node)) < self::MIN_COMMAS_IN_PARAGRAPH) {
                /*
                 * If there are not very many commas, and the number of
                 * non-paragraph elements is more than paragraphs or other ominous signs, remove the element.
                 */
                $p = $node->getElementsByTagName('p')->length;
                $img = $node->getElementsByTagName('img')->length;
                $li = $node->getElementsByTagName('li')->length - 100;
                $input = $node->getElementsByTagName('input')->length;
                $a = $node->getElementsByTagName('a')->length;
                $embedCount = 0;
                $embeds = $node->getElementsByTagName('embed');

                for ($ei = 0, $il = $embeds->length; $ei < $il; ++$ei) {
                    if (preg_match($this->regexps['media'], $embeds->item($ei)->getAttribute('src'))) {
                        ++$embedCount;
                    }
                }

                $embeds = $node->getElementsByTagName('iframe');
                for ($ei = 0, $il = $embeds->length; $ei < $il; ++$ei) {
                    if (preg_match($this->regexps['media'], $embeds->item($ei)->getAttribute('src'))) {
                        ++$embedCount;
                    }
                }

                $linkDensity = $this->getLinkDensity($node, true);
                $contentLength = mb_strlen($this->getInnerText($node));
                $toRemove = false;

                if ($this->lightClean) {
                    if ($li > $p && $tag !== 'ul' && $tag !== 'ol') {
                        $this->logger->debug(' too many <li> elements, and parent is not <ul> or <ol>');
                        $toRemove = true;
                    } elseif ($input > floor($p / 3)) {
                        $this->logger->debug(' too many <input> elements');
                        $toRemove = true;
                    } elseif ($contentLength < 6 && ($embedCount === 0 && ($img === 0 || $img > 2))) {
                        $this->logger->debug(' content length less than 6 chars, 0 embeds and either 0 images or more than 2 images');
                        $toRemove = true;
                    } elseif ($weight < 25 && $linkDensity > 0.25) {
                        $this->logger->debug(' weight is ' . $weight . ' < 25 and link density is ' . sprintf('%.2f', $linkDensity) . ' > 0.25');
                        $toRemove = true;
                    } elseif ($a > 2 && ($weight >= 25 && $linkDensity > 0.5)) {
                        $this->logger->debug('  more than 2 links and weight is ' . $weight . ' > 25 but link density is ' . sprintf('%.2f', $linkDensity) . ' > 0.5');
                        $toRemove = true;
                    } elseif ($embedCount > 3) {
                        $this->logger->debug(' more than 3 embeds');
                        $toRemove = true;
                    }
                } else {
                    if ($img > $p) {
                        $this->logger->debug(' more image elements than paragraph elements');
                        $toRemove = true;
                    } elseif ($li > $p && $tag !== 'ul' && $tag !== 'ol') {
                        $this->logger->debug('  too many <li> elements, and parent is not <ul> or <ol>');
                        $toRemove = true;
                    } elseif ($input > floor($p / 3)) {
                        $this->logger->debug('  too many <input> elements');
                        $toRemove = true;
                    } elseif ($contentLength < 10 && ($img === 0 || $img > 2)) {
                        $this->logger->debug('  content length less than 10 chars and 0 images, or more than 2 images');
                        $toRemove = true;
                    } elseif ($weight < 25 && $linkDensity > 0.2) {
                        $this->logger->debug('  weight is ' . $weight . ' lower than 0 and link density is ' . sprintf('%.2f', $linkDensity) . ' > 0.2');
                        $toRemove = true;
                    } elseif ($weight >= 25 && $linkDensity > 0.5) {
                        $this->logger->debug('  weight above 25 but link density is ' . sprintf('%.2f', $linkDensity) . ' > 0.5');
                        $toRemove = true;
                    } elseif (($embedCount === 1 && $contentLength < 75) || $embedCount > 1) {
                        $this->logger->debug('  1 embed and content length smaller than 75 chars, or more than one embed');
                        $toRemove = true;
                    }
                }

                if ($toRemove) {
                    $this->logger->debug('Removing...');
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }

    /**
     * Clean out spurious headers from an Element. Checks things like classnames and link density.
     *
     * @param \DOMElement $e
     */
    public function cleanHeaders($e)
    {
        for ($headerIndex = 1; $headerIndex < 3; ++$headerIndex) {
            $headers = $e->getElementsByTagName('h' . $headerIndex);

            for ($i = $headers->length - 1; $i >= 0; --$i) {
                if ($this->getWeight($headers->item($i)) < 0 || $this->getLinkDensity($headers->item($i)) > 0.33) {
                    $headers->item($i)->parentNode->removeChild($headers->item($i));
                }
            }
        }
    }

    /**
     * Check if the given flag is active.
     *
     * @param int $flag
     *
     * @return bool
     */
    public function flagIsActive($flag)
    {
        return ($this->flags & $flag) > 0;
    }

    /**
     * Add a flag.
     *
     * @param int $flag
     */
    public function addFlag($flag)
    {
        $this->flags = $this->flags | $flag;
    }

    /**
     * Remove a flag.
     *
     * @param int $flag
     */
    public function removeFlag($flag)
    {
        $this->flags = $this->flags & ~$flag;
    }

    /**
     * Will recreate previously deleted body property.
     */
    protected function reinitBody()
    {
        if (!isset($this->body->childNodes)) {
            $this->body = $this->dom->createElement('body');
            $this->body->innerHTML = $this->bodyCache;
        }
    }
}
