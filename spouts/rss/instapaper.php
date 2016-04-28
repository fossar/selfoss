<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news and cleaning the content with instapaper.com
 *
 * @package    plugins
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class instapaper extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'RSS Feed (with instapaper)';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed cleaning the content with instapaper.com';
    
    
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
            "type"       => "url",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        )
    );
  
    /**
     * loads content for given source
     *
     * @return void
     * @param string $url
     */
    public function load($params) {
        parent::load(array( 'url' => $params['url']) );
    }
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        $contentFromInstapaper = $this->fetchFromInstapaper(parent::getLink());
        if($contentFromInstapaper===false)
            return "instapaper parse error <br />" . parent::getContent();
        return $contentFromInstapaper;
    }
    
    
    /**
     * fetch content from instapaper.com
     *
     * @author janeczku @github
     * @return string content
     */
    private function fetchFromInstapaper($url) {
        if (function_exists('curl_init') && !ini_get("open_basedir")) {
            $content = $this->file_get_contents_curl("https://www.instapaper.com/text?u=" . urlencode($url));
        }
        else {
            $content = @file_get_contents("https://www.instapaper.com/text?u=" . urlencode($url));
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML($content);
        if (!$dom)
            return false;
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query("//div[@id='story']");
        $content = $dom->saveXML($elements->item(0), LIBXML_NOEMPTYTAG);
        return $content;
    }

    private function file_get_contents_curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);   
        $data = @curl_exec($ch);
        curl_close($ch);
     
        return $data;
    }

}
