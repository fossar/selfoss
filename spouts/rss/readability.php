<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news and cleaning the content with readability.com
 *
 * @package    plugins
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class readability extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'RSS Feed (with readability)';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed cleaning the content with readability.com';
    
    
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
        ),
        "api" => array(
            "title"      => "Readability API Key",
            "type"       => "text",
            "default"    => "",
            "required"   => false,
            "validation" => array()
        )
    );

    
    /**
     * the readability api key
     *
     * @var string
     */
    private $apiKey = "";


    /**
     * loads content for given source
     *
     * @return void
     * @param string $url
     */
    public function load($params) {
        $this->apiKey = $params['api'];
        if(strlen(trim($this->apiKey))==0)
            $this->apiKey = \F3::get('readability');
        
        parent::load(array( 'url' => $params['url']) );
    }
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        $contentFromReadability = $this->fetchFromReadability(parent::getLink());
        if($contentFromReadability===false)
            return "readability parse error <br />" . parent::getContent();
        return $contentFromReadability;
    }
    
    
    /**
     * fetch content from readability.com
     *
     * @author oxman @github
     * @return string content
     */
    private function fetchFromReadability($url) {
        $content = @file_get_contents("https://readability.com/api/content/v1/parser?token=" . $this->apiKey . "&url=" . $url);
        $data = json_decode($content);
        if(isset($data->content)===false)
            return false;
        return $data->content;
    }
}
