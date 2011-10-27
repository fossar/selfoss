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
            \F3::set('DB',
                new \MongoDB(
                    new Mongo('mongodb://' . \F3::get('db_host') . ':' . \F3::get('db_port')),
                    \F3::get('db_database')
                )
            );

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
