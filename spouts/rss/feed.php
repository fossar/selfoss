<?PHP 

namespace spouts\rss;

/**
 * Spout for fetching an rss feed
 *
 * @package    spouts
 * @subpackage rss
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class feed extends \spouts\spout {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'RSS Feed';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'An default RSS Feed as source';
    
    
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
        "url" => array(
            "title"      => "URL",
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
        // initialize simplepie feed loader
        $this->feed = @new \SimplePie();
        @$this->feed->set_cache_location(\F3::get('cache'));
        @$this->feed->set_cache_duration(1800);
        @$this->feed->set_feed_url(htmlspecialchars_decode($params['url']));
        @$this->feed->set_autodiscovery_level( SIMPLEPIE_LOCATOR_AUTODISCOVERY | SIMPLEPIE_LOCATOR_LOCAL_EXTENSION | SIMPLEPIE_LOCATOR_LOCAL_BODY);
         
        // fetch items
        @$this->feed->init();
        
        // on error retry with force_feed
        if(@$this->feed->error()) {
            @$this->feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
            @$this->feed->force_feed(true);
            @$this->feed->init();
        }
        
        // check for error
        if(@$this->feed->error()) {
            throw new \exception($this->feed->error());
        } else {
            // save fetched items
            $this->items = @$this->feed->get_items();
        }
        
        // return html url
        $this->htmlUrl = @$this->feed->get_link();
    }


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        return isset($params['url']) ? html_entity_decode($params['url']) : false;
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
        if($this->items!==false && $this->valid()) {
            $id = @current($this->items)->get_id();
            if(strlen($id)>255)
                $id = md5($id);
            return $id;
        }
        return false;
    }
    
    
    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if($this->items!==false && $this->valid())
            return @current($this->items)->get_title();
        return false;
    }
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid())
            return @current($this->items)->get_content();
        return false;
    }
    
    
    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        if(isset($this->faviconUrl))
            return $this->faviconUrl;
        
        $this->faviconUrl = false;
        $imageHelper = $this->getImageHelper();
        $htmlUrl = $this->getHtmlUrl();
        if($htmlUrl && $imageHelper->fetchFavicon($htmlUrl))
            $this->faviconUrl = $imageHelper->getFaviconUrl();
        return $this->faviconUrl;
    }
    
    
    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if($this->items!==false && $this->valid())
            return @current($this->items)->get_link();
        return false;
    }
    
    
    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if($this->items!==false && $this->valid())
            $date = @current($this->items)->get_date('Y-m-d H:i:s');
        if(strlen($date)==0)
            $date = date('Y-m-d H:i:s');
        return $date;
    }


    /**
     * returns the author of this item
     * @return string author
     */
    public function getAuthor() {
        if($this->items!==false && $this->valid()) {
            $author = @current($this->items)->get_author();
            if(isset($author)) {
                $name = $author->get_name();
                if(isset($name)) {
                    return $name;
                } else {
                    return $author->get_email();
                }
            }
        }
        return null;
    }

    
    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        $this->feed->__destruct();
        unset($this->items);
        $this->items = false;
    }
}
