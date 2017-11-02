<?php

namespace daos\mysql;

use Monolog\Logger;

/**
 * Base class for database access -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database implements \daos\DatabaseInterface {
    /** @var \DB\SQL database connection */
    private $connection;

    /** @var Logger */
    private $logger;

    /**
     * establish connection and
     * create undefined tables
     *
     * @return void
     */
    public function __construct(\DB\SQL $connection, Logger $logger) {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->logger->debug('Established database connection');

        // create tables if necessary
        $result = @$this->exec('SHOW TABLES');
        $tables = [];
        foreach ($result as $table) {
            foreach ($table as $key => $value) {
                $tables[] = $value;
            }
        }

        if (!in_array(\F3::get('db_prefix') . 'items', $tables, true)) {
            $this->exec('
                CREATE TABLE ' . \F3::get('db_prefix') . 'items (
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
            $this->exec('
                CREATE TRIGGER insert_updatetime_trigger
                BEFORE INSERT ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                BEFORE UPDATE ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
        }

        $isNewestSourcesTable = false;
        if (!in_array(\F3::get('db_prefix') . 'sources', $tables, true)) {
            $this->exec('
                CREATE TABLE ' . \F3::get('db_prefix') . 'sources (
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
        if (!in_array(\F3::get('db_prefix') . 'version', $tables, true)) {
            $this->exec('
                CREATE TABLE ' . \F3::get('db_prefix') . 'version (
                    version INT
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            $this->exec('
                INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (8);
            ');

            $this->exec('
                CREATE TABLE ' . \F3::get('db_prefix') . 'tags (
                    tag         TEXT NOT NULL,
                    color       VARCHAR(7) NOT NULL
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            if ($isNewestSourcesTable === false) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'sources ADD tags TEXT;
                ');
            }
        } else {
            $version = @$this->exec('SELECT version FROM ' . \F3::get('db_prefix') . 'version ORDER BY version DESC LIMIT 0, 1');
            $version = $version[0]['version'];

            if (strnatcmp($version, '3') < 0) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'sources ADD lastupdate INT;
                ');
                $this->exec('
                    INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (3);
                ');
            }
            if (strnatcmp($version, '4') < 0) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'items ADD updatetime DATETIME;
                ');
                $this->exec('
                    CREATE TRIGGER insert_updatetime_trigger
                    BEFORE INSERT ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                        END;
                ');
                $this->exec('
                    CREATE TRIGGER update_updatetime_trigger
                    BEFORE UPDATE ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                        END;
                ');
                $this->exec('
                    INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (4);
                ');
            }
            if (strnatcmp($version, '5') < 0) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'items ADD author VARCHAR(255);
                ');
                $this->exec('
                    INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (5);
                ');
            }
            if (strnatcmp($version, '6') < 0) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'sources ADD filter TEXT;
                ');
                $this->exec('
                    INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (6);
                ');
            }
            // Jump straight from v6 to v8 due to bug in previous version of the code
            // in \daos\sqlite\Database which
            // set the database version to "7" for initial installs.
            if (strnatcmp($version, '8') < 0) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'sources ADD lastentry INT;
                ');
                $this->exec('
                    INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (8);
                ');
            }
            if (strnatcmp($version, '9') < 0) {
                $this->exec('
                    ALTER TABLE ' . \F3::get('db_prefix') . 'items ADD shared BOOL;
                ');
                $this->exec('
                    INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (9);
                ');
            }
            if (strnatcmp($version, '10') < 0) {
                $this->exec([
                    'ALTER TABLE `' . \F3::get('db_prefix') . 'items` CONVERT TO CHARACTER SET utf8mb4;',
                    'ALTER TABLE `' . \F3::get('db_prefix') . 'sources` CONVERT TO CHARACTER SET utf8mb4;',
                    'ALTER TABLE `' . \F3::get('db_prefix') . 'tags` CONVERT TO CHARACTER SET utf8mb4;',
                    'ALTER TABLE `' . \F3::get('db_prefix') . 'version` CONVERT TO CHARACTER SET utf8mb4;',
                    'INSERT INTO `' . \F3::get('db_prefix') . 'version` (version) VALUES (10);'
                ]);
            }
            if (strnatcmp($version, '11') < 0) {
                $this->exec([
                    'DROP TRIGGER insert_updatetime_trigger',
                    'DROP TRIGGER update_updatetime_trigger',
                    'ALTER TABLE ' . \F3::get('db_prefix') . 'items ADD lastseen DATETIME',
                    'UPDATE ' . \F3::get('db_prefix') . 'items SET lastseen = CURRENT_TIMESTAMP',
                    // Needs to be a trigger since MySQL before 5.6.5 does not support default value for DATETIME.
                    // https://dev.mysql.com/doc/relnotes/mysql/5.6/en/news-5-6-5.html#mysqld-5-6-5-data-types
                    // Needs to be a single trigger due to MySQL before 5.7.2 not supporting multiple triggers for the same event on the same table.
                    // https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-2.html#mysqld-5-7-2-triggers
                    'CREATE TRIGGER ' . \F3::get('db_prefix') . 'items_insert_trigger
                        BEFORE INSERT ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                            BEGIN
                                SET NEW.updatetime = NOW();
                                SET NEW.lastseen = NOW();
                            END;',
                    'CREATE TRIGGER ' . \F3::get('db_prefix') . 'items_update_trigger
                        BEFORE UPDATE ON ' . \F3::get('db_prefix') . 'items FOR EACH ROW
                        BEGIN
                            IF (
                                OLD.unread <> NEW.unread OR
                                OLD.starred <> NEW.starred
                            ) THEN
                                SET NEW.updatetime = NOW();
                            END IF;
                        END;',
                    'INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (11)'
                ]);
            }
            if (strnatcmp($version, '12') < 0) {
                $this->exec([
                    'UPDATE ' . \F3::get('db_prefix') . 'items SET updatetime = datetime WHERE updatetime IS NULL',
                    'ALTER TABLE ' . \F3::get('db_prefix') . 'items MODIFY updatetime DATETIME NOT NULL',
                    'ALTER TABLE ' . \F3::get('db_prefix') . 'items MODIFY lastseen DATETIME NOT NULL',
                    'INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (12)'
                ]);
            }
            if (strnatcmp($version, '13') < 0) {
                $this->exec([
                    'ALTER TABLE ' . \F3::get('db_prefix') . 'items ADD UNIQUE INDEX (uid (191), source);',
                    'INSERT INTO ' . \F3::get('db_prefix') . 'version (version) VALUES (13)'
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
        $this->exec($query, $params);
        $res = $this->exec('SELECT LAST_INSERT_ID() as lastid');

        return (int) $res[0]['lastid'];
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
     * optimize database by
     * database own optimize statement
     *
     * @return void
     */
    public function optimize() {
        @$this->exec('OPTIMIZE TABLE `' . \F3::get('db_prefix') . 'sources`, `' . \F3::get('db_prefix') . 'items`');
    }
}
