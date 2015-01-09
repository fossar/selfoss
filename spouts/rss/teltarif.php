<?PHP 

namespace spouts\rss;

/**
 * Plugin for fetching the news from Teltarif with the full text
 *
 * @package    plugins
 * @subpackage news
 * @copyright  Copyright (c) Martin Sauter (http://www.wirelessmoves.com)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Martin Sauter  <martin.sauter@wirelessmoves.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class teltarif extends feed {


    /**
     * name of spout
     *
     * @var string
     */
    public $name = 'News: Teltarif';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'This feed fetches Telarif news with full content (not only the header as content)';
    
    
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
    private $feedUrl ="http://www.teltarif.de/feed/news/20.rss2";


    /**
     * htmLawed configuration
     */
    private $htmLawedConfig = array(
        'abs_url'  => 1,
        'base_url' => 'http://www.teltarif.de/',
        'comment'  => 1,
        'safe'     => 1,
    );


    /**
     * ctor
     */
    public function __construct() {
        // include htmLawed
        if(!function_exists('htmLawed'))
            require('libs/htmLawed.php');
    }


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
    public function getXmlUrl($params = NULL) {
         return $this->feedUrl;
    }


    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        
        $start_marker = "<!-- Artikel -->";
        $end_marker = "<!-- NOPRINT Start -->";
        
        if($this->items!==false && $this->valid()) {
            $originalContent = @file_get_contents($this->getLink());
            if($originalContent) {
                
                $originalContent = mb_convert_encoding($originalContent, 'UTF-8', 'ISO-8859-1');
                
                // cut the article from the page
                $text_start_pos = strpos ($originalContent, $start_marker);
                $text_end_pos= strrpos ($originalContent, $end_marker);
                
                if (($text_start_pos != false) && ($text_end_pos != false)) {
                    $content = substr ($originalContent, 
                                       $text_start_pos + strlen ($start_marker), 
                                       $text_end_pos - $text_start_pos - strlen ($start_marker));

                    // remove most html coding and return result
                    return htmLawed($content, $this->htmLawedConfig);
                }
            }       
        }
        return parent::getContent();
    }
    
}

