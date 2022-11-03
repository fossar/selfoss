<?php

namespace helpers;

use Monolog\Logger;
use PDO;
use PDOException;

/**
 * Simple wrapper around PDO taken from F3.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 * Copyright (c) 2009-2019 F3::Factory/Bong Cosca
 */
class DatabaseConnection {
    /** @var PDO Original PDO connection */
    private $pdo;

    /** @var string */
    private $tableNamePrefix;

    /** @var bool whether a transaction is currently in progress */
    private $isInTransaction = false;

    /** @var Logger */
    private $logger;

    /**
     * Instantiate class
     *
     * @param string $dsn
     * @param string $user
     * @param string $pw
     * @param string $tableNamePrefix
     **/
    public function __construct(Logger $logger, $dsn, $user = null, $pw = null, array $options = [], $tableNamePrefix = '') {
        $this->logger = $logger;
        $this->logger->debug('Creating database connection', ['dsn' => $dsn]);
        $this->pdo = new PDO($dsn, $user, $pw, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->tableNamePrefix = $tableNamePrefix;
    }

    /**
     * Begin SQL transaction
     *
     * @return bool
     **/
    public function beginTransaction() {
        $out = $this->pdo->beginTransaction();
        $this->isInTransaction = true;

        return $out;
    }

    /**
     * Roll back SQL transaction
     *
     * @return bool
     **/
    public function rollBack() {
        $out = false;
        if ($this->pdo->inTransaction()) {
            $out = $this->pdo->rollBack();
        }
        $this->isInTransaction = false;

        return $out;
    }

    /**
     * Commit SQL transaction
     *
     * @return bool
     **/
    public function commit() {
        $out = false;
        if ($this->pdo->inTransaction()) {
            $out = $this->pdo->commit();
        }
        $this->isInTransaction = false;

        return $out;
    }

    /**
     * @return string
     **/
    public function getTableNamePrefix() {
        return $this->tableNamePrefix;
    }

    /**
     * Return transaction flag
     *
     * @return bool
     **/
    private function isInTransaction() {
        return $this->isInTransaction;
    }

    /**
     * Map data type of argument to a PDO constant
     *
     * @param scalar $val
     *
     * @return int
     **/
    private function type($val) {
        switch (gettype($val)) {
            case 'NULL':
                return PDO::PARAM_NULL;
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'integer':
                return PDO::PARAM_INT;
            case 'resource':
                return PDO::PARAM_LOB;
            default:
                return PDO::PARAM_STR;
        }
    }

    /**
     * Execute SQL statement.
     *
     * @param string $cmd
     * @param array|scalar $args
     *
     * @return \PDOStatement
     **/
    public function execute($cmd, $args = []) {
        if (is_scalar($args)) {
            $args = [1 => $args];
        }

        // ensure 1-based arguments
        if (array_key_exists(0, $args)) {
            array_unshift($args, '');
            unset($args[0]);
        }

        try {
            $query = $this->pdo->prepare($cmd);
        } catch (PDOException $e) {
            // PDO-level error occurred
            if ($this->isInTransaction) {
                $this->rollBack();
            }

            throw $e;
        }

        foreach ($args as $key => $val) {
            if (is_array($val)) {
                // User-specified data type
                if (count($val) !== 2) {
                    throw new \InvalidArgumentException('Expected [$value, $type] for binding.');
                }

                [$value, $type] = $val;
                $query->bindValue($key, $value, $type);
            } else {
                // Convert to PDO data type
                $type = $this->type($val);
                $query->bindValue($key, $val, $type);
            }
        }

        try {
            $query->execute();
        } catch (PDOException $e) {
            // Statement-level error occurred
            if ($this->isInTransaction) {
                $this->rollBack();
            }

            throw $e;
        }

        return $query;
    }

    /**
     * Execute SQL statement and fetch the result as an associative array (when applicable).
     *
     * @param string $cmd
     * @param array|scalar $args
     *
     * @return ?array
     **/
    public function exec($cmd, $args = []) {
        $statement = $this->execute($cmd, $args);

        $result = null;
        if ($statement->columnCount() !== 0) {
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        $statement->closeCursor();
        unset($statement);

        return $result;
    }

    /**
     * Quote string
     *
     * @param mixed $val
     * @param int $type
     *
     * @return string
     **/
    public function quote($val, $type = PDO::PARAM_STR) {
        return $this->pdo->quote($val, $type);
    }

    /**
     * Redirect call to PDO object
     *
     * @param string $func
     *
     * @return mixed
     **/
    public function __call($func, array $args) {
        return call_user_func_array([$this->pdo, $func], $args);
    }
}
