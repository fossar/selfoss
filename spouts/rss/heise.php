<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news from heise with the full text
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class heise extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'heise news';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed fetches the heise news with full content (not only the header as content)';
    
    
    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
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
     * loads content for given source
     *
     * @return void
     * @param string $url
     */
    public function load($params) {
        parent::load(array( 'url' => 'http://www.heise.de/newsticker/heise-atom.xml') );
    }
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid()) {
            $content = file_get_contents($this->getLink());
            $content = $this->getTag('class', 'meldung_wrapper', $content, 'div');
            if(is_array($content) && count($content)>=1) {
                $content = $content[0] . '</div>';
                $content = preg_replace(',<a([^>]+)href="([^>"\s]+)",ie',
                                        '"<a\1href=\"" . $this->absolute("\2", "http://www.heise.de") . "\""',
                                        $content);
                $content = preg_replace(',<img([^>]+)src="([^>"\s]+)",ie',
                                        '"<img\1src=\"" . $this->absolute("\2", "http://www.heise.de") . "\""',
                                        $content);
                return $content;
            }
        }
        return "";
    }
    
    
    /**
     * get tag by attribute
     * taken from http://www.catswhocode.com/blog/15-php-regular-expressions-for-web-developers
     *
     * @return string content
     * @return string $attr attribute
     * @return string $value necessary value
     * @return string $xml data string
     * @return string $tag optional tag
     */
    private function getTag($attr, $value, $xml, $tag=null) {
        if(is_null($tag))
            $tag = '\w+';
        else
            $tag = preg_quote($tag);

        $attr = preg_quote($attr);
        $value = preg_quote($value);
        $tag_regex = '|<('.$tag.')[^>]*'.$attr.'\s*=\s*([\'"])'.$value.'\2[^>]*>(.*?)</\1>|ims';
        $tag_regex = '|<('.$tag.')[^>]*'.$attr.'\s*=\s*([\'"])'.$value.'\2[^>]*>(.*?)<\!-- AUTHOR-DATA-MARKER-BEGIN|ims';
        preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);
        return $matches[3];
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
