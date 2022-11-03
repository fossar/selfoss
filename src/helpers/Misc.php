<?php

namespace helpers;

class Misc {
    public const ORDER_ASC = 1;
    public const ORDER_DESC = -1;

    public const CMP_LT = -1;
    public const CMP_GT = 1;
    public const CMP_EQ = 0;

    /**
     * Compare two values for use in sort functions.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return self::CMP_* mutual ordering
     */
    public static function compare($a, $b) {
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
     * @return callable(mixed, mixed): self::CMP_* comparator
     */
    public static function compareBy(...$transformations) {
        if (count($transformations) > 0) {
            return function($a, $b) use ($transformations) {
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

                    $comparison = $order * $comparison;

                    if ($comparison !== self::CMP_EQ) {
                        break;
                    }
                }

                return $comparison;
            };
        } else {
            return [self::class, 'compare'];
        }
    }
}
