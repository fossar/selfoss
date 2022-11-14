<?php

namespace daos;

/**
 * Interface for class providing SQL helpers.
 */
interface StatementsInterface {
    /**
     * null first for order by clause
     *
     * @param string $column column to concat
     * @param 'DESC'|'ASC' $order
     *
     * @return string full statement
     */
    public static function nullFirst(string $column, string $order): string;

    /**
     * sum statement for boolean columns
     *
     * @param string $column column to concat
     *
     * @return string full statement
     */
    public static function sumBool(string $column): string;

    /**
     * bool true statement
     *
     * @param string $column column to check for truth
     *
     * @return string full statement
     */
    public static function isTrue(string $column): string;

    /**
     * bool false statement
     *
     * @param string $column column to check for false
     *
     * @return string full statement
     */
    public static function isFalse(string $column): string;

    /**
     * Combine expressions using OR operator.
     *
     * @param string ...$exprs expressions to combine
     *
     * @return string combined expression
     */
    public static function exprOr(string ...$exprs): string;

    /**
     * check if CSV column matches a value.
     *
     * @param string $column CSV column to check
     * @param string $value value to search in CSV column
     *
     * @return string full statement
     */
    public static function csvRowMatches(string $column, string $value): string;

    /**
     * check column against int list.
     *
     * @param string $column column to check
     * @param array $ints of string or int values to match column against
     *
     * @return ?string full statement
     */
    public static function intRowMatches(string $column, array $ints);

    /**
     * Return the statement required to update a datetime column to the current
     * datetime.
     *
     * @return string full statement
     */
    public static function rowTouch(string $column): string;

    /**
     * Convert boolean into a representation recognized by the database engine.
     *
     * @return string representation of boolean
     */
    public static function bool(bool $bool): string;

    /**
     * Convert a date into a representation suitable for comparison by
     * the database engine.
     *
     * @param \DateTime $date datetime
     *
     * @return string representation of datetime
     */
    public static function datetime(\DateTime $date): string;

    /**
     * Ensure row values have the appropriate PHP type. This assumes we are
     * using buffered queries (sql results are in PHP memory);.
     *
     * @param array $rows array of associative array representing row results
     * @param array $expectedRowTypes associative array mapping columns to PDO types
     *
     * @return array of associative array representing row results having
     *         expected types
     */
    public static function ensureRowTypes(array $rows, array $expectedRowTypes): array;

    /**
     * convert string array to string for storage in table row
     *
     * @param string[] $a
     */
    public static function csvRow(array $a): string;

    /**
     * Match a value to a regular expression.
     *
     * @param string $value value to match
     * @param string $regex regular expression
     *
     * @return string expression for matching
     */
    public static function matchesRegex(string $value, string $regex): string;
}
