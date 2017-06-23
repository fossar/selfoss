<?php

namespace daos\pgsql;

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
class Database {
    /** @var bool indicates whether database connection was initialized */
    private static $initialized = false;

    /**
     * establish connection and create undefined tables
     *
     * @return  void
     */
    public function __construct() {
        if (self::$initialized === false && \F3::get('db_type') == 'pgsql') {
            $host = \F3::get('db_host');
            $port = \F3::get('db_port');
            $database = \F3::get('db_database');

            if ($port) {
                $dsn = "pgsql:host=$host; port=$port; dbname=$database";
            } else {
                $dsn = "pgsql:host=$host; dbname=$database";
            }

            \F3::get('logger')->debug('Establish database connection');
            \F3::set('db', new \DB\SQL(
                $dsn,
                \F3::get('db_username'),
                \F3::get('db_password')
            ));

            // create tables if necessary
            $result = @\F3::get('db')->exec("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
            $tables = [];
            foreach ($result as $table) {
                foreach ($table as $key => $value) {
                    $tables[] = $value;
                }
            }

            if (!in_array('items', $tables)) {
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
            if (!in_array('sources', $tables)) {
                \F3::get('db')->exec('
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
            if (!in_array('version', $tables)) {
                \F3::get('db')->exec('
                    CREATE TABLE version (
                        version INTEGER
                    );
                ');

                \F3::get('db')->exec('
                    INSERT INTO version (version) VALUES (8);
                ');

                \F3::get('db')->exec('
                    CREATE TABLE tags (
                        tag         TEXT NOT NULL,
                        color       TEXT NOT NULL
                    );
                ');

                if ($isNewestSourcesTable === false) {
                    \F3::get('db')->exec('
                        ALTER TABLE sources ADD COLUMN tags TEXT;
                    ');
                }
            } else {
                $version = @\F3::get('db')->exec('SELECT version FROM version ORDER BY version DESC LIMIT 1');
                $version = $version[0]['version'];

                if (strnatcmp($version, '3') < 0) {
                    \F3::get('db')->exec('
                        ALTER TABLE sources ADD lastupdate INT;
                    ');
                    \F3::get('db')->exec('
                        INSERT INTO version (version) VALUES (3);
                    ');
                }
                if (strnatcmp($version, '4') < 0) {
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
                if (strnatcmp($version, '5') < 0) {
                    \F3::get('db')->exec([
                        'ALTER TABLE items ADD author TEXT;',
                        'INSERT INTO version (version) VALUES (5);'
                    ]);
                }
                if (strnatcmp($version, '6') < 0) {
                    \F3::get('db')->exec([
                        'ALTER TABLE sources ADD filter TEXT;',
                        'INSERT INTO version (version) VALUES (6);'
                    ]);
                }
                // Jump straight from v6 to v8 due to bug in previous version of the code
                // in /daos/sqlite/Database.php which
                // set the database version to "7" for initial installs.
                if (strnatcmp($version, '8') < 0) {
                    \F3::get('db')->exec([
                        'ALTER TABLE sources ADD lastentry INT;',
                        'INSERT INTO version (version) VALUES (8);'
                    ]);
                }
                if (strnatcmp($version, '9') < 0) {
                    \F3::get('db')->exec([
                        'ALTER TABLE items ADD shared BOOLEAN;',
                        'INSERT INTO version (version) VALUES (9);'
                    ]);
                }
                if (strnatcmp($version, '10') < 0) {
                    \F3::get('db')->exec([
                        'ALTER TABLE items ALTER COLUMN datetime SET DATA TYPE timestamp(0) with time zone;',
                        'ALTER TABLE items ALTER COLUMN updatetime SET DATA TYPE timestamp(0) with time zone;',
                        'INSERT INTO version (version) VALUES (10);'
                    ]);
                }
                if (strnatcmp($version, '11') < 0) {
                    \F3::get('db')->exec([
                        'DROP TRIGGER update_updatetime_trigger ON items',
                        'ALTER TABLE items ADD lastseen TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW()',
                        'CREATE TRIGGER update_updatetime_trigger
                            BEFORE UPDATE ON items FOR EACH ROW
                            WHEN (
                                OLD.unread IS DISTINCT FROM NEW.unread OR
                                OLD.starred IS DISTINCT FROM NEW.starred
                            )
                            EXECUTE PROCEDURE update_updatetime_procedure();',
                        'INSERT INTO version (version) VALUES (11);'
                    ]);
                }
                if (strnatcmp($version, '12') < 0) {
                    \F3::get('db')->exec([
                        'UPDATE items SET updatetime = datetime WHERE updatetime IS NULL',
                        'ALTER TABLE items ALTER COLUMN updatetime SET NOT NULL',
                        'INSERT INTO version (version) VALUES (12)'
                    ]);
                }
            }

            // just initialize once
            self::$initialized = true;
        }
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
