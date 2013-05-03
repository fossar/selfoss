<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news from mmospy with the full text
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class mmospy extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'mmospy';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed fetches the mmospy news with full content (not only the header as content)';
    
    
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
    public $params = false;


    /**
     * addresses of feeds for the sections
     */
    private $feedUrl = "http://www.mmo-spy.de/misc.php?action=newsfeed";


    /**
     * loads content for given source
     *
     * @return void
     * @param string $url
     */
    public function load($params) {
        parent::load(array( 'url' => $this->getXmlUrl() ) );
    }


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params = null) {
        return $this->feedUrl;
    }


    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid()) {
            $originalContent = file_get_contents($this->getLink());
            preg_match_all('|<div class="content">(.*?)</div>|ims', $originalContent, $matches, PREG_PATTERN_ORDER);
            if(is_array($matches) && is_array($matches[0]) && isset($matches[0][0])) {
                $content = utf8_encode($matches[0][0]);
                
                $content = preg_replace(',<a([^>]+)href="([^>"\s]+)",ie',
                                            '"<a\1href=\"" . $this->absolute("\2", "http://www.mmo-spy.de") . "\""',
                                            $content);
                $content = preg_replace(',<img([^>]+)src="([^>"\s]+)",ie',
                                        '"<img\1src=\"" . $this->absolute("\2", "http://www.mmo-spy.de") . "\""',
                                        $content);
            
                return $content;
            }
        }
        return parent::getContent();
    }

    /**
     * convert relative url to absolute
     *
     * @return string absolute url
     * @return string $relative url
     * @return string $absolute url
     */
    public function absolute($relative, $absolute) {
        if (preg_match(',^(https?://|ftp://|mailto:|news:),i', $relative))
            return $relative;
        return $absolute . $relative;
    }
}
