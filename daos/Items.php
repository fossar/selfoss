<?PHP

namespace daos;

/**
 * Class for accessing persistent saved items
 *
 * @package    daos
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
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
        $class = 'daos\\' . \F3::get('db_type') . '\\Items';
        $this->backend = new $class();
        parent::__construct();
    }
    
    
    /**
     * pass any method call to the backend.
     * 
     * @return methods return value
     * @param string $name name of the function
     * @param array $args arguments
     */
    public function __call($name, $args) {
        if(method_exists($this->backend, $name))
            return call_user_func_array(array($this->backend, $name), $args);
        else
            \F3::get('logger')->log('Unimplemented method for ' . \F3::get('db_type') . ': ' . $name, \ERROR);
    }
    
    
    /**
     * cleanup orphaned and old items
     *
     * @return void
     * @param int $days delete all items older than this value [optional]
     */
    public function cleanup($days) {
        $minDate = NULL;
        if ($days !== "") {
            $minDate = new \DateTime();
            $minDate->sub(new \DateInterval('P'.$days.'D'));
        }
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
        
        $items = $this->backend->get($options);

        // remove private posts with private tags
        if(!\F3::get('auth')->showPrivateTags()) {
            foreach($items as $idx => $item) {
                if (strpos($item['tags'], "@") !== false) {
                    unset($items[$idx]);
                }
            }
            $items = array_values($items);
        }

        // remove posts with hidden tags
        if(!isset($options['tag']) || strlen($options['tag']) === 0) {
            foreach($items as $idx => $item) {
                if (strpos($item['tags'], "#") !== false) {
                    unset($items[$idx]);
                }
            }
            $items = array_values($items);
        }

        return $items;
    }
}
