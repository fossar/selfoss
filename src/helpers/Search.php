<?php

declare(strict_types=1);

namespace helpers;

/**
 * Helper class for searching
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tim Gerundt <tim@gerundt.de>
 */
class Search {
    /**
     * return search terms as array
     *
     * @return string[] search terms
     */
    public static function splitTerms(string $search): array {
        if (strlen($search) === 0) {
            return [];
        }

        // Split search terms by space (but save it inside quotes)...
        /** @var string[] */ // For PHPStan: The only case where null appears is array{null} when the $string is empty.
        $parts = str_getcsv(trim($search), ' ', '"', '\\');

        return array_filter(
            $parts,
            fn(string $item): bool => $item !== ''
        );
    }
}
