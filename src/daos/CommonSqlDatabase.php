<?php

namespace daos;

trait CommonSqlDatabase {
    public function exec($cmds, $args = null) {
        return $this->connection->exec($cmds, $args);
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
        if (!$this->connection->begin()) {
            throw new \Exception('Failed to begin a transaction.');
        }
    }

    /**
     * Rollback SQL transaction
     *
     * @return void
     */
    public function rollBack() {
        if (!$this->connection->rollback()) {
            throw new \Exception('Failed to rollback a transaction.');
        }
    }

    /**
     * Commit SQL transaction
     *
     * @return void
     */
    public function commit() {
        if (!$this->connection->commit()) {
            throw new \Exception('Failed to commit a transaction.');
        }
    }

    public function getSchemaVersion() {
        $version = @$this->exec('SELECT version FROM ' . $this->configuration->dbPrefix . 'version ORDER BY version DESC LIMIT 1');

        return (int) $version[0]['version'];
    }
}
