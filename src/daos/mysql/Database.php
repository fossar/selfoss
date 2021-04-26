<?php

namespace daos\mysql;

use helpers\Configuration;
use Monolog\Logger;

/**
 * Base class for database access -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database implements \daos\DatabaseInterface {
    /** @var Configuration configuration */
    private $configuration;

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
    public function __construct(Configuration $configuration, \DB\SQL $connection, Logger $logger) {
        $this->configuration = $configuration;
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

        if (!in_array($this->configuration->dbPrefix . 'items', $tables, true)) {
            $this->exec('
                CREATE TABLE ' . $this->configuration->dbPrefix . 'items (
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
                BEFORE INSERT ON ' . $this->configuration->dbPrefix . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                BEFORE UPDATE ON ' . $this->configuration->dbPrefix . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
        }

        $isNewestSourcesTable = false;
        if (!in_array($this->configuration->dbPrefix . 'sources', $tables, true)) {
            $this->exec('
                CREATE TABLE ' . $this->configuration->dbPrefix . 'sources (
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
        if (!in_array($this->configuration->dbPrefix . 'version', $tables, true)) {
            $this->exec('
                CREATE TABLE ' . $this->configuration->dbPrefix . 'version (
                    version INT
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            $this->exec('
                INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (8);
            ');

            $this->exec('
                CREATE TABLE ' . $this->configuration->dbPrefix . 'tags (
                    tag         TEXT NOT NULL,
                    color       VARCHAR(7) NOT NULL
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            if ($isNewestSourcesTable === false) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'sources ADD tags TEXT;
                ');
            }
        } else {
            $version = @$this->exec('SELECT version FROM ' . $this->configuration->dbPrefix . 'version ORDER BY version DESC LIMIT 0, 1');
            $version = $version[0]['version'];

            if (strnatcmp($version, '3') < 0) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'sources ADD lastupdate INT;
                ');
                $this->exec('
                    INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (3);
                ');
            }
            if (strnatcmp($version, '4') < 0) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'items ADD updatetime DATETIME;
                ');
                $this->exec('
                    CREATE TRIGGER insert_updatetime_trigger
                    BEFORE INSERT ON ' . $this->configuration->dbPrefix . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                        END;
                ');
                $this->exec('
                    CREATE TRIGGER update_updatetime_trigger
                    BEFORE UPDATE ON ' . $this->configuration->dbPrefix . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                        END;
                ');
                $this->exec('
                    INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (4);
                ');
            }
            if (strnatcmp($version, '5') < 0) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'items ADD author VARCHAR(255);
                ');
                $this->exec('
                    INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (5);
                ');
            }
            if (strnatcmp($version, '6') < 0) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'sources ADD filter TEXT;
                ');
                $this->exec('
                    INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (6);
                ');
            }
            // Jump straight from v6 to v8 due to bug in previous version of the code
            // in \daos\sqlite\Database which
            // set the database version to "7" for initial installs.
            if (strnatcmp($version, '8') < 0) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'sources ADD lastentry INT;
                ');
                $this->exec('
                    INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (8);
                ');
            }
            if (strnatcmp($version, '9') < 0) {
                $this->exec('
                    ALTER TABLE ' . $this->configuration->dbPrefix . 'items ADD shared BOOL;
                ');
                $this->exec('
                    INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (9);
                ');
            }
            if (strnatcmp($version, '10') < 0) {
                $this->exec([
                    'ALTER TABLE `' . $this->configuration->dbPrefix . 'items` CONVERT TO CHARACTER SET utf8mb4;',
                    'ALTER TABLE `' . $this->configuration->dbPrefix . 'sources` CONVERT TO CHARACTER SET utf8mb4;',
                    'ALTER TABLE `' . $this->configuration->dbPrefix . 'tags` CONVERT TO CHARACTER SET utf8mb4;',
                    'ALTER TABLE `' . $this->configuration->dbPrefix . 'version` CONVERT TO CHARACTER SET utf8mb4;',
                    'INSERT INTO `' . $this->configuration->dbPrefix . 'version` (version) VALUES (10);'
                ]);
            }
            if (strnatcmp($version, '11') < 0) {
                $this->exec([
                    'DROP TRIGGER insert_updatetime_trigger',
                    'DROP TRIGGER update_updatetime_trigger',
                    'ALTER TABLE ' . $this->configuration->dbPrefix . 'items ADD lastseen DATETIME',
                    'UPDATE ' . $this->configuration->dbPrefix . 'items SET lastseen = CURRENT_TIMESTAMP',
                    // Needs to be a trigger since MySQL before 5.6.5 does not support default value for DATETIME.
                    // https://dev.mysql.com/doc/relnotes/mysql/5.6/en/news-5-6-5.html#mysqld-5-6-5-data-types
                    // Needs to be a single trigger due to MySQL before 5.7.2 not supporting multiple triggers for the same event on the same table.
                    // https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-2.html#mysqld-5-7-2-triggers
                    'CREATE TRIGGER ' . $this->configuration->dbPrefix . 'items_insert_trigger
                        BEFORE INSERT ON ' . $this->configuration->dbPrefix . 'items FOR EACH ROW
                            BEGIN
                                SET NEW.updatetime = NOW();
                                SET NEW.lastseen = NOW();
                            END;',
                    'CREATE TRIGGER ' . $this->configuration->dbPrefix . 'items_update_trigger
                        BEFORE UPDATE ON ' . $this->configuration->dbPrefix . 'items FOR EACH ROW
                        BEGIN
                            IF (
                                OLD.unread <> NEW.unread OR
                                OLD.starred <> NEW.starred
                            ) THEN
                                SET NEW.updatetime = NOW();
                            END IF;
                        END;',
                    'INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (11)'
                ]);
            }
            if (strnatcmp($version, '12') < 0) {
                $this->exec([
                    'UPDATE ' . $this->configuration->dbPrefix . 'items SET updatetime = datetime WHERE updatetime IS NULL',
                    'ALTER TABLE ' . $this->configuration->dbPrefix . 'items MODIFY updatetime DATETIME NOT NULL',
                    'ALTER TABLE ' . $this->configuration->dbPrefix . 'items MODIFY lastseen DATETIME NOT NULL',
                    'INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (12)'
                ]);
            }
            if (strnatcmp($version, '13') < 0) {
                $this->exec([
                    'UPDATE ' . $this->configuration->dbPrefix . "sources SET spout = 'spouts\\\\rss\\\\fulltextrss' WHERE spout = 'spouts\\\\rss\\\\instapaper'",
                    'INSERT INTO ' . $this->configuration->dbPrefix . 'version (version) VALUES (13)'
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
        @$this->exec('OPTIMIZE TABLE `' . $this->configuration->dbPrefix . 'sources`, `' . $this->configuration->dbPrefix . 'items`');
    }
}
