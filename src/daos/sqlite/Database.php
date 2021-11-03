<?php

namespace daos\sqlite;

use daos\CommonSqlDatabase;
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
        $result = @$this->exec('SELECT name FROM sqlite_master WHERE type = "table"');
        $tables = [];
        foreach ($result as $table) {
            foreach ($table as $key => $value) {
                $tables[] = $value;
            }
        }

        if (!in_array('items', $tables, true)) {
            $this->logger->debug('Creating items table');

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
        }

        $version = $this->getSchemaVersion();

        if ($version < 3) {
            $this->logger->debug('Upgrading database schema to version 3');

            $this->exec('
                ALTER TABLE sources ADD lastupdate INT;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (3);
            ');
        }
        if ($version < 4) {
            $this->logger->debug('Upgrading database schema to version 4');

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
        }
        if ($version < 5) {
            $this->logger->debug('Upgrading database schema to version 5');

            $this->exec('
                ALTER TABLE items ADD author VARCHAR(255);
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (5);
            ');
        }
        if ($version < 6) {
            $this->logger->debug('Upgrading database schema to version 6');

            $this->exec('
                ALTER TABLE sources ADD filter TEXT;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (6);
            ');
        }
        // Jump straight from v6 to v8 due to bug in previous version of the code
        // in \daos\sqlite\Database which
        // set the database version to "7" for initial installs.
        if ($version < 8) {
            $this->logger->debug('Upgrading database schema to version 8');

            $this->exec('
                ALTER TABLE sources ADD lastentry INT;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (8);
            ');

            $this->initLastEntryFieldDuringUpgrade();
        }
        if ($version < 9) {
            $this->logger->debug('Upgrading database schema to version 9');

            $this->exec('
                ALTER TABLE items ADD shared BOOL;
            ');
            $this->exec('
                INSERT INTO version (version) VALUES (9);
            ');
        }
        if ($version < 11) {
            $this->logger->debug('Upgrading database schema to version 11');

            $this->exec([
                // Table needs to be re-created because ALTER TABLE is rather limited.
                // https://sqlite.org/lang_altertable.html#otheralter
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
                )',
                'UPDATE items SET updatetime = datetime WHERE updatetime IS NULL',
                'INSERT INTO new_items SELECT *, CURRENT_TIMESTAMP FROM items',
                'DROP TABLE items',
                'ALTER TABLE new_items RENAME TO items',
                'CREATE INDEX source ON items (source)',
                'CREATE TRIGGER update_updatetime_trigger
                    AFTER UPDATE ON items FOR EACH ROW
                        WHEN (
                            OLD.unread <> NEW.unread OR
                            OLD.starred <> NEW.starred
                        )
                        BEGIN
                            UPDATE items
                            SET updatetime = CURRENT_TIMESTAMP
                            WHERE id = NEW.id;
                        END',
                'INSERT INTO version (version) VALUES (11)',
            ]);
        }
        if ($version < 13) {
            $this->logger->debug('Upgrading database schema to version 13');

            $this->exec([
                "UPDATE sources SET spout = 'spouts\\rss\\fulltextrss' WHERE spout = 'spouts\\rss\\instapaper'",
                'INSERT INTO version (version) VALUES (13)',
            ]);
        }
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
        $this->exec($query, $params);
        $res = $this->exec('SELECT last_insert_rowid() as lastid');

        return (int) $res[0]['lastid'];
    }

    /**
     * optimize database by database own optimize statement
     *
     * @return  void
     */
    public function optimize() {
        @$this->exec('
            VACUUM;
        ');
    }

    /**
     * Initialize 'lastentry' Field in Source table during database upgrade
     *
     * @return void
     */
    private function initLastEntryFieldDuringUpgrade() {
        $sources = @$this->exec('SELECT id FROM sources');

        // have a look at each entry in the source table
        foreach ($sources as $current_src) {
            // get the date of the newest entry found in the database
            $latestEntryDate = @$this->exec(
                'SELECT datetime FROM items WHERE source=? ORDER BY datetime DESC LIMIT 0, 1',
                $current_src['id']
            );

            // if an entry for this source was found in the database, write the date of the newest one into the sources table
            if (isset($latestEntryDate[0]['datetime'])) {
                @$this->exec(
                    'UPDATE sources SET lastentry=? WHERE id=?',
                    strtotime($latestEntryDate[0]['datetime']),
                    $current_src['id']
                );
            }
        }
    }
}
