<?php

namespace daos\sqlite;

/**
 * Sqlite specific statements
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class Statements extends \daos\mysql\Statements {
    /**
     * Return the statement required to update a datetime column to the current
     * datetime.
     *
     * @param string $column
     *
     * @return string full statement
     */
    public static function rowTouch($column) {
        return $column . '=datetime(\'now\')';
    }

    /**
     * check if CSV column matches a value.
     *
     * @param string $column CSV column to check
     * @param string $value value to search in CSV column
     *
     * @return string full statement
     */
    public static function csvRowMatches($column, $value) {
        return "(',' || $column || ',') LIKE ('%,' || $value || ',%')";
    }

    /**
     * Convert boolean into a representation recognized by the database engine.
     *
     * @param bool $bool
     *
     * @return string representation of boolean
     */
    public static function bool($bool) {
        return $bool ? '1' : '0';
    }

    /**
     * Convert a date into a representation suitable for comparison by
     * the database engine.
     *
     * @param \DateTime $date datetime
     *
     * @return string representation of datetime
     */
    public static function datetime(\DateTime $date) {
        // SQLite does not support timezones.
        // The client previously sent the local timezone
        // but now it sends UTC time so we need to adjust it here
        // to avoid fromDatetime mismatch.
        // TODO: Switch to UTC everywhere.
        $date->setTimeZone((new \DateTime())->getTimeZone());

        return $date->format('Y-m-d H:i:s');
    }
}
