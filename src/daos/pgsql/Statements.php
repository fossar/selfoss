<?php

declare(strict_types=1);

namespace daos\pgsql;

use daos\DatabaseInterface;

/**
 * PostgreSQL specific statements
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
final class Statements extends \daos\mysql\Statements {
    /**
     * null first for order by clause
     *
     * @param string $column column to concat
     * @param 'DESC'|'ASC' $order
     *
     * @return string full statement
     */
    public static function nullFirst(string $column, string $order): string {
        $nulls = $order === 'DESC' ? 'LAST' : 'FIRST';

        return "$column $order NULLS $nulls";
    }

    /**
     * sum statement for boolean columns
     *
     * @param string $column column to concat
     *
     * @return string full statement
     */
    public static function sumBool(string $column): string {
        return "SUM($column::int)";
    }

    /**
     * bool true statement
     *
     * @param string $column column to check for truth
     *
     * @return string full statement
     */
    public static function isTrue(string $column): string {
        return "$column=true";
    }

    /**
     * bool false statement
     *
     * @param string $column column to check for false
     *
     * @return string full statement
     */
    public static function isFalse(string $column): string {
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
    public static function csvRowMatches(string $column, string $value): string {
        return "$value=ANY(string_to_array($column, ','))";
    }

    /**
     * Ensure row values have the appropriate PHP type. This assumes we are
     * using buffered queries (sql results are in PHP memory).
     *
     * @param array<array<mixed>> $rows array of associative array representing row results
     * @param array<string, DatabaseInterface::PARAM_*> $expectedRowTypes associative array mapping columns to PDO types
     *
     * @return array<array<mixed>> of associative array representing row results having
     *         expected types
     */
    public static function ensureRowTypes(array $rows, array $expectedRowTypes): array {
        foreach ($rows as $rowIndex => $row) {
            foreach ($expectedRowTypes as $columnIndex => $type) {
                if (array_key_exists($columnIndex, $row)) {
                    if ($type & DatabaseInterface::PARAM_NULL) {
                        $type ^= DatabaseInterface::PARAM_NULL;
                        if ($row[$columnIndex] === null) {
                            // Keep as is.
                            continue;
                        }
                    }
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
                            $value = new \DateTime($row[$columnIndex]);
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
    public static function matchesRegex(string $value, string $regex): string {
        // https://www.postgresql.org/docs/12/functions-matching.html#FUNCTIONS-POSIX-REGEXP
        return $value . ' ~ ' . $regex;
    }
}
