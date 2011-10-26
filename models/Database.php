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
        $db_type = \F3::get('db_type');
        
        $this->backend = new $db_type\Database();
    }
    
    /**
     * optimize database by
     * database own optimize statement
     *
     * @return void
     */
    public static function optimize() {
        $db_type = \F3::get('db_type');

        $db_type\Database::optimize();
    }
}
