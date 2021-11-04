<?php

namespace daos\pgsql;

use daos\DatabaseInterface;

/**
 * PostgreSQL specific statements
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class Statements extends \daos\mysql\Statements {
    /**
     * null first for order by clause
     *
     * @param string $column column to concat
     * @param string $order
     *
     * @return string full statement
     */
    public static function nullFirst($column, $order) {
        if ($order === 'DESC') {
            $nulls = 'LAST';
        } elseif ($order === 'ASC') {
            $nulls = 'FIRST';
        }

        return "$column $order NULLS $nulls";
    }

    /**
     * sum statement for boolean columns
     *
     * @param string $column column to concat
     *
     * @return string full statement
     */
    public static function sumBool($column) {
        return "SUM($column::int)";
    }

    /**
     * bool true statement
     *
     * @param string $column column to check for truth
     *
     * @return string full statement
     */
    public static function isTrue($column) {
        return "$column=true";
    }

    /**
     * bool false statement
     *
     * @param string $column column to check for false
     *
     * @return string full statement
     */
    public static function isFalse($column) {
        return "$column=false";
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
        return "$value=ANY(string_to_array($column, ','))";
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
    public static function ensureRowTypes(array $rows, array $expectedRowTypes) {
        foreach ($rows as $rowIndex => $row) {
            foreach ($expectedRowTypes as $columnIndex => $type) {
                if (array_key_exists($columnIndex, $row)) {
                    switch ($type) {
                        // pgsql returns correct PHP types for INT and BOOL
                        case DatabaseInterface::PARAM_CSV:
                            if ($row[$columnIndex] === '') {
                                $value = [];
                            } else {
                                $value = explode(',', $row[$columnIndex]);
                            }
                            break;
                        case DatabaseInterface::PARAM_DATETIME:
                            if (empty($row[$columnIndex])) {
                                $value = null;
                            } else {
                                $value = new \DateTime($row[$columnIndex]);
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
     * Match a value to a regular expression.
     *
     * @param string $value value to match
     * @param string $regex regular expression
     *
     * @return string expression for matching
     */
    public static function matchesRegex($value, $regex) {
        // https://www.postgresql.org/docs/12/functions-matching.html#FUNCTIONS-POSIX-REGEXP
        return $value . ' ~ ' . $regex;
    }
}
