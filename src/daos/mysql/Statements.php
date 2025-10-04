<?php

declare(strict_types=1);

namespace Selfoss\daos\mysql;

use Selfoss\daos\DatabaseInterface;

/**
 * MySQL specific statements
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class Statements implements \Selfoss\daos\StatementsInterface {
    /**
     * null first for order by clause
     *
     * @param string $column column to concat
     * @param 'DESC'|'ASC' $order
     *
     * @return string full statement
     */
    public static function nullFirst(string $column, string $order): string {
        return "$column $order";
    }

    /**
     * sum statement for boolean columns
     *
     * @param string $column column to concat
     *
     * @return string full statement
     */
    public static function sumBool(string $column): string {
        return "SUM($column)";
    }

    /**
     * bool true statement
     *
     * @param string $column column to check for truth
     *
     * @return string full statement
     */
    public static function isTrue(string $column): string {
        return "$column=1";
    }

    /**
     * bool false statement
     *
     * @param string $column column to check for false
     *
     * @return string full statement
     */
    public static function isFalse(string $column): string {
        return "$column=0";
    }

    /**
     * Combine expressions using OR operator.
     *
     * @param string ...$exprs expressions to combine
     *
     * @return string combined expression
     */
    public static function exprOr(string ...$exprs): string {
        return '(' . implode(' OR ', $exprs) . ')';
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
        if ($value[0] === ':') {
            $value = "_utf8mb4 $value";
        }

        return "CONCAT(',', $column, ',') LIKE CONCAT('%,', $value, ',%') COLLATE utf8mb4_bin ESCAPE ''";
    }

    /**
     * check column against int list.
     *
     * @param string $column column to check
     * @param int[] $ints list of ints to match column against
     *
     * @return ?string full statement
     */
    public static function intRowMatches(string $column, array $ints): ?string {
        // checks types
        if (count($ints) === 0) {
            return null;
        }

        $comma_ints = implode(',', $ints);

        return $column . " IN ($comma_ints)";
    }

    /**
     * Return the statement required to update a datetime column to the current
     * datetime.
     *
     * @return string full statement
     */
    public static function rowTouch(string $column): string {
        return $column . '=NOW()';
    }

    /**
     * Convert boolean into a representation recognized by the database engine.
     *
     * @return string representation of boolean
     */
    public static function bool(bool $bool): string {
        return $bool ? 'TRUE' : 'FALSE';
    }

    /**
     * Convert a date into a representation suitable for comparison by
     * the database engine.
     *
     * @param \DateTime $date datetime
     *
     * @return string representation of datetime
     */
    public static function datetime(\DateTime $date): string {
        // mysql supports ISO8601 datetime comparisons
        return $date->format(\DateTime::ATOM);
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
                        case DatabaseInterface::PARAM_INT:
                            // PDO returns a string in PHP < 8.1.
                            $value = (int) $row[$columnIndex];
                            break;
                        case DatabaseInterface::PARAM_BOOL:
                            // PDO returns '0'|'1' in PHP < 8.1.
                            if ($row[$columnIndex] === 1 || $row[$columnIndex] === '1') {
                                $value = true;
                            } else {
                                $value = false;
                            }
                            break;
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
     * convert string array to string for storage in table row
     *
     * @param string[] $a
     */
    public static function csvRow(array $a): string {
        $filtered = [];
        foreach ($a as $s) {
            $t = trim($s);
            if ($t) {
                $filtered[] = $t;
            }
        }

        return implode(',', $filtered);
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
        // https://dev.mysql.com/doc/refman/5.7/en/regexp.html
        return $value . ' REGEXP ' . $regex;
    }
}
