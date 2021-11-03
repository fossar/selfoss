<?php

namespace daos;

/**
 * Interface describing database backend.
 */
interface DatabaseInterface {
    /**
     * Execute SQL statement(s)
     *
     * @param string|string[] $cmds
     * @param string|array $args
     *
     * @return array|int|false
     */
    public function exec($cmds, $args = null);

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
    public function begin();

    /**
     * Rollback SQL transaction
     *
     * @return bool
     */
    public function rollback();

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
