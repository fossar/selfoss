<?PHP 

namespace spouts\twitter;

/**
 * Spout for fetching an rss feed
 *
 * @package    spouts
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class usertimeline extends \spouts\spout {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'Twitter - User timeline';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'The timeline of a given user';
    
    
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
        "username" => array(
            "title"      => "Username",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array("notempty")
        )
    );
    
    
    /**
     * current fetched items
     *
     * @var array|bool
     */
    protected $items = false;
    

    
    
    //
    // Iterator Interface
    //
    
    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if($this->items!==false)
            reset($this->items);
    }

    
    /**
     * receive current item
     *
     * @return SimplePie_Item current item
     */
    public function current() {
        if($this->items!==false)
            return $this;
        return false;
    }

    
    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if($this->items!==false)
            return key($this->items);
        return false;
    }

    
    /**
     * select next item
     *
     * @return SimplePie_Item next item
     */
    public function next() {
        if($this->items!==false)
            next($this->items);
        return $this;
    }

    
    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if($this->items!==false)
            return current($this->items) !== false;
        return false;
    }
    
    
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
        $twitter = new \TwitterOAuth($params['consumer_key'], $params['consumer_secret']);
        $timeline = $twitter->get('statuses/user_timeline', array('screen_name' => $params['username'], 'include_rts' => 1, 'count' => 50));
        
        if(isset($timeline->error))
            throw new \exception($timeline->error);
        
        if(!is_array($timeline))
            throw new \exception('invalid twitter response');
        
        $this->items = $timeline;
        
        $this->htmlUrl = 'http://twitter.com/' . urlencode($params['username']);
    }
    
    
    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    public function getHtmlUrl() {
        if(isset($this->htmlUrl))
            return $this->htmlUrl;
        return false;
    }
    
    
    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if($this->items!==false)
            return @current($this->items)->id_str;
        return false;
    }
    
    
    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if($this->items!==false) {
            $item = @current($this->items);
            $rt = "";
            if(isset($item->retweeted_status)){
                $rt = " (RT " . $item->user->name . ")";
                $item = $item->retweeted_status;
            }
            $tweet = $item->user->name . $rt . ":<br>" . $this->formatLinks($item->text);
            return $tweet;
        }
        return false;
    }
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        return;
    }
    
    
    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        if($this->items!==false){
            $item = @current($this->items);
            if(isset($item->retweeted_status)){
                $item = $item->retweeted_status;
            }
            return $item->user->profile_image_url;
        }
        return false;
    }
    
    
    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if($this->items!==false) {
            $item = @current($this->items);
            return 'http://twitter.com/'.$item->user->screen_name.'/status/'.$item->id_str;
        }
        return false;
    }
    
    
    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return mixed thumbnail data
     */
    public function getThumbnail() {
        if($this->items!==false) {
            $item = current($this->items);
            if(isset($item->retweeted_status)){
                $item = $item->retweeted_status;
            }
            if(isset($item->entities->media) && $item->entities->media[0]->type==="photo"){
                return $item->entities->media[0]->media_url;
            }
        }
        return "";
    }
    
    
    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if($this->items!==false)
            $date = date('Y-m-d H:i:s',strtotime(@current($this->items)->created_at));
        if(strlen($date)==0)
            $date = date('Y-m-d H:i:s');
        return $date;
    }
    
    
    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        unset($this->items);
        $this->items = false;
    }
    
    
    /**
     * format links and emails as clickable
     *
     * @return string formated text
     * @param string $text unformated text
     */
    public function formatLinks($text) {
        $text = preg_replace("/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i","<a href=\"mailto:$1\">$1</a>",$text);
        $text = str_replace("http://www.","www.",$text);
        $text = str_replace("www.","http://www.",$text);
        $text = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a href=\"$1\" target=\"_blank\">$1</a>", $text);
        return $text;
    }
}
