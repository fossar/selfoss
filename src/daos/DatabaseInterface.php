<?php

declare(strict_types=1);

namespace Selfoss\daos;

/**
 * Interface describing database backend.
 */
interface DatabaseInterface {
    public const PARAM_INT = 1;
    public const PARAM_BOOL = 2;
    public const PARAM_CSV = 3;
    public const PARAM_DATETIME = 4;
    public const PARAM_NULL = 128;

    /**
     * Execute SQL statement.
     *
     * @param array<string, mixed> $args
     */
    public function execute(string $cmd, array $args = []): \PDOStatement;

    /**
     * Execute SQL statement and fetch the result as an associative array (when applicable).
     *
     * @param array<string, mixed> $args
     *
     * @return array<int, array<string, mixed>>
     */
    public function exec(string $cmd, array $args = []): array;

    /**
     * wrap insert statement to return id
     *
     * @param string $query sql statement
     * @param array<string, mixed> $params sql params
     *
     * @return int id after insert
     */
    public function insert(string $query, array $params): int;

    /**
     * Quote string
     */
    public function quote(mixed $value, int $type = \PDO::PARAM_STR): string;

    /**
     * Begin SQL transaction
     */
    public function beginTransaction(): bool;

    /**
     * Rollback SQL transaction
     */
    public function rollBack(): bool;

    /**
     * Commit SQL transaction
     */
    public function commit(): bool;

    /**
     * Optimize database using its own optimize statement.
     */
    public function optimize(): void;

    /**
     * Get the current version database schema.
     */
    public function getSchemaVersion(): int;
}
