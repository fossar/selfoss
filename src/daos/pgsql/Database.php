<?php

declare(strict_types=1);

namespace Selfoss\daos\pgsql;

use Monolog\Logger;
use Selfoss\daos\CommonSqlDatabase;
use Selfoss\helpers\DatabaseConnection;

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
final class Database implements \Selfoss\daos\DatabaseInterface {
    use CommonSqlDatabase;

    /**
     * establish connection and create undefined tables
     *
     * @return  void
     */
    public function __construct(
        private DatabaseConnection $connection,
        private Logger $logger
    ) {
        $this->logger->debug('Establishing PostgreSQL database connection');

        $this->migrate();
    }

    private function migrate(): void {
        // create tables if necessary
        $result = @$this->exec("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
        $tables = [];
        foreach ($result as $table) {
            foreach ($table as $key => $value) {
                $tables[] = $value;
            }
        }

        if (!in_array('items', $tables, true)) {
            $this->logger->debug('Creating items table');

            $this->beginTransaction();
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
            $this->commit();
        }

        $isNewestSourcesTable = false;
        if (!in_array('sources', $tables, true)) {
            $this->logger->debug('Creating sources table');

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
            $this->logger->debug('Upgrading database schema to version 8 from initial state');

            $this->beginTransaction();
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
            $this->commit();
        }

        $version = $this->getSchemaVersion();

        if ($version < 3) {
            $this->logger->debug('Upgrading database schema to version 3');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE sources ADD lastupdate INT;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (3);
            ');
            $this->commit();
        }
        if ($version < 4) {
            $this->logger->debug('Upgrading database schema to version 4');

            $this->beginTransaction();
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
            $this->commit();
        }
        if ($version < 5) {
            $this->logger->debug('Upgrading database schema to version 5');

            $this->beginTransaction();
            $this->exec('ALTER TABLE items ADD author TEXT;');
            $this->exec('INSERT INTO version (version) VALUES (5);');
            $this->commit();
        }
        if ($version < 6) {
            $this->logger->debug('Upgrading database schema to version 6');

            $this->beginTransaction();
            $this->exec('ALTER TABLE sources ADD filter TEXT;');
            $this->exec('INSERT INTO version (version) VALUES (6);');
            $this->commit();
        }
        // Jump straight from v6 to v8 due to bug in previous version of the code
        // in \Selfoss\daos\sqlite\Database which
        // set the database version to "7" for initial installs.
        if ($version < 8) {
            $this->logger->debug('Upgrading database schema to version 8');

            $this->beginTransaction();
            $this->exec('ALTER TABLE sources ADD lastentry INT;');
            $this->exec('INSERT INTO version (version) VALUES (8);');
            $this->commit();
        }
        if ($version < 9) {
            $this->logger->debug('Upgrading database schema to version 9');

            $this->beginTransaction();
            $this->exec('ALTER TABLE items ADD shared BOOLEAN;');
            $this->exec('INSERT INTO version (version) VALUES (9);');
            $this->commit();
        }
        if ($version < 10) {
            $this->logger->debug('Upgrading database schema to version 10');

            $this->beginTransaction();
            $this->exec('ALTER TABLE items ALTER COLUMN datetime SET DATA TYPE timestamp(0) with time zone;');
            $this->exec('ALTER TABLE items ALTER COLUMN updatetime SET DATA TYPE timestamp(0) with time zone;');
            $this->exec('INSERT INTO version (version) VALUES (10);');
            $this->commit();
        }
        if ($version < 11) {
            $this->logger->debug('Upgrading database schema to version 11');

            $this->beginTransaction();
            $this->exec('DROP TRIGGER update_updatetime_trigger ON items');
            $this->exec('ALTER TABLE items ADD lastseen TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW()');
            $this->exec(
                'CREATE TRIGGER update_updatetime_trigger
                    BEFORE UPDATE ON items FOR EACH ROW
                    WHEN (
                        OLD.unread IS DISTINCT FROM NEW.unread OR
                        OLD.starred IS DISTINCT FROM NEW.starred
                    )
                    EXECUTE PROCEDURE update_updatetime_procedure();'
            );
            $this->exec('INSERT INTO version (version) VALUES (11);');
            $this->commit();
        }
        if ($version < 12) {
            $this->logger->debug('Upgrading database schema to version 12');

            $this->beginTransaction();
            $this->exec('UPDATE items SET updatetime = datetime WHERE updatetime IS NULL');
            $this->exec('ALTER TABLE items ALTER COLUMN updatetime SET NOT NULL');
            $this->exec('INSERT INTO version (version) VALUES (12)');
            $this->commit();
        }
        if ($version < 13) {
            $this->logger->debug('Upgrading database schema to version 13');

            $this->beginTransaction();
            $this->exec("UPDATE sources SET spout = 'spouts\\rss\\fulltextrss' WHERE spout = 'spouts\\rss\\instapaper'");
            $this->exec('INSERT INTO version (version) VALUES (13)');
            $this->commit();
        }
        if ($version < 14) {
            $this->logger->debug('Upgrading database schema to version 14');

            $this->beginTransaction();
            $this->exec("UPDATE items SET author = NULL WHERE author = ''");
            $this->exec('INSERT INTO version (version) VALUES (14)');
            $this->commit();
        }
    }

    /**
     * wrap insert statement to return id
     *
     * @param string $query sql statement
     * @param array<string, mixed> $params sql params
     *
     * @return int id after insert
     */
    public function insert(string $query, array $params): int {
        $res = $this->exec("$query RETURNING id", $params);

        return $res[0]['id'];
    }

    /**
     * optimize database by the database's own optimize statement
     *
     * Note that for pg, no optimization is needed because autovacuuming is
     * enabled by default.
     * See
     * https://www.postgresql.org/docs/9.1/static/runtime-config-autovacuum.html
     */
    public function optimize(): void {
    }
}
