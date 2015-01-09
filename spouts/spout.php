<?PHP 

namespace spouts; 

/**
 * This abstract class defines the interface of a spout (source or plugin)
 * template pattern
 *
 * @package    sources
 * @subpackage interface
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
abstract class spout implements \Iterator {
    
    /**
     * name of source
     *
     * @var string
     */
    public $name = '';
    
    
    /**
     * description of this source type
     *
     * @var string
     */
    public $description = '';
    
    
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
     * loads content for given source
     *
     * @return void
     * @param mixed $params params of this source
     */
    abstract public function load($params);


    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        return false;
    }


    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    abstract public function getHtmlUrl();
    
    
    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    abstract public function getId();
    
    
    /**
     * returns the current title as string
     *
     * @return string title
     */
    abstract public function getTitle();
    
    
    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        return "";
    }
    
    
    /**
     * returns the thumbnail of this item
     *
     * @return string thumbnail url
     */
    public function getThumbnail() {
        return "";
    }
    
    
    /**
     * returns the icon of this item
     *
     * @return string icon as url
     */
    abstract public function getIcon();
    
    
    /**
     * returns the link of this item
     *
     * @return string link
     */
    abstract public function getLink();
    
    
    /**
     * returns the date of this item
     *
     * @return string date
     */
    abstract public function getDate();
    

    /**
     * returns the author of this item
     * @return string author
     */
    public function getAuthor() {
        return null;
    }

    
    /**
     * destroy the plugin (prevent memory issues)
     *
     * @return void
     */
    public function destroy() {
        
    }
    
    
    /**
     * returns an instance of selfoss image helper
     * for fetching favicons
     *
     * @return \helpers\Image
     */
    public function getImageHelper() {
        return new \helpers\Image();
    }
}
