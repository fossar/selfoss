<?PHP 

namespace spouts\facebook;

/**
 * Spout for fetching a facebook page feed
 *
 * @package    spouts
 * @subpackage facebook
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Thomas Muguet <t.muguet@thomasmuguet.info>
 */
class page extends \spouts\rss\feed {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'Facebook page feed';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'Page wall';
    
    
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
    public $params = array(
        "user" => array(
            "title"      => "Page name",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        )
    );
    
    
    
    //
    // Source Methods
    //
    
    
    /**
     * loads content for given source
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @return void
     * @param mixed $params the params of this source
     */
    public function load($params) {
        parent::load(array('url' => $this->getXmlUrl($params)));
    }


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        $protocol = "http://";
        if (version_compare(PHP_VERSION, "5.3.0") >= 0 && defined("OPENSSL_VERSION_NUMBER")) {
            $protocol = "https://";
        }
        $content = @file_get_contents($protocol . "graph.facebook.com/" . urlencode($params['user']));
        $data = json_decode($content, TRUE);

        return $protocol . "www.facebook.com/feeds/page.php?format=atom10&id=" . $data['id'];
    }
}
