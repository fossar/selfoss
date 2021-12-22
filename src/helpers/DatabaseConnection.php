<?php

namespace helpers;

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

    /** @var bool whether a transaction is currently in progress */
    private $isInTransaction = false;

    /**
     * Instantiate class
     *
     * @param string $dsn
     * @param string $user
     * @param string $pw
     **/
    public function __construct($dsn, $user = null, $pw = null, array $options = []) {
        $this->pdo = new PDO($dsn, $user, $pw, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
     * @param $val scalar
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
     * Execute SQL statement
     *
     * @param $cmd string
     * @param $args array|scalar
     *
     * @return ?array
     **/
    public function exec($cmd, $args = []) {
        $tag = '';
        $result = null;

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
                    throw new InvalidArgumentException('Expected [$value, $type] for binding.');
                }

                list($value, $type) = $val;
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

        if ($query->columnCount() !== 0) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
        }

        $query->closeCursor();
        unset($query);

        return $result;
    }

    /**
     * Quote string
     *
     * @param $val mixed
     * @param $type int
     *
     * @return string
     **/
    public function quote($val, $type = PDO::PARAM_STR) {
        return $this->pdo->quote($val, $type);
    }

    /**
     * Redirect call to PDO object
     *
     * @param $func string
     * @param $args array
     *
     * @return mixed
     **/
    public function __call($func, array $args) {
        return call_user_func_array([$this->pdo, $func], $args);
    }
}
