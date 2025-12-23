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
    /** Whether a transaction is currently in progress */
    private bool $isInTransaction = false;

    private PDO $pdo;

    /**
     * Instantiate class
     *
     * @param ?array<string, mixed> $options
     */
    public function __construct(
        private Logger $logger,
        string $dsn,
        ?string $user = null,
        ?string $password = null,
        ?array $options = null,
        private string $tableNamePrefix = ''
    ) {
        $this->logger->debug('Creating database connection', ['dsn' => $dsn]);
        $this->pdo = new PDO($dsn, $user, $password, $options);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
     * Map data type of argument to a PDO constant
     *
     * @param scalar|null $val
     */
    private function type($val): int {
        return match (gettype($val)) {
            'NULL' => PDO::PARAM_NULL,
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            default => PDO::PARAM_STR,
        };
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
     *
     * @return array<int, array<string, mixed>>
     */
    public function exec(string $cmd, array $args = []): array {
        $statement = $this->execute($cmd, $args);

        $result = [];
        if ($statement->columnCount() !== 0) {
            // Can return false on failure before PHP 8.0.0.
            $result = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $statement->closeCursor();
        unset($statement);

        return $result;
    }

    /**
     * Quote string
     */
    public function quote(mixed $val, int $type = PDO::PARAM_STR): string {
        return $this->pdo->quote((string) $val, $type);
    }

    public function sqliteCreateFunction(
        string $function_name,
        callable $callback,
        int $num_args = -1,
        int $flags = 0
    ): bool {
        return $this->pdo->sqliteCreateFunction(
            $function_name,
            $callback,
            $num_args,
            $flags
        );
    }
}
