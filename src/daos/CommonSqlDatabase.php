<?php

namespace daos;

trait CommonSqlDatabase {
    /**
     * Execute SQL statement.
     *
     * @param string $cmd
     * @param array|scalar $args
     *
     * @return \PDOStatement
     */
    public function execute($cmd, $args = []) {
        return $this->connection->execute($cmd, $args);
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
        return $this->connection->exec($cmd, $args);
    }

    /**
     * Quote string
     *
     * @param mixed $value
     * @param int $type
     *
     * @return string
     **/
    public function quote($value, $type = \PDO::PARAM_STR) {
        return $this->connection->quote($value, $type);
    }

    /**
     * Begin SQL transaction
     *
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Rollback SQL transaction
     *
     * @return bool
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }

    /**
     * Commit SQL transaction
     *
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }

    public function getSchemaVersion() {
        $version = @$this->exec('SELECT version FROM ' . $this->connection->getTableNamePrefix() . 'version ORDER BY version DESC LIMIT 1');

        return (int) $version[0]['version'];
    }
}
