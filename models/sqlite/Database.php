<?PHP

namespace models\sqlite;
    
/**
 * Base class for database access -- sqlite
 *
 * @package     models
 * @copyright   Copyright (c) Harald Lapp (harald.lapp@gmail.com)
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Harald Lapp (harald.lapp@gmail.com)
 */
class Database {
    /**
     * indicates whether database connection was initialized
     *
     * @var     bool
     */
    static private $initialized = false;

    /**
     * establish connection and create undefined tables
     *
     * @return  void
     */
    public function __construct() {
        if (self::$initialized === false) {
            $db_file = \F3::get('db_file');
            
            // create empty database file if it does not exist
            if (!is_file($db_file)) {
                touch($db_file);
            }
            
            // establish database connection
            \F3::set('DB',
                new \DB(
                    'sqlite:' . $db_file
                )
            );
            
            // create tables if necessary
            @\DB::sql('SELECT name FROM sqlite_master WHERE type = "table"');
            $tables = array();
            foreach(\F3::get('DB->result') as $table)
                foreach($table as $key=>$value)
                    $tables[] = $value;
            
            if(!in_array('items', $tables)) {
                \DB::sql('
                    CREATE TABLE items (
                        id          INTEGER PRIMARY KEY AUTOINCREMENT,
                        datetime    DATETIME NOT NULL,
                        title       TEXT NOT NULL,
                        content     TEXT NOT NULL,
                        thumbnail   TEXT,
                        icon        TEXT,
                        unread      BOOL NOT NULL,
                        starred     BOOL NOT NULL,
                        source      INT NOT NULL,
                        uid         VARCHAR(255) NOT NULL,
                        link        TEXT NOT NULL
                    );
                ');
                
                \DB::sql('
                    CREATE INDEX source ON items (
                        source
                    );
                ');
            }
                 
            if(!in_array('sources', $tables)) {
                \DB::sql('
                    CREATE TABLE sources (
                        id          INTEGER PRIMARY KEY AUTOINCREMENT,
                        title       TEXT NOT NULL,
                        spout       TEXT NOT NULL,
                        params      TEXT NOT NULL,
                        error       TEXT 
                    );
                ');
            }
                
            // just initialize once
            $initialized = true;
        }
    }
    
    
    /**
     * optimize database by database own optimize statement
     *
     * @return  void
     */
    public static function optimize() {
    }
}
