<?PHP 

namespace spouts\deviantart;

/**
 * Spout for fetching an rss feed
 *
 * @package    spouts
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
class user extends \spouts\rss\images {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'deviantArt User';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'deviations of a deviantart user';
    
    
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
        "username" => array(
            "title"      => "Username",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        )
    );
    
    
    /**
     * loads content for given source
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @return void
     * @param mixed $params the params of this source
     */
    public function load($params) {
        parent::load(array( "url" => 'http://backend.deviantart.com/rss.xml?q=sort%3Atime%20by%3A'.urlencode($params['username']).'&type=deviation'));
    }
    
}
