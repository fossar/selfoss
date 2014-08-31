<?PHP

namespace daos\pgsql;
    
/**
 * Base class for database access -- postgresql
 *
 * Note that before use you'll want to create the database itself. See 
 * http://www.postgresql.org/docs/8.4/static/manage-ag-createdb.html for full information.
 * In a nutshell (from the command line), as the administrative user (postgres),
 * execute "createdb -O USER DBNAME" where USER is the user you will be connecting as
 * and DBNAME is the database to create. Administering users (roles) and authentication
 * is out of scope for this comment, but the online postgresql documentation is comprehensive.
 *
 * @package     daos
 * @copyright   Copyright (c) Michael Jackson <michael.o.jackson@gmail.com>
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Michael Jackson <michael.o.jackson@gmail.com>
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
        if (self::$initialized === false && \F3::get('db_type')=="pgsql") {
            // establish database connection
            \F3::set('db', new \DB\SQL(
                'pgsql:host=' . \F3::get('db_host') . ';port=' . \F3::get('db_port') . ';dbname='.\F3::get('db_database'),
                \F3::get('db_username'),
                \F3::get('db_password')
            ));
            
            // create tables if necessary
            $result = @\F3::get('db')->exec("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
            $tables = array();
            foreach($result as $table)
                foreach($table as $key=>$value)
                    $tables[] = $value;
            
            if(!in_array('items', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE items (
                        id          SERIAL PRIMARY KEY,
                        datetime    TIMESTAMP WITH TIME ZONE NOT NULL,
                        title       TEXT NOT NULL,
                        content     TEXT NOT NULL,
                        thumbnail   TEXT,
                        icon        TEXT,
                        unread      BOOLEAN NOT NULL,
                        starred     BOOLEAN NOT NULL,
                        source      INTEGER NOT NULL,
                        uid         TEXT NOT NULL,
                        link        TEXT NOT NULL,
                        updatetime  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        author      TEXT
                    );
                ');
                \F3::get('db')->exec('
                    CREATE INDEX source ON items (
                        source
                    );
                ');
                \F3::get('db')->exec('
                    CREATE OR REPLACE FUNCTION update_updatetime_procedure()
                    RETURNS TRIGGER AS $$
                        BEGIN
                            NEW.updatetime = NOW();
                            RETURN NEW;
                        END;
                    $$ LANGUAGE "plpgsql";
                ');
                \F3::get('db')->exec('
                    CREATE TRIGGER update_updatetime_trigger
                    BEFORE UPDATE ON items FOR EACH ROW EXECUTE PROCEDURE
                    update_updatetime_procedure();
                ');
            }
            
            $isNewestSourcesTable = false;
            if(!in_array('sources', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE sources (
                        id          SERIAL PRIMARY KEY,
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
                        version INTEGER
                    );
                ');
                
                \F3::get('db')->exec('
                    INSERT INTO version (version) VALUES (4);
                ');
                
                \F3::get('db')->exec('
                    CREATE TABLE tags (
                        tag         TEXT NOT NULL,
                        color       TEXT NOT NULL
                    );
                ');
                
                if($isNewestSourcesTable===false) {
                    \F3::get('db')->exec('
                        ALTER TABLE sources ADD COLUMN tags TEXT;
                    ');
                }
            }
            else{
                $version = @\F3::get('db')->exec('SELECT version FROM version ORDER BY version DESC LIMIT 1');
                $version = $version[0]['version'];

                if(strnatcmp($version, "3") < 0){
                    \F3::get('db')->exec('
                        ALTER TABLE sources ADD lastupdate INT;
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO version (version) VALUES (3);
                    ');
                }
                if(strnatcmp($version, "4") < 0){
                    \F3::get('db')->exec('
                        ALTER TABLE items ADD updatetime TIMESTAMP WITH TIME ZONE;
                    ');
                    \F3::get('db')->exec('
                        ALTER TABLE items ALTER COLUMN updatetime SET DEFAULT CURRENT_TIMESTAMP;
                    ');
                    \F3::get('db')->exec('
                        CREATE OR REPLACE FUNCTION update_updatetime_procedure()
                        RETURNS TRIGGER AS $$
                            BEGIN
                                NEW.updatetime = NOW();
                                RETURN NEW;
                            END;
                        $$ LANGUAGE "plpgsql";
                    ');
                    \F3::get('db')->exec('
                        CREATE TRIGGER update_updatetime_trigger
                        BEFORE UPDATE ON items FOR EACH ROW EXECUTE PROCEDURE
                        update_updatetime_procedure();
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO version (version) VALUES (4);
                    ');
                }
            }
            
            // just initialize once
            $initialized = true;
        }
    }
    
    
    /**
     * optimize database by the database's own optimize statement
     *
     * Note that for pg, for full optimization you'd run "vacuum full analyze {table}".  This does require
     * an exclusive lock on the table though and so this is probably best run offline during scheduled
     * downtime.  See http://www.postgresql.org/docs/8.4/static/sql-vacuum.html for more information
     * (particularly the notes in the footer of that page leading to further DBA-related info e.g. the
     * autovacuum daemon).
     *
     * @return  void
     */
    public function optimize() {
        $result = @\F3::get('db')->exec("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
        $tables = array();
        foreach($result as $table)
            foreach($table as $key=>$value)
                @\F3::get('db')->exec("VACUUM ANALYZE " . $value);
    }
}
