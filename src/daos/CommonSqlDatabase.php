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
    public function begin() {
        return $this->connection->begin();
    }

    /**
     * Rollback SQL transaction
     *
     * @return bool
     */
    public function rollback() {
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
}
