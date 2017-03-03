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
     * wrap insert statement to return id
     *
     * @param string $query sql statement
     * @param array $params sql params
     *
     * @return int id after insert
     */
    public static function insert($query, array $params) {
        \F3::get('db')->exec($query, $params);
        $res = \F3::get('db')->exec('SELECT last_insert_rowid() as lastid');

        return (int) $res[0]['lastid'];
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
     * @return string representation of boolean
     */
    public static function bool($bool) {
        return $bool ? '1' : '0';
    }

    /**
     * Convert a date string into a representation suitable for comparison by
     * the database engine.
     *
     * @param string $datestr ISO8601 datetime
     *
     * @return string representation of datetime
     */
    public static function datetime($datestr) {
        $date = new \DateTime($datestr);

        return $date->format('Y-m-d H:i:s');
    }
}
