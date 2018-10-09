<?php

namespace daos\mysql;

/**
 * MySQL specific statements
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class Statements {
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
        $res = \F3::get('db')->exec('SELECT LAST_INSERT_ID() as lastid');

        return (int) $res[0]['lastid'];
    }

    /**
     * null first for order by clause
     *
     * @param column to concat
     * @param order
     *
     * @return string full statement
     */
    public static function nullFirst($column, $order) {
        return "$column $order";
    }

    /**
     * sum statement for boolean columns
     *
     * @param bool column to concat
     *
     * @return string full statement
     */
    public static function sumBool($column) {
        return "SUM($column)";
    }

    /**
     * bool true statement
     *
     * @param column to check for truth
     *
     * @return string full statement
     */
    public static function isTrue($column) {
        return "$column=1";
    }

    /**
     * bool false statement
     *
     * @param column to check for false
     *
     * @return string full statement
     */
    public static function isFalse($column) {
        return "$column=0";
    }

    /**
     * check if CSV column matches a value.
     *
     * @param CSV column to check
     * @param value to search in CSV column
     *
     * @return string full statement
     */
    public static function csvRowMatches($column, $value) {
        if ($value[0] == ':') {
            $value = "_utf8mb4 $value";
        }

        return "CONCAT(',', $column, ',') LIKE CONCAT('%,', $value, ',%') COLLATE utf8mb4_general_ci";
    }

    /**
     * check column against int list.
     *
     * @param int $column column to check
     * @param array $ints of string or int values to match column against
     *
     * @return ?string full statement
     */
    public static function intRowMatches($column, array $ints) {
        // checks types
        if (!is_array($ints) && sizeof($ints) < 1) {
            return null;
        }
        $all_ints = [];
        foreach ($ints as $ints_str) {
            $i = (int) $ints_str;
            if ($i > 0) {
                $all_ints[] = $i;
            }
        }

        if (sizeof($all_ints) > 0) {
            $comma_ints = implode(',', $all_ints);

            return $column . " IN ($comma_ints)";
        }

        return null;
    }

    /**
     * Convert boolean into a representation recognized by the database engine.
     *
     * @return string representation of boolean
     */
    public static function bool($bool) {
        return $bool ? 'TRUE' : 'FALSE';
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
        return $datestr; // mysql supports ISO8601 datetime comparisons
    }

    /**
     * Ensure row values have the appropriate PHP type. This assumes we are
     * using buffered queries (sql results are in PHP memory).
     *
     * @param array $rows array of associative array representing row results
     * @param array $expectedRowTypes associative array mapping columns to PDO types
     *
     * @return array of associative array representing row results having
     *         expected types
     */
    public function ensureRowTypes(array $rows, array $expectedRowTypes) {
        foreach ($rows as $rowIndex => $row) {
            foreach ($expectedRowTypes as $columnIndex => $type) {
                if (array_key_exists($columnIndex, $row)) {
                    switch ($type) {
                        case \daos\PARAM_INT:
                            $value = (int) $row[$columnIndex];
                            break;
                        case \daos\PARAM_BOOL:
                            if ($row[$columnIndex] == '1') {
                                $value = true;
                            } else {
                                $value = false;
                            }
                            break;
                        case \daos\PARAM_CSV:
                            if ($row[$columnIndex] === '') {
                                $value = [];
                            } else {
                                $value = explode(',', $row[$columnIndex]);
                            }
                            break;
                        default:
                            $value = null;
                    }
                    if ($value !== null) {
                        $rows[$rowIndex][$columnIndex] = $value;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * convert string array to string for storage in table row
     *
     * @param string[] $a
     *
     * @return string
     */
    public function csvRow(array $a) {
        $filtered = [];
        foreach ($a as $s) {
            $t = trim($s);
            if ($t) {
                $filtered[] = $t;
            }
        }

        return implode(',', $filtered);
    }
}
