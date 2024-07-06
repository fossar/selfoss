<?php

declare(strict_types=1);

namespace daos\sqlite;

use daos\CommonSqlDatabase;
use helpers\DatabaseConnection;
use Monolog\Logger;

/**
 * Base class for database access -- sqlite
 *
 * @copyright   Copyright (c) Harald Lapp (harald.lapp@gmail.com)
 * @license     GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Harald Lapp (harald.lapp@gmail.com)
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database implements \daos\DatabaseInterface {
    use CommonSqlDatabase;

    private DatabaseConnection $connection;
    private Logger $logger;

    /**
     * establish connection and create undefined tables
     *
     * @return  void
     */
    public function __construct(DatabaseConnection $connection, Logger $logger) {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->logger->debug('Establishing SQLite database connection');

        $this->migrate();
    }

    private function migrate(): void {
        // create tables if necessary
        $result = @$this->exec('SELECT "name" FROM "sqlite_master" WHERE "type" = \'table\'');
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
                    updatetime  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    author      VARCHAR(255)
                );
            ');

            $this->exec('
                CREATE INDEX source ON items (
                    source
                );
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                AFTER UPDATE ON items FOR EACH ROW
                    BEGIN
                        UPDATE items
                        SET updatetime = CURRENT_TIMESTAMP
                        WHERE id = NEW.id;
                    END;
             ');
            $this->commit();
        }

        $isNewestSourcesTable = false;
        if (!in_array('sources', $tables, true)) {
            $this->logger->debug('Creating sources table');

            $this->exec('
                CREATE TABLE sources (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT,
                    title       TEXT NOT NULL,
                    tags        TEXT,
                    spout       TEXT NOT NULL,
                    params      TEXT NOT NULL,
                    filter      TEXT,
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
                    version INT
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
                    ALTER TABLE sources ADD tags TEXT;
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
                ALTER TABLE items ADD updatetime DATETIME;
            ');
            $this->exec('
                CREATE TRIGGER insert_updatetime_trigger
                AFTER INSERT ON items FOR EACH ROW
                    BEGIN
                        UPDATE items
                        SET updatetime = CURRENT_TIMESTAMP
                        WHERE id = NEW.id;
                    END;
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                AFTER UPDATE ON items FOR EACH ROW
                    BEGIN
                        UPDATE items
                        SET updatetime = CURRENT_TIMESTAMP
                        WHERE id = NEW.id;
                    END;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (4);
            ');
            $this->commit();
        }
        if ($version < 5) {
            $this->logger->debug('Upgrading database schema to version 5');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE items ADD author VARCHAR(255);
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (5);
            ');
            $this->commit();
        }
        if ($version < 6) {
            $this->logger->debug('Upgrading database schema to version 6');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE sources ADD filter TEXT;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (6);
            ');
            $this->commit();
        }
        // Jump straight from v6 to v8 due to bug in previous version of the code
        // in \daos\sqlite\Database which
        // set the database version to "7" for initial installs.
        if ($version < 8) {
            $this->logger->debug('Upgrading database schema to version 8');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE sources ADD lastentry INT;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (8);
            ');

            $this->initLastEntryFieldDuringUpgrade();
            $this->commit();
        }
        if ($version < 9) {
            $this->logger->debug('Upgrading database schema to version 9');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE items ADD shared BOOL;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (9);
            ');
            $this->commit();
        }
        if ($version < 11) {
            $this->logger->debug('Upgrading database schema to version 11');

            $this->beginTransaction();
            // Table needs to be re-created because ALTER TABLE is rather limited.
            // https://sqlite.org/lang_altertable.html#otheralter
            $this->exec(
                'CREATE TABLE new_items (
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
                    updatetime  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    author      VARCHAR(255),
                    shared      BOOL,
                    lastseen    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )'
            );
            $this->exec('UPDATE items SET updatetime = datetime WHERE updatetime IS NULL');
            $this->exec('INSERT INTO new_items SELECT *, CURRENT_TIMESTAMP FROM items');
            $this->exec('DROP TABLE items');
            $this->exec('ALTER TABLE new_items RENAME TO items');
            $this->exec('CREATE INDEX source ON items (source)');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                    AFTER UPDATE ON items FOR EACH ROW
                        WHEN (
                            OLD.unread <> NEW.unread OR
                            OLD.starred <> NEW.starred
                        )
                        BEGIN
                            UPDATE items
                            SET updatetime = CURRENT_TIMESTAMP
                            WHERE id = NEW.id;
                        END
            ');
            $this->exec('INSERT INTO version (version) VALUES (11)');
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
        $this->exec($query, $params);
        $res = $this->exec('SELECT last_insert_rowid() as lastid');

        return (int) $res[0]['lastid'];
    }

    /**
     * optimize database by database own optimize statement
     */
    public function optimize(): void {
        @$this->exec('
            VACUUM;
        ');
    }

    /**
     * Initialize 'lastentry' Field in Source table during database upgrade
     */
    private function initLastEntryFieldDuringUpgrade(): void {
        $sources = @$this->exec('SELECT id FROM sources');

        // have a look at each entry in the source table
        foreach ($sources as $current_src) {
            // get the date of the newest entry found in the database
            $latestEntryDate = @$this->exec(
                'SELECT datetime FROM items WHERE source=:id ORDER BY datetime DESC LIMIT 0, 1',
                [
                    'id' => $current_src['id'],
                ]
            );

            // if an entry for this source was found in the database, write the date of the newest one into the sources table
            if (isset($latestEntryDate[0]['datetime'])) {
                @$this->exec(
                    'UPDATE sources SET lastentry=:entry WHERE id=:id',
                    [
                        'entry' => strtotime($latestEntryDate[0]['datetime']),
                        'id' => $current_src['id'],
                    ]
                );
            }
        }
    }
}
