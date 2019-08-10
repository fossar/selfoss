<?php

namespace helpers;

class Misc {
    const ORDER_ASC = 1;
    const ORDER_DESC = -1;

    const CMP_LT = -1;
    const CMP_GT = 1;
    const CMP_EQ = 0;

    /**
     * Compare two values for use in sort functions.
     *
     * In PHP 7, spaceship operator can be used instead.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return self::CMP_* mutual ordering
     */
    public static function compare($a, $b) {
        return ($a < $b) ? self::CMP_LT : (($a > $b) ? self::CMP_GT : self::CMP_EQ);
    }

    /**
     * Compare lexicographically using successive transformations.
     *
     * Callable transformations will transform the compared values and then compare them,
     * other types will be used as keys to access array array values to be compared.
     *
     * @param callable|array-key|array{callable|array-key, self::ORDER_*} ...$transformations
     *
     * @return callable comparator
     */
    public static function compareBy(...$transformations) {
        if (count($transformations) > 0) {
            return function($a, $b) use ($transformations) {
                foreach ($transformations as $transformation) {
                    $order = self::ORDER_ASC;
                    if (is_array($transformation)) {
                        list($transformation, $order) = $transformation;
                    }
                    if (is_callable($transformation)) {
                        $comparison = self::compare($transformation($a), $transformation($b));
                    } else {
                        $comparison = self::compare($a[$transformation], $b[$transformation]);
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
