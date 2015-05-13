<?php

namespace spouts\github;

/**
 * Spout for fetching from GitHub 
 *
 * @package    spouts
 * @subpackage github
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Tim Gerundt <tim@gerundt.de>
 */
class commits extends \spouts\spout {

    /**
     * name of source
     *
     * @var string
     */
    public $name = 'GitHub';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'List commits on a repository';
    
    
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
        "owner" => array(
            "title"      => "Owner",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array( "notempty" )
        ),
        "repo" => array(
            "title"      => "Repository",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array( "notempty" )
        ),
        "branch" => array(
            "title"      => "Branch",
            "type"       => "text",
            "default"    => "",
            "required"   => true,
            "validation" => array( "notempty" )
        )
    );
    
    
    /**
     * current fetched items
     *
     * @var array|bool
     */
    protected $items = false;
    
    
    /**
     * global html url for the source
     *
     * @var string
     */
    protected $htmlUrl = '';
    
    
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
     *
     * @return void
     * @param mixed $params the params of this source
     */
    public function load($params) {
        $this->htmlUrl = "https://github.com/" . urlencode($params['owner']) . "/" . urlencode($params['repo']) . "/" . urlencode($params['branch']);
        
        $jsonUrl = "https://api.github.com/repos/" . urlencode($params['owner']) . "/" . urlencode($params['repo']) . "/commits?sha=" . urlencode($params['branch']);
        $this->items = $this->getJsonContent($jsonUrl);
    }
    
    
    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    public function getHtmlUrl() {
        return $this->htmlUrl;
    }
    
    
    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if($this->items!==false && $this->valid())
            return @current( $this->items )->sha;
        return false;
    }
    
    
    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if($this->items!==false && $this->valid()) {
            $message = @current( $this->items )->commit->message;
            
            return self::cutTitle($message);
        }
        return false;
    }
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if($this->items!==false && $this->valid()) {
            $message = @current( $this->items )->commit->message;
            
            return nl2br($message, false);
        }
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
            return @current( $this->items )->html_url;
        return false;
    }
    
    
    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if($this->items!==false && $this->valid())
            $date = date('Y-m-d H:i:s', strtotime(@current( $this->items )->commit->author->date));
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
     * get JSON object
     * @param string $url URL
     * @return object JSON object
     */
    public function getJsonContent($url) {
        $content = null;
        try {
            $content = \helpers\WebClient::request($url);
        }catch( \exception $e ) {
            throw new \exception('github spout error ' . $e->getMessage());
        }

        $json = @json_decode($content);
        
        if (empty($json)) {
            throw new \exception('github spout error: empy json');
        }
        
        return $json;
    }
    
    /**
     * cut title after X chars (from the first line)
     * @param string $title title
     * @param integer $cutafter Cut after X chars
     * @return string Cutted title
     */
    public static function cutTitle($title, $cutafter = 69) {
        $title = strtok($title, "\n");
        if (($cutafter > 0) && (strlen($title) > $cutafter)) {
            return substr($title, 0, $cutafter) . '&hellip;';
        }
        return $title;
    }
}
