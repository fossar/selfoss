<?PHP

namespace models;

/**
 * Class for accessing persistent saved items
 *
 * @package    models
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 */
class Items extends Database {
    /**
     * Instance of backend specific items class
     *
     * @var     object
     */
    private $backend = null;
    
    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $class = 'models\\' . \F3::get('db_type') . '\\Items';
        
        $this->backend = new $class();
        
        parent::__construct();
    }

    /**
     * mark item as read
     *
     * @return void
     * @param int $id
     */
    public function mark($id) {
        $this->backend->mark($id);
    }
	
	/**
     * mark item as unread
     *
     * @return void
     * @param int $id
     */
    public function unmark($id) {
        $this->backend->unmark($id);
    }
    
    
    /**
     * starr item
     *
     * @return void
     * @param int $id the item
     */
    public function starr($id) {
        $this->backend->starr($id);
    }
    
    
    /**
     * unstarr item
     *
     * @return void
     * @param int $id the item
     */
    public function unstarr($id) {
        $this->backend->unstarr($id);
    }
    
    
    /**
     * add new item
     *
     * @return void
     * @param mixed $values
     */
    public function add($values) {
        $this->backend->add($values);
    }
    
    
    /**
     * checks whether an item with given
     * uid exists or not
     *
     * @return bool
     * @param string $uid
     */
    public function exists($uid) {
        return $this->backend->exists($uid);
    }
    
    
    /**
     * cleanup old items
     *
     * @return void
     * @param int $days delete all items older than this value
     */
    public function cleanup($days) {
        $minDate = new \DateTime();
        $minDate->sub(new \DateInterval('P'.$days.'D'));
        
        $this->backend->cleanup($minDate);
    }
    
    
    /**
     * returns items
     *
     * @return mixed items as array
     * @param mixed $options search, offset and filter params
     */
    public function get($options = array()) {
        $options = array_merge(
            array(
                'starred' => false,
                'offset'  => 0,
                'search'  => false,
                'items'   => \F3::get('items_perpage')
            ),
            $options
        );
        
        return $this->backend->get($options);
    }
    
    
    /**
     * returns whether more items for last given
     * get call are available
     *
     * @return bool
     */
    public function hasMore() {
        return $this->backend->hasMore();
    }
    
    
    /**
     * return all thumbnails
     *
     * @return string[] array with thumbnails
     */
    public function getThumbnails() {
        return $this->backend->getThumbnails();
    }
    
    
    /**
     * return all icons
     *
     * @return string[] array with all icons
     */
    public function getIcons() {
        return $this->backend->getIcons();
    }
    
    
    /**
     * return all thumbnails
     *
     * @return bool true if thumbnail is still in use
     * @param string $thumbnail name
     */
    public function hasThumbnail($thumbnail) {
        return $this->backend->hasThumbnail($thumbnail);
    }
    
    
    /**
     * return all icons
     *
     * @return bool true if icon is still in use
     * @param string $icon file
     */
    public function hasIcon($icon) {
        return $this->backend->hasIcon($icon);
    }
    
    /**
     * test if the value of a specified field is valid
     *
     * @return  bool
     * @param   string      $name
     * @param   mixed       $value
     */
    public function isValid($name, $value) {
        return $this->backend->isValid($name, $value);
    }
	
	
	/**
     * returns the icon of the last fetched item.
     *
     * @return bool|string false if none was found
     * @param number $sourceid id of the source
     */
    public function getLastIcon($sourceid) {
		return $this->backend->getLastIcon($sourceid);
	}
}
