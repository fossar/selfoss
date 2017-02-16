<?PHP

namespace daos\mysql;
    
/**
 * Base class for database access -- mysql
 *
 * @package    daos
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
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
        if(self::$initialized===false && \F3::get('db_type')=="mysql") {
            $host = \F3::get('db_host');
            $port = \F3::get('db_port');
            $database = \F3::get('db_database');

            if ($port) {
                $dsn = "mysql:host=$host; port=$port; dbname=$database";
            } else {
                $dsn = "mysql:host=$host; dbname=$database";
            }

            \F3::get('logger')->debug("Establish database connection");
            \F3::set('db', new \DB\SQL(
                $dsn,
                \F3::get('db_username'),
                \F3::get('db_password'),
                array(\PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8mb4;')
            ));
            
            // create tables if necessary
            $result = @\F3::get('db')->exec('SHOW TABLES');
            $tables = array();
            foreach($result as $table)
                foreach($table as $key=>$value)
                    $tables[] = $value;
            
            if(!in_array(\F3::get('db_prefix').'items', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE '.\F3::get('db_prefix').'items (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        datetime DATETIME NOT NULL ,
                        title TEXT NOT NULL ,
                        content LONGTEXT NOT NULL ,
                        thumbnail TEXT ,
                        icon TEXT ,
                        unread BOOL NOT NULL ,
                        starred BOOL NOT NULL ,
                        source INT NOT NULL ,
                        uid VARCHAR(255) NOT NULL,
                        link TEXT NOT NULL,
                        updatetime DATETIME NOT NULL,
                        author VARCHAR(255),
                        INDEX (source)
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
                ');
                \F3::get('db')->exec('
                    CREATE TRIGGER insert_updatetime_trigger
                    BEFORE INSERT ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                        END;
                ');
                \F3::get('db')->exec('
                    CREATE TRIGGER update_updatetime_trigger
                    BEFORE UPDATE ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                        END;
                ');
            }
            
            $isNewestSourcesTable = false;
            if(!in_array(\F3::get('db_prefix').'sources', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE '.\F3::get('db_prefix').'sources (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        title TEXT NOT NULL ,
                        tags TEXT,
                        spout TEXT NOT NULL ,
                        params TEXT NOT NULL ,
                        filter TEXT,
                        error TEXT,
                        lastupdate INT,
                		lastentry INT
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
                ');
                $isNewestSourcesTable = true;
            }
            
            // version 1 or new
            if(!in_array(\F3::get('db_prefix').'version', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE '.\F3::get('db_prefix').'version (
                        version INT
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
                ');
                
                \F3::get('db')->exec('
                    INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (8);
                ');
                
                \F3::get('db')->exec('
                    CREATE TABLE '.\F3::get('db_prefix').'tags (
                        tag         TEXT NOT NULL,
                        color       VARCHAR(7) NOT NULL
                    ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
                ');
                
                if($isNewestSourcesTable===false) {
                    \F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'sources ADD tags TEXT;
                    ');
                }
            }
            else{
                $version = @\F3::get('db')->exec('SELECT version FROM '.\F3::get('db_prefix').'version ORDER BY version DESC LIMIT 0, 1');
                $version = $version[0]['version'];
                
                if(strnatcmp($version, "3") < 0){
                    \F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'sources ADD lastupdate INT;
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (3);
                    ');
                }
                if(strnatcmp($version, "4") < 0){
                    \F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'items ADD updatetime DATETIME;
                    ');
                    \F3::get('db')->exec('
                        CREATE TRIGGER insert_updatetime_trigger
                        BEFORE INSERT ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                            BEGIN
                                SET NEW.updatetime = NOW();
                            END;
                    ');
                    \F3::get('db')->exec('
                        CREATE TRIGGER update_updatetime_trigger
                        BEFORE UPDATE ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                            BEGIN
                                SET NEW.updatetime = NOW();
                            END;
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (4);
                    ');
                }
                if(strnatcmp($version, "5") < 0){
                    \F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'items ADD author VARCHAR(255);
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (5);
                    ');
                }
                if(strnatcmp($version, "6") < 0){
                    \F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'sources ADD filter TEXT;
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (6);
                    ');
                }
                // Jump straight from v6 to v8 due to bug in previous version of the code
                // in /daos/sqlite/Database.php which
                // set the database version to "7" for initial installs.
                if(strnatcmp($version, "8") < 0){
                	\F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'sources ADD lastentry INT;
                    ');
                	\F3::get('db')->exec('
                        INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (8);
                    ');
                }
				if(strnatcmp($version, "9") < 0) {
					\F3::get('db')->exec('
                        ALTER TABLE '.\F3::get('db_prefix').'items ADD shared BOOL;
                    ');
		 \F3::get('db')->exec('
                        INSERT INTO '.\F3::get('db_prefix').'version (version) VALUES (9);
                    ');
				}
                if(strnatcmp($version, "10") < 0) {
                    \F3::get('db')->exec(array(
                        'ALTER TABLE `' . \F3::get('db_prefix') . 'items` CONVERT TO CHARACTER SET utf8mb4;',
                        'ALTER TABLE `' . \F3::get('db_prefix') . 'sources` CONVERT TO CHARACTER SET utf8mb4;',
                        'ALTER TABLE `' . \F3::get('db_prefix') . 'tags` CONVERT TO CHARACTER SET utf8mb4;',
                        'ALTER TABLE `' . \F3::get('db_prefix') . 'version` CONVERT TO CHARACTER SET utf8mb4;',
                        'INSERT INTO `' . \F3::get('db_prefix') . 'version` (version) VALUES (10);'
                    ));
                }
            }
            
            // just initialize once
            self::$initialized = true;
        }

        $class = 'daos\\' . \F3::get('db_type') . '\\Statements';
        $this->stmt = new $class();
    }
    
    
    /**
     * optimize database by
     * database own optimize statement
     *
     * @return void
     */
    public function optimize() {
        @\F3::get('db')->exec('OPTIMIZE TABLE `'.\F3::get('db_prefix').'sources`, `'.\F3::get('db_prefix').'items`');
    }


    /**
     * Ensure row values have the appropriate PHP type. This assumes we are
     * using buffered queries (sql results are in PHP memory).
     *
     * @param expectedRowTypes associative array mapping columns to PDO types
     * @param rows array of associative array representing row results
     * @return array of associative array representing row results having
     *         expected types
     */
    public function ensureRowTypes($expectedRowTypes, $rows) {
        foreach($rows as $rowIndex => $row) {
            foreach($expectedRowTypes as $column => $type) {
                if( array_key_exists($column, $row) ) {
                    switch($type) {
                        case \PDO::PARAM_INT:
                            $value = intval($row[$column]);
                            break;
                        case \PDO::PARAM_BOOL:
                            if( $row[$column] == "1" )
                                $value = true;
                            else
                                $value = false;
                            break;
                    }
                    // $row is only a reference, so we change $rows[$rowIndex]
                    $rows[$rowIndex][$column] = $value;
                }
            }
        }
        return $rows;
    }
}
