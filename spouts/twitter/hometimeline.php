<?PHP 

namespace spouts\twitter;

/**
 * Spout for fetching the twitter timeline of your twitter account
 *
 * @package    spouts
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class hometimeline extends \spouts\twitter\usertimeline {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'Twitter - Your timeline';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'Your timeline on twitter';
    
    
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
        "consumer_key" => array(
            "title"      => "Consumer Key",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        ),
        "consumer_secret" => array(
            "title"      => "Consumer Secret",
            "type"       => "password",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        ),
        "access_key" => array(
            "title"      => "Access Key",
            "type"       => "password",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        ),
        "access_secret" => array(
            "title"      => "Access Secret",
            "type"       => "password",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        )
    );
    
    
    /**
     * loads content for given twitter user
     *
     * @return void
     * @param mixed $params the params of this source
     */
    public function load($params) {
        $twitter = new \TwitterOAuth($params['consumer_key'], $params['consumer_secret'], $params['access_key'], $params['access_secret']);
        $timeline = $twitter->get('statuses/home_timeline', array('include_rts' => 1, 'count' => 50));
        
        if(isset($timeline->error))
            throw new \exception($timeline->error);
        
        if(!is_array($timeline))
            throw new \exception('invalid twitter response');
        
        $this->items = $timeline;
        
        $this->htmlUrl = 'http://twitter.com/';
    }
}
