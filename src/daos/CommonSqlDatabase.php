<?php

namespace daos;

use Nette\Database\ResultSet;

trait CommonSqlDatabase {
    /**
     * Generates and executes SQL query.
     * @param  string
     * @return ResultSet
     */
    public function query($sql, ...$params) {
        return $this->connection->query($sql, ...$params);
    }

    public function quote($value, $type = \PDO::PARAM_STR) {
        return $this->connection->quote($value, $type);
    }

    /**
     * Begin SQL transaction
     *
     * @return void
     */
    public function beginTransaction() {
        $this->connection->beginTransaction();
    }

    /**
     * Rollback SQL transaction
     *
     * @return void
     */
    public function rollBack() {
        $this->connection->rollBack();
    }

    /**
     * Commit SQL transaction
     *
     * @return void
     */
    public function commit() {
        $this->connection->commit();
    }

    public function getSchemaVersion() {
        $version = $this->query('SELECT version FROM ' . $this->configuration->dbPrefix . 'version ORDER BY version DESC LIMIT 1');

        return $version->fetch()['version'];
    }
}
