<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news from golem with the full text
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class golem extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'News: Golem';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed fetches the golem news with full content (not only the header as content)';
    
    
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
        "section" => array(
            "title"      => "Section",
            "type"       => "select",
            "values"     => array(
                "main"           => "All",
                "audiovideo"     => "Audio/Video",
                "foto"           => "Foto",
                "games"          => "Games",
                "handy"          => "Handy",
                "internet"       => "Internet",
                "mobil"          => "Mobil",
                "oss"            => "OSS",
                "politik"        => "Politik/Recht",
                "security"       => "Security",
                "desktop"        => "Desktop-Applikationen",
                "se"             => "Software-Entwicklung",
                "wirtschaft"     => "Wirtschaft",
                "hardware"       => "Hardware",
                "software"       => "Software",
                "networld"       => "Networld",
                "entertainment"  => "Entertainment",
                "tk"             => "TK",
                "wirtschaft"     => "Wirtschaft",
                "ecommerce"      => "E-Commerce",
                "forum"          => "Forumsbeiträge"
            ),
            "default"    => "main",
            "required"   => true,
            "validation" => array()
        )
    );


    /**
     * addresses of feeds for the sections
     */
    private $feedUrls = array(
        "main"           => "http://rss.golem.de/rss.php?feed=RSS2.0",
        "audiovideo"     => "http://rss.golem.de/rss.php?tp=av&feed=RSS2.0",
        "foto"           => "http://rss.golem.de/rss.php?tp=foto&feed=RSS2.0",
        "games"          => "http://rss.golem.de/rss.php?tp=games&feed=RSS2.0",
        "handy"          => "http://rss.golem.de/rss.php?tp=handy&feed=RSS2.0",
        "internet"       => "http://rss.golem.de/rss.php?tp=inet&feed=ATOM1.0",
        "mobil"          => "http://rss.golem.de/rss.php?tp=mc&feed=RSS2.0",
        "oss"            => "http://rss.golem.de/rss.php?tp=oss&feed=RSS2.0",
        "politik"        => "http://rss.golem.de/rss.php?tp=pol&feed=RSS2.0",
        "security"       => "http://rss.golem.de/rss.php?tp=sec&feed=RSS2.0",
        "desktop"        => "http://rss.golem.de/rss.php?tp=apps&feed=RSS2.0",
        "se"             => "http://rss.golem.de/rss.php?tp=dev&feed=RSS2.0",
        "wirtschaft"     => "http://rss.golem.de/rss.php?tp=wirtschaft&feed=RSS2.0",
        "hardware"       => "http://rss.golem.de/rss.php?r=hw&feed=RSS2.0",
        "software"       => "http://rss.golem.de/rss.php?r=sw&feed=RSS2.0",
        "networld"       => "http://rss.golem.de/rss.php?r=nw&feed=RSS2.0",
        "entertainment"  => "http://rss.golem.de/rss.php?r=et&feed=RSS2.0",
        "tk"             => "http://rss.golem.de/rss.php?r=tk&feed=RSS2.0",
        "wirtschaft"     => "http://rss.golem.de/rss.php?r=wi&feed=RSS2.0",
        "ecommerce"      => "http://rss.golem.de/rss.php?r=ec&feed=RSS2.0",
        "forum"          => "http://forum.golem.de/rss.php?feed=RSS2.0"
    );


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
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        return $this->feedUrls[$params['section']];
    }


    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid()) {
            $originalContent = $this->cleanContent(file_get_contents($this->getLink()));
            preg_match_all('|<!--content-->(.*?)<!--/content-->|ims', $originalContent, $matches, PREG_PATTERN_ORDER);
            if(is_array($matches) && is_array($matches[0]) && isset($matches[0][0])) {
                return $matches[0][0];
            }
        }
        return parent::getContent();
    }


    /**
     * clean the content
     *
     * @return string cleaned content
     * @param string $content original content
     */
    private function cleanContent($content) {
        $content = preg_replace('|<!-- begin ad tag \(tile=4\) -->(.*?)<!-- end ad tag \(tile=4\) -->|ims', '', $content);
        $content = preg_replace('|<figure id="([^"]+)"></figure>|ims', '', $content);
        $content = preg_replace('|<a class="golem-gallery2-nojs" href="([^"]+)">(.*?)<img src="([^"]+)" alt="([^"]+)" title="([^"]+)" data-src="([^"]+)" data-src-full="([^"]+)">(.*?)</a>|ims', '<p><a href="$1" target="_blank"><img src="$3" alt="$4" title="$5" /></a></p>', $content);
        return $content;
    }

}
