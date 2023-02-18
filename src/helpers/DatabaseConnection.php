<?php

declare(strict_types=1);

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
     */
    public function __construct(
        Logger $logger,
        string $dsn,
        ?string $user = null,
        ?string $pw = null,
        array $options = [],
        string $tableNamePrefix = ''
    ) {
        $this->logger = $logger;
        $this->logger->debug('Creating database connection', ['dsn' => $dsn]);
        $this->pdo = new PDO($dsn, $user, $pw, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->tableNamePrefix = $tableNamePrefix;
    }

    /**
     * Begin SQL transaction
     */
    public function beginTransaction(): bool {
        $out = $this->pdo->beginTransaction();
        $this->isInTransaction = true;

        return $out;
    }

    /**
     * Roll back SQL transaction
     */
    public function rollBack(): bool {
        $out = false;
        if ($this->pdo->inTransaction()) {
            $out = $this->pdo->rollBack();
        }
        $this->isInTransaction = false;

        return $out;
    }

    /**
     * Commit SQL transaction
     */
    public function commit(): bool {
        $out = false;
        if ($this->pdo->inTransaction()) {
            $out = $this->pdo->commit();
        }
        $this->isInTransaction = false;

        return $out;
    }

    public function getTableNamePrefix(): string {
        return $this->tableNamePrefix;
    }

    /**
     * Return transaction flag
     */
    private function isInTransaction(): bool {
        return $this->isInTransaction;
    }

    /**
     * Map data type of argument to a PDO constant
     *
     * @param scalar $val
     */
    private function type($val): int {
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
     * @param array<string, mixed> $args
     */
    public function execute(string $cmd, array $args = []): \PDOStatement {
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
     * @param array<string, mixed> $args
     */
    public function exec(string $cmd, array $args = []): ?array {
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
     */
    public function quote($val, int $type = PDO::PARAM_STR): string {
        return $this->pdo->quote((string) $val, $type);
    }

    /**
     * Redirect call to PDO object
     *
     * @return mixed
     */
    public function __call(string $func, array $args) {
        return call_user_func_array([$this->pdo, $func], $args);
    }
}
