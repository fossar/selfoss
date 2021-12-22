<?php

namespace daos;

/**
 * Interface describing database backend.
 */
interface DatabaseInterface {
    const PARAM_INT = 1;
    const PARAM_BOOL = 2;
    const PARAM_CSV = 3;
    const PARAM_DATETIME = 4;

    /**
     * Execute SQL statement(s)
     *
     * @param string $cmd
     * @param array|scalar $args
     *
     * @return array|null
     */
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
