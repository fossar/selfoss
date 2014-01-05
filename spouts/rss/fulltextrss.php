<?PHP 

namespace spouts\rss;

if(!function_exists('htmLawed'))
    require('libs/htmLawed.php');

/**
 * Plugin for fetching the news with fivefilters Full-Text RSS
 *
 * @package    plugins
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class fulltextrss extends feed {

    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'RSS Feed (with FullTextRss)';

    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed extracts full text article from webpages with an embedded version of Full-Text RSS';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox, select
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * When type is "select", a new entry "values" must be supplied, holding
     * key/value pairs of internal names (key) and displayed labels (value).
     * See /spouts/rss/heise for an example.
     * 
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
    public $params = array(
        "url" => array(
            "title"      => "URL",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        ),
    );

    /**
     * tag for logger
     *
     * @var string
     */
    public $tag = 'ftrss';

    private $extractor;

    private $fingerprints = array(
        '<meta name="generator" content="Posterous"' => array('hostname'=>'fingerprint.posterous.com', 'head'=>true),
        '<meta content=\'blogger\' name=\'generator\'' => array('hostname'=>'fingerprint.blogspot.com', 'head'=>true),
        '<meta name="generator" content="Blogger"' => array('hostname'=>'fingerprint.blogspot.com', 'head'=>true),
        // '<meta name="generator" content="WordPress.com"' => array('hostname'=>'fingerprint.wordpress.com', 'head'=>true),
        '<meta name="generator" content="WordPress' => array('hostname'=>'fingerprint.wordpress.com', 'head'=>true)
    );
    private $allowed_parsers = array('libxml', 'html5lib');
    private $rewrite_relative_urls = true;

    /**
     * loads content for given source
     *
     * @return void
     * @param string $url
     */
    public function load($params) {
        parent::load(array( 'url' => $this->getXmlUrl($params)) );
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {

        $url = parent::getLink();
        \F3::get('logger')->log($this->tag . ' - Loading page: ' . $url, \INFO);
        $content = $this->fetchFromWebSite($url);
        if ($content===false) {
            \F3::get('logger')->log($this->tag . ' - Failed loading page', \ERROR);
            return parent::getContent() .
                   "<p><strong>Failed to get web page</strong></p>";
        }

        \F3::get('logger')->log($this->tag . ' - Extracting content', \INFO);
        $content = $this->extractContent($content, parent::getLink());
        if ($content===false) {
            \F3::get('logger')->log($this->tag . ' - Failed extracting content', \ERROR);
            return parent::getContent() .
                   "<p><strong>Full Text RSS extracting error</strong></p>";
        }

        \F3::get('logger')->log($this->tag . ' - Cleaning content', \INFO);
        $content = $this->cleanContent($content);
        if ($content===false) {
            \F3::get('logger')->log($this->tag . ' - Failed cleaning content from', \ERROR);
            return parent::getContent() .
                   "<p><strong>Full Text RSS cleaning error</strong></p>";
        }
        return $content;
    }

    /**
     * fetch content from FullTextRss
     *
     * @author Jean Baptiste Favre
     * @return string content
     */
    private function fetchFromWebSite($url) {

        $this->extractor = new \ContentExtractor(\F3::get('FTRSS_DATA_DIR').'/site_config/custom', \F3::get('FTRSS_DATA_DIR').'/site_config/standard');
        \SiteConfig::use_apc(false);
        $this->extractor->fingerprints = $this->fingerprints;
        $this->extractor->allowedParsers = $this->allowed_parsers;

        $stream_opts = array(
            'http'=>array(
                'timeout' => 5,
                'method'  => "GET",
                'header'  => "Accept-language: en-us,en-gb;q=0.8,en;q=0.6,fr;q=0.4,fr-fr;q=0.2\r\n" .
                             "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                             "User-Agent: SimplePie/1.3.1 (Feed Parser; http://simplepie.org; Allow like Gecko) Build/20121030175911" .
                             "DNT: 1"
          )
        );
        $context = stream_context_create($stream_opts);

        $url = $this->removeTrackersFromUrl($url);

        // Load web page
        $html = @file_get_contents($url, false, $context);
        if ($html===false)
            return false;

        return $html;
    }

    /**
     * remove tarkers from url
     *
     * @author Jean Baptiste Favre
     * @return string url
     * @param string $url
     */
    private function removeTrackersFromUrl($url) {
        $url = parse_url($url);

        // Next, rebuild URL
        $real_url = $url['scheme'] . '://';
        if (isset($url['user']) && isset($url['password']))
            $real_url .= $url['user'] . ':' . $url['password'] . '@';
        $real_url .= $url['host'] . $url['path'];

        // Query string
        if (isset($url['query'])) {
            parse_str($url['query'], $q_array);
            $real_query = array();
            foreach ($q_array as $key => $value) {
                // Remove utm_* parameters
                if(strpos($key, 'utm_')===false)
                    $real_query[]= $key.'='.$value;
            }
            $real_url .= '?' . implode('&', $real_query);
        }
        // Fragment
        if (isset($url['fragment'])) {
            // Remove xtor=RSS anchor
            if (strpos($url['fragment'], 'xtor=RSS')===false)
                $real_url .= '#' . $url['fragment'];
        }
        return $real_url;
    }

    /**
     * Extract full text from a full web page
     *
     * @author Jean Baptiste Favre
     * @return string html
     * @param string $html
     */
    private function extractContent($html, $url) {
        \F3::get('logger')->log($this->tag . ' - Cleaning content', \DEBUG);
        // remove strange things
        $html = str_replace('</[>', '', $html);
        //$html = convert_to_utf8($html, $response['headers']);

        $extract_result = $this->extractor->process($html, $url);
        if ($extract_result===false)
            return false;

        $extracted_content = $this->extractor->getContent();
        $readability = $this->extractor->readability;
        $readability->clean($extracted_content, 'select');
        if ($this->rewrite_relative_urls) $this->makeAbsolute($url, $extracted_content);
        unset($readability);

        // remove nesting: <div><div><div><p>test</p></div></div></div> = <p>test</p>
        while ($extracted_content->childNodes->length == 1 && $extracted_content->firstChild->nodeType === XML_ELEMENT_NODE) {
                // only follow these tag names
                if (!in_array(strtolower($extracted_content->tagName), array('div', 'article', 'section', 'header', 'footer'))) break;
                $extracted_content = $extracted_content->firstChild;
        }

        // Need to preserve things like body: //img[@id='feature']
        if (in_array(strtolower($extracted_content->tagName), array('div', 'article', 'section', 'header', 'footer'))) {
                $html = $extracted_content->innerHTML;
        } else {
                $html = $extracted_content->ownerDocument->saveXML($extracted_content); // essentially outerHTML
        }
        unset($extracted_content);

        return $html;
    }

    /**
     * Clean extracted content before giving it back to SelfOSS
     *
     * @author Jean Baptiste Favre
     * @return string html
     * @param string $html
     */
    private function cleanContent($html){
        // post-processing cleanup
        \F3::get('logger')->log($this->tag . ' - Post process cleaning & anti-XSS', \DEBUG);
        $html = preg_replace('!<p>[\s\h\v]*</p>!u', '', $html);
        $html = preg_replace('!<a[^>]*/>!', '', $html);

        if ( $html === false )
            return $false;
        return $html;
    }

    private function makeAbsolute($base, $elem) {
        $base = new \SimplePie_IRI($base);
        // remove '//' in URL path (used to prevent URLs from resolving properly)
        // TODO: check if this is still the case
        if (isset($base->path)) $base->path = preg_replace('!//+!', '/', $base->path);
        foreach(array('a'=>'href', 'img'=>'src') as $tag => $attr) {
                $elems = $elem->getElementsByTagName($tag);
                for ($i = $elems->length-1; $i >= 0; $i--) {
                        $e = $elems->item($i);
                        $this->makeAbsoluteAttr($base, $e, $attr);
                }
                if (strtolower($elem->tagName) == $tag) makeAbsoluteAttr($base, $elem, $attr);
        }
    }
    private function makeAbsoluteAttr($base, $e, $attr) {
        if ($e->hasAttribute($attr)) {
                // Trim leading and trailing white space. I don't really like this but 
                // unfortunately it does appear on some sites. e.g.  <img src=" /path/to/image.jpg" />
                $url = trim(str_replace('%20', ' ', $e->getAttribute($attr)));
                $url = str_replace(' ', '%20', $url);
                if (!preg_match('!https?://!i', $url)) {
                        if ($absolute = \SimplePie_IRI::absolutize($base, $url)) {
                                $e->setAttribute($attr, $absolute);
                        }
                }
        }
    }

}
