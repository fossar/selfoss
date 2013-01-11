<?PHP

namespace daos\mysql;
    
/**
 * Base class for database access -- mysql
 *
 * @package    daos
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
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
            // establish database connection
            \F3::set('DB',
                        new \DB(
                            'mysql:host=' . \F3::get('db_host') . ';port=' . \F3::get('db_port') . ';dbname='.\F3::get('db_database'),
                            \F3::get('db_username'),
                            \F3::get('db_password')
                        ));
            
            // create tables if necessary
            @\DB::sql('SHOW TABLES');
            $tables = array();
            foreach(\F3::get('DB->result') as $table)
                foreach($table as $key=>$value)
                    $tables[] = $value;
            
            if(!in_array('items', $tables))
                \DB::sql('
                    CREATE TABLE items (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        datetime DATETIME NOT NULL ,
                        title TEXT NOT NULL ,
                        content TEXT NOT NULL ,
                        thumbnail TEXT ,
                        icon TEXT ,
                        unread BOOL NOT NULL ,
                        starred BOOL NOT NULL ,
                        source INT NOT NULL ,
                        uid VARCHAR(255) NOT NULL,
                        link TEXT NOT NULL,
                        INDEX (source)
                    ) ENGINE = MYISAM;
                ');
                
            if(!in_array('sources', $tables))
                \DB::sql('
                    CREATE TABLE sources (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        title TEXT NOT NULL ,
                        tags TEXT NOT NULL ,
                        spout TEXT NOT NULL ,
                        params TEXT NOT NULL ,
                        error TEXT 
                    ) ENGINE = MYISAM;
                ');    
            
			// version 1
			if(!in_array('version', $tables)) {
                \DB::sql('
                    CREATE TABLE version (
                        version INT
                    ) ENGINE = MYISAM;
                ');
				
				\DB::sql('
                    INSERT INTO sources (version) VALUES (2);
                ');
				
				\DB::sql('
					ALTER TABLE sources ADD tags TEXT;
                ');
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
        @\F3::sql("OPTIMIZE TABLE `sources`, `items`");
    }
}
