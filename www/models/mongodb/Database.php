<?PHP

namespace models\mongodb;
    
/**
 * Base class for database access -- mongodb
 *
 * @package     models
 * @copyright   Copyright (c) Harald Lapp (http://octris.org/)
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Harald Lapp <harald.lapp@gmail.com>
 */
class Database {
    /**
     * indicates whether database connection was
     * initialized
     * @var bool
     */
    static private $initialized = false;

    /**
     * establish connection and
     * create undefined tables
     *
     * @return void
     */
    public function __construct() {
        if (self::$initialized === false) {
            // establish database connection
            $db_database = \F3::get('db_database');
            
            $mdb = new \MongoDB(
                new \Mongo('mongodb://' . \F3::get('db_host') . ':' . \F3::get('db_port')),
                $db_database
            );
            
            \F3::set('DB', $mdb);

            // create collections if necessary -- this seems to be required for F3::M2,
            // but as it seems there is no built-in functionality for this
            $list = $mdb->listCollections();
            foreach ($list as &$tmp) $tmp = (string)$tmp;
            
            if (!in_array($db_database . '.sources', $list)) {
                $mdb->createCollection('sources');
            }
            
            if (!in_array($db_database . '.items', $list)) {
                $mdb->createCollection('items');
                
                $col = $mdb->selectCollection('items');
                $col->ensureIndex(array('title'   => 1));
                $col->ensureIndex(array('content' => 1));
            }
            
            // just initialize once
            $initialized = true;
        }
    }
    
    /**
     * optimize database by
     * database own optimize statement
     *
     * @return void
     */
    public static function optimize() {
    }
}
