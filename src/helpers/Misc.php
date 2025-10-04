<?php

declare(strict_types=1);

namespace Selfoss\helpers;

use InvalidArgumentException;

final class Misc {
    public const ORDER_ASC = 1;
    public const ORDER_DESC = -1;

    public const CMP_LT = -1;
    public const CMP_GT = 1;
    public const CMP_EQ = 0;

    /**
     * Compare two values for use in sort functions.
     *
     * @return self::CMP_* mutual ordering
     */
    public static function compare(mixed $a, mixed $b): int {
        return $a <=> $b;
    }

    /**
     * Compare lexicographically using successive transformations.
     *
     * Callable transformations will transform the compared values and then compare them,
     * other types will be used as keys to access array array values to be compared.
     *
     * @param callable|array-key|array{callable|array-key, self::ORDER_*} ...$transformations
     *
     * @return callable(mixed, mixed): (self::CMP_*) comparator
     */
    public static function compareBy(...$transformations): callable {
        if (count($transformations) > 0) {
            return function(array $a, array $b) use ($transformations) {
                foreach ($transformations as $transformation) {
                    $order = self::ORDER_ASC;
                    if (is_array($transformation)) {
                        [$transformation, $order] = $transformation;
                    }
                    if (is_callable($transformation)) {
                        $comparison = $transformation($a) <=> $transformation($b);
                    } else {
                        $comparison = $a[$transformation] <=> $b[$transformation];
                    }

                    /** @var self::CMP_* */ // For PHPStan.
                    $comparison = $order * $comparison;

                    if ($comparison !== self::CMP_EQ) {
                        break;
                    }
                }

                return $comparison;
            };
        } else {
            return self::compare(...);
        }
    }

    /**
     * Ensure the passed value is numeric and converts it to integer.
     *
     * @throws InvalidArgumentException when argument is not a numberic value
     */
    public static function forceId(mixed $value): int {
        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException("{$value} is not a well-formed id.");
    }

    /**
     * Ensures the passed value is numeric or list thereof and converts it to integer list.
     *
     * @param array<numeric>|numeric $value
     *
     * @throws InvalidArgumentException when argument is not a numeric value or a list thereof
     *
     * @return int[]
     */
    public static function forceIds($value): array {
        if (is_array($value)) {
            return array_map(self::forceId(...), $value);
        }

        return [
            self::forceId($value)
        ];
    }
}
