<?php

namespace daos\pgsql;

use Monolog\Logger;

/**
 * Base class for database access -- postgresql
 *
 * Note that before use you'll want to create the database itself. See
 * https://www.postgresql.org/docs/9.6/static/manage-ag-createdb.html for full information.
 * In a nutshell (from the command line), as the administrative user (postgres),
 * execute "createdb -O USER DBNAME" where USER is the user you will be connecting as
 * and DBNAME is the database to create. Administering users (roles) and authentication
 * is out of scope for this comment, but the online postgresql documentation is comprehensive.
 *
 * @copyright   Copyright (c) Michael Jackson <michael.o.jackson@gmail.com>
 * @license     GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Michael Jackson <michael.o.jackson@gmail.com>
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database implements \daos\DatabaseInterface {
    /** @var \DB\SQL database connection */
    private $connection;

    /** @var Logger */
    private $logger;

    /**
     * establish connection and create undefined tables
     *
     * @return  void
     */
    public function __construct(\DB\SQL $connection, Logger $logger) {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->logger->debug('Established database connection');

        // create tables if necessary
        $result = @$this->exec("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
        $tables = [];
        foreach ($result as $table) {
            foreach ($table as $key => $value) {
                $tables[] = $value;
            }
        }

        if (!in_array('items', $tables, true)) {
            $this->exec('
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
            $this->exec('
                CREATE INDEX source ON items (
                    source
                );
            ');
            $this->exec('
                CREATE OR REPLACE FUNCTION update_updatetime_procedure()
                RETURNS TRIGGER AS $$
                    BEGIN
                        NEW.updatetime = NOW();
                        RETURN NEW;
                    END;
                $$ LANGUAGE "plpgsql";
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                BEFORE UPDATE ON items FOR EACH ROW EXECUTE PROCEDURE
                update_updatetime_procedure();
            ');
        }

        $isNewestSourcesTable = false;
        if (!in_array('sources', $tables, true)) {
            $this->exec('
                CREATE TABLE sources (
                    id          SERIAL PRIMARY KEY,
                    title       TEXT NOT NULL,
                    tags        TEXT,
                    filter      TEXT,
                    spout       TEXT NOT NULL,
                    params      TEXT NOT NULL,
                    error       TEXT,
                    lastupdate  INTEGER,
                    lastentry   INTEGER
                );
            ');
            $isNewestSourcesTable = true;
        }

        // version 1
        if (!in_array('version', $tables, true)) {
            $this->exec('
                CREATE TABLE version (
                    version INTEGER
                );
            ');

            $this->exec('
                INSERT INTO version (version) VALUES (8);
            ');

            $this->exec('
                CREATE TABLE tags (
                    tag         TEXT NOT NULL,
                    color       TEXT NOT NULL
                );
            ');

            if ($isNewestSourcesTable === false) {
                $this->exec('
                    ALTER TABLE sources ADD COLUMN tags TEXT;
                ');
            }
        } else {
            $version = @$this->exec('SELECT version FROM version ORDER BY version DESC LIMIT 1');
            $version = $version[0]['version'];

            if (strnatcmp($version, '3') < 0) {
                $this->exec('
                    ALTER TABLE sources ADD lastupdate INT;
                ');
                $this->exec('
                    INSERT INTO version (version) VALUES (3);
                ');
            }
            if (strnatcmp($version, '4') < 0) {
                $this->exec('
                    ALTER TABLE items ADD updatetime TIMESTAMP WITH TIME ZONE;
                ');
                $this->exec('
                    ALTER TABLE items ALTER COLUMN updatetime SET DEFAULT CURRENT_TIMESTAMP;
                ');
                $this->exec('
                    CREATE OR REPLACE FUNCTION update_updatetime_procedure()
                    RETURNS TRIGGER AS $$
                        BEGIN
                            NEW.updatetime = NOW();
                            RETURN NEW;
                        END;
                    $$ LANGUAGE "plpgsql";
                ');
                $this->exec('
                    CREATE TRIGGER update_updatetime_trigger
                    BEFORE UPDATE ON items FOR EACH ROW EXECUTE PROCEDURE
                    update_updatetime_procedure();
                ');
                $this->exec('
                    INSERT INTO version (version) VALUES (4);
                ');
            }
            if (strnatcmp($version, '5') < 0) {
                $this->exec([
                    'ALTER TABLE items ADD author TEXT;',
                    'INSERT INTO version (version) VALUES (5);',
                ]);
            }
            if (strnatcmp($version, '6') < 0) {
                $this->exec([
                    'ALTER TABLE sources ADD filter TEXT;',
                    'INSERT INTO version (version) VALUES (6);',
                ]);
            }
            // Jump straight from v6 to v8 due to bug in previous version of the code
            // in \daos\sqlite\Database which
            // set the database version to "7" for initial installs.
            if (strnatcmp($version, '8') < 0) {
                $this->exec([
                    'ALTER TABLE sources ADD lastentry INT;',
                    'INSERT INTO version (version) VALUES (8);',
                ]);
            }
            if (strnatcmp($version, '9') < 0) {
                $this->exec([
                    'ALTER TABLE items ADD shared BOOLEAN;',
                    'INSERT INTO version (version) VALUES (9);',
                ]);
            }
            if (strnatcmp($version, '10') < 0) {
                $this->exec([
                    'ALTER TABLE items ALTER COLUMN datetime SET DATA TYPE timestamp(0) with time zone;',
                    'ALTER TABLE items ALTER COLUMN updatetime SET DATA TYPE timestamp(0) with time zone;',
                    'INSERT INTO version (version) VALUES (10);',
                ]);
            }
            if (strnatcmp($version, '11') < 0) {
                $this->exec([
                    'DROP TRIGGER update_updatetime_trigger ON items',
                    'ALTER TABLE items ADD lastseen TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW()',
                    'CREATE TRIGGER update_updatetime_trigger
                        BEFORE UPDATE ON items FOR EACH ROW
                        WHEN (
                            OLD.unread IS DISTINCT FROM NEW.unread OR
                            OLD.starred IS DISTINCT FROM NEW.starred
                        )
                        EXECUTE PROCEDURE update_updatetime_procedure();',
                    'INSERT INTO version (version) VALUES (11);',
                ]);
            }
            if (strnatcmp($version, '12') < 0) {
                $this->exec([
                    'UPDATE items SET updatetime = datetime WHERE updatetime IS NULL',
                    'ALTER TABLE items ALTER COLUMN updatetime SET NOT NULL',
                    'INSERT INTO version (version) VALUES (12)',
                ]);
            }
            if (strnatcmp($version, '13') < 0) {
                $this->exec([
                    "UPDATE sources SET spout = 'spouts\\rss\\fulltextrss' WHERE spout = 'spouts\\rss\\instapaper'",
                    'INSERT INTO version (version) VALUES (13)',
                ]);
            }
        }
    }

    public function exec($cmds, $args = null) {
        return $this->connection->exec($cmds, $args);
    }

    /**
     * wrap insert statement to return id
     *
     * @param string $query sql statement
     * @param array $params sql params
     *
     * @return int id after insert
     */
    public function insert($query, array $params) {
        $res = $this->exec("$query RETURNING id", $params);

        return $res[0]['id'];
    }

    public function quote($value, $type = \PDO::PARAM_STR) {
        return $this->connection->quote($value, $type);
    }

    /**
     * Begin SQL transaction
     *
     * @return bool
     */
    public function begin() {
        return $this->connection->begin();
    }

    /**
     * Rollback SQL transaction
     *
     * @return bool
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Commit SQL transaction
     *
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * optimize database by the database's own optimize statement
     *
     * Note that for pg, no optimization is needed because autovacuuming is
     * enabled by default.
     * See
     * https://www.postgresql.org/docs/9.1/static/runtime-config-autovacuum.html
     *
     * @return  void
     */
    public function optimize() {
    }
}
