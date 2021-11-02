<?php

declare(strict_types=1);

namespace daos;

trait CommonSqlDatabase {
    /**
     * Execute SQL statement.
     *
     * @param array<string, mixed> $args
     */
    public function execute(string $cmd, array $args = []): \PDOStatement {
        return $this->connection->execute($cmd, $args);
    }

    /**
     * Execute SQL statement and fetch the result as an associative array (when applicable).
     *
     * @param array<string, mixed> $args
     *
     * @return array<int, array<string, mixed>>
     */
    public function exec(string $cmd, array $args = []): array {
        return $this->connection->exec($cmd, $args);
    }

    /**
     * Quote string
     *
     * @param mixed $value
     */
    public function quote($value, int $type = \PDO::PARAM_STR): string {
        return $this->connection->quote($value, $type);
    }

    /**
     * Begin SQL transaction
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    /**
     * Rollback SQL transaction
     */
    public function rollBack(): bool {
        return $this->connection->rollBack();
    }

    /**
     * Commit SQL transaction
     */
    public function commit(): bool {
        return $this->connection->commit();
    }

    public function getSchemaVersion(): int {
        $version = @$this->exec('SELECT version FROM ' . $this->connection->getTableNamePrefix() . 'version ORDER BY version DESC LIMIT 1');

        return (int) $version[0]['version'];
    }

    /**
     * Insert raw table data into given table.
     *
     * @param string $table target database table
     * @param string[] $fields column names
     * @param array<string, mixed> $data rows to insert
     */
    public function insertRaw(string $table, array $fields, array $data): void {
        $fieldsSql = implode(', ', $fields);
        $valuesSql = implode(', ', array_map(function($field) {
            return ":$field";
        }, $fields));
        $values = [];
        foreach ($fields as $field) {
            $values[":$field"] = $data[$field];
        }

        $this->exec("insert into $table($fieldsSql) values($valuesSql)", $values);
    }
}
