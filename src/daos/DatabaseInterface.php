<?php

namespace daos;

/**
 * Interface describing database backend.
 */
interface DatabaseInterface {
    public const PARAM_INT = 1;
    public const PARAM_BOOL = 2;
    public const PARAM_CSV = 3;
    public const PARAM_DATETIME = 4;

    /**
     * Execute SQL statement.
     *
     * @param string $cmd
     * @param array|scalar $args
     *
     * @return \PDOStatement
     */
    public function execute($cmd, $args = []);

    /**
     * Execute SQL statement and fetch the result as an associative array (when applicable).
     *
     * @param string $cmd
     * @param array|scalar $args
     *
     * @return ?array
     **/
    public function exec($cmd, $args = []);

    /**
     * wrap insert statement to return id
     *
     * @param string $query sql statement
     * @param array $params sql params
     *
     * @return int id after insert
     */
    public function insert($query, array $params);

    /**
     * Quote string
     *
     * @param mixed $value
     * @param int $type
     *
     * @return string
     */
    public function quote($value, $type = \PDO::PARAM_STR);

    /**
     * Begin SQL transaction
     *
     * @return bool
     */
    public function beginTransaction();

    /**
     * Rollback SQL transaction
     *
     * @return bool
     */
    public function rollBack();

    /**
     * Commit SQL transaction
     *
     * @return bool
     */
    public function commit();

    /**
     * Optimize database using its own optimize statement.
     *
     * @return void
     */
    public function optimize();

    /**
     * Get the current version database schema.
     *
     * @return int
     */
    public function getSchemaVersion();
}
