<?php

declare(strict_types=1);

namespace daos\mysql;

use daos\CommonSqlDatabase;
use helpers\DatabaseConnection;
use Monolog\Logger;

/**
 * Base class for database access -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database implements \daos\DatabaseInterface {
    use CommonSqlDatabase;

    /** @var DatabaseConnection database connection */
    private $connection;

    /** @var Logger */
    private $logger;

    /**
     * establish connection and
     * create undefined tables
     */
    public function __construct(DatabaseConnection $connection, Logger $logger) {
        $this->connection = $connection;
        $this->logger = $logger;

        $this->logger->debug('Establishing MySQL database connection');

        // create tables if necessary
        $result = @$this->exec('SHOW TABLES');
        $tables = [];
        foreach ($result as $table) {
            foreach ($table as $key => $value) {
                $tables[] = $value;
            }
        }

        if (!in_array($this->connection->getTableNamePrefix() . 'items', $tables, true)) {
            $this->logger->debug('Creating items table');

            $this->beginTransaction();
            $this->exec('
                CREATE TABLE ' . $this->connection->getTableNamePrefix() . 'items (
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
                BEFORE INSERT ON ' . $this->connection->getTableNamePrefix() . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                BEFORE UPDATE ON ' . $this->connection->getTableNamePrefix() . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
            $this->commit();
        }

        $isNewestSourcesTable = false;
        if (!in_array($this->connection->getTableNamePrefix() . 'sources', $tables, true)) {
            $this->logger->debug('Creating sources table');

            $this->exec('
                CREATE TABLE ' . $this->connection->getTableNamePrefix() . 'sources (
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
        if (!in_array($this->connection->getTableNamePrefix() . 'version', $tables, true)) {
            $this->logger->debug('Upgrading database schema to version 8 from initial state');

            $this->beginTransaction();
            $this->exec('
                CREATE TABLE ' . $this->connection->getTableNamePrefix() . 'version (
                    version INT
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (8);
            ');

            $this->exec('
                CREATE TABLE ' . $this->connection->getTableNamePrefix() . 'tags (
                    tag         TEXT NOT NULL,
                    color       VARCHAR(7) NOT NULL
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;
            ');

            if ($isNewestSourcesTable === false) {
                $this->exec('
                    ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'sources ADD tags TEXT;
                ');
            }
            $this->commit();
        }

        $version = $this->getSchemaVersion();

        if ($version < 3) {
            $this->logger->debug('Upgrading database schema to version 3');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'sources ADD lastupdate INT;
            ');
            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (3);
            ');
            $this->commit();
        }
        if ($version < 4) {
            $this->logger->debug('Upgrading database schema to version 4');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'items ADD updatetime DATETIME;
            ');
            $this->exec('
                CREATE TRIGGER insert_updatetime_trigger
                BEFORE INSERT ON ' . $this->connection->getTableNamePrefix() . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
            $this->exec('
                CREATE TRIGGER update_updatetime_trigger
                BEFORE UPDATE ON ' . $this->connection->getTableNamePrefix() . 'items FOR EACH ROW
                    BEGIN
                        SET NEW.updatetime = NOW();
                    END;
            ');
            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (4);
            ');
            $this->commit();
        }
        if ($version < 5) {
            $this->logger->debug('Upgrading database schema to version 5');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'items ADD author VARCHAR(255);
            ');
            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (5);
            ');
            $this->commit();
        }
        if ($version < 6) {
            $this->logger->debug('Upgrading database schema to version 6');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'sources ADD filter TEXT;
            ');
            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (6);
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
                ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'sources ADD lastentry INT;
            ');
            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (8);
            ');
            $this->commit();
        }
        if ($version < 9) {
            $this->logger->debug('Upgrading database schema to version 9');

            $this->beginTransaction();
            $this->exec('
                ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'items ADD shared BOOL;
            ');
            $this->exec('
                INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (9);
            ');
            $this->commit();
        }
        if ($version < 10) {
            $this->logger->debug('Upgrading database schema to version 10');

            $this->beginTransaction();
            $this->exec('ALTER TABLE `' . $this->connection->getTableNamePrefix() . 'items` CONVERT TO CHARACTER SET utf8mb4;');
            $this->exec('ALTER TABLE `' . $this->connection->getTableNamePrefix() . 'sources` CONVERT TO CHARACTER SET utf8mb4;');
            $this->exec('ALTER TABLE `' . $this->connection->getTableNamePrefix() . 'tags` CONVERT TO CHARACTER SET utf8mb4;');
            $this->exec('ALTER TABLE `' . $this->connection->getTableNamePrefix() . 'version` CONVERT TO CHARACTER SET utf8mb4;');
            $this->exec('INSERT INTO `' . $this->connection->getTableNamePrefix() . 'version` (version) VALUES (10);');
            $this->commit();
        }
        if ($version < 11) {
            $this->logger->debug('Upgrading database schema to version 11');

            $this->beginTransaction();
            $this->exec('DROP TRIGGER insert_updatetime_trigger');
            $this->exec('DROP TRIGGER update_updatetime_trigger');
            $this->exec('ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'items ADD lastseen DATETIME');
            $this->exec('UPDATE ' . $this->connection->getTableNamePrefix() . 'items SET lastseen = CURRENT_TIMESTAMP');
            // Needs to be a trigger since MySQL before 5.6.5 does not support default value for DATETIME.
            // https://dev.mysql.com/doc/relnotes/mysql/5.6/en/news-5-6-5.html#mysqld-5-6-5-data-types
            // Needs to be a single trigger due to MySQL before 5.7.2 not supporting multiple triggers for the same event on the same table.
            // https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-2.html#mysqld-5-7-2-triggers
            $this->exec('
                CREATE TRIGGER ' . $this->connection->getTableNamePrefix() . 'items_insert_trigger
                    BEFORE INSERT ON ' . $this->connection->getTableNamePrefix() . 'items FOR EACH ROW
                        BEGIN
                            SET NEW.updatetime = NOW();
                            SET NEW.lastseen = NOW();
                        END;
            ');
            $this->exec('
                CREATE TRIGGER ' . $this->connection->getTableNamePrefix() . 'items_update_trigger
                    BEFORE UPDATE ON ' . $this->connection->getTableNamePrefix() . 'items FOR EACH ROW
                    BEGIN
                        IF (
                            OLD.unread <> NEW.unread OR
                            OLD.starred <> NEW.starred
                        ) THEN
                            SET NEW.updatetime = NOW();
                        END IF;
                    END;
            ');
            $this->exec('INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (11)');
            $this->commit();
        }
        if ($version < 12) {
            $this->logger->debug('Upgrading database schema to version 12');

            $this->beginTransaction();
            $this->exec('UPDATE ' . $this->connection->getTableNamePrefix() . 'items SET updatetime = datetime WHERE updatetime IS NULL');
            $this->exec('ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'items MODIFY updatetime DATETIME NOT NULL');
            $this->exec('ALTER TABLE ' . $this->connection->getTableNamePrefix() . 'items MODIFY lastseen DATETIME NOT NULL');
            $this->exec('INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (12)');
            $this->commit();
        }
        if ($version < 13) {
            $this->logger->debug('Upgrading database schema to version 13');

            $this->beginTransaction();
            $this->exec('UPDATE ' . $this->connection->getTableNamePrefix() . "sources SET spout = 'spouts\\\\rss\\\\fulltextrss' WHERE spout = 'spouts\\\\rss\\\\instapaper'");
            $this->exec('INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (13)');
            $this->commit();
        }
        if ($version < 14) {
            $this->logger->debug('Upgrading database schema to version 14');

            $this->beginTransaction();
            $this->exec('UPDATE ' . $this->connection->getTableNamePrefix() . "items SET author = NULL WHERE author = ''");
            $this->exec('INSERT INTO ' . $this->connection->getTableNamePrefix() . 'version (version) VALUES (14)');
            $this->commit();
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
    public function insert(string $query, array $params): int {
        $this->exec($query, $params);
        $res = $this->exec('SELECT LAST_INSERT_ID() as lastid');

        return (int) $res[0]['lastid'];
    }

    /**
     * optimize database by
     * database own optimize statement
     */
    public function optimize(): void {
        @$this->exec('OPTIMIZE TABLE `' . $this->connection->getTableNamePrefix() . 'sources`, `' . $this->connection->getTableNamePrefix() . 'items`');
    }
}
