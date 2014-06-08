<?PHP

namespace daos\sqlite;
    
/**
 * Base class for database access -- sqlite
 *
 * @package     daos
 * @copyright   Copyright (c) Harald Lapp (harald.lapp@gmail.com)
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Harald Lapp (harald.lapp@gmail.com)
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database {

    /**
     * indicates whether database connection was initialized
     *
     * @var bool
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
            \F3::set('db', new \DB\SQL(
                    'sqlite:' . $db_file
                )
            );
            
            // create tables if necessary
            $result = @\F3::get('db')->exec('SELECT name FROM sqlite_master WHERE type = "table"');
            $tables = array();
            foreach($result as $table)
                foreach($table as $key=>$value)
                    $tables[] = $value;
            
            if(!in_array('items', $tables)) {
                \F3::get('db')->exec('
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
                        link        TEXT NOT NULL,
                        author      VARCHAR(255)
                    );
                ');
                
                \F3::get('db')->exec('
                    CREATE INDEX source ON items (
                        source
                    );
                ');
            }
            
            $isNewestSourcesTable = false;
            if(!in_array('sources', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE sources (
                        id          INTEGER PRIMARY KEY AUTOINCREMENT,
                        title       TEXT NOT NULL,
                        tags        TEXT,
                        spout       TEXT NOT NULL,
                        params      TEXT NOT NULL,
                        error       TEXT,
                        lastupdate  INTEGER
                    );
                ');
                $isNewestSourcesTable = true;
            }
                 
            // version 1
            if(!in_array('version', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE version (
                        version INT
                    );
                ');
                
                \F3::get('db')->exec('
                    INSERT INTO version (version) VALUES (3);
                ');
                
                \F3::get('db')->exec('
                    CREATE TABLE tags (
                        tag         TEXT NOT NULL,
                        color       TEXT NOT NULL
                    );
                ');
                
                if($isNewestSourcesTable===false) {
                    \F3::get('db')->exec('
                        ALTER TABLE sources ADD tags TEXT;
                    ');
                }
            }
            else{
                $version = @\F3::get('db')->exec('SELECT version FROM version ORDER BY version DESC LIMIT 0, 1');
                $version = $version[0]['version'];

                if($version == "2"){
                    \F3::get('db')->exec('
                        ALTER TABLE sources ADD lastupdate INT;
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO version (version) VALUES (3);
                    ');
                }
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
    public function optimize() {
        @\F3::get('db')->exec('
            VACUUM;
        ');
    }
}
