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
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->begin();
    }

    /**
     * Rollback SQL transaction
     *
     * @return bool
     */
    public function rollBack() {
        return $this->connection->rollback();
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
        $version = @$this->exec('SELECT version FROM ' . $this->configuration->dbPrefix . 'version ORDER BY version DESC LIMIT 1');

        return (int) $version[0]['version'];
    }
}
