<?PHP

namespace daos;

/**
 * Class for accessing tag colors
 *
 * @package    daos\mysql
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */

class Tags extends Database {
    /**
     * Instance of backend specific sources class
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
        $class = 'daos\\' . \F3::get('db_type') . '\\Tags';
        $this->backend = new $class();
        parent::__construct();
    }

    public function get() {
        $tags = $this->backend->get();
        // remove items with private tags
        if(!\F3::get('auth')->showPrivateTags()) {
            foreach($tags as $idx => $tag) {
                if (strpos($tag['tag'], "@") !== false) {
                    unset($tags[$idx]);
                }
            }
            $tags = array_values($tags);
        }

        return $tags;
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
}
