<?PHP

namespace models;
    
/**
 * Base class for database access
 *
 * @package    models
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 */
class Database {
    /**
     * Instance of backend specific database access class
     *
     * @var     object
     */
    private $backend = null;
    
    /**
     * establish connection and
     * create undefined tables
     *
     * @return void
     */
    public function __construct() {
        $class = 'models\\' . \F3::get('db_type') . '\\Database';
        
        $this->backend = new $class();
    }
    
    /**
     * optimize database by
     * database own optimize statement
     *
     * @return void
     */
    public static function optimize() {
        $class = 'models\\' . \F3::get('db_type') . '\\Database';
        
        $class::optimize();
    }
}
