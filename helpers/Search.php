<?php

namespace helpers;

/**
 * Helper class for searching
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tim Gerundt <tim@gerundt.de>
 */
class Search {

    /**
     * return search terms as array
     *
     * @return array search terms
     */
    public static function splitTerms($search) {
        if ( strlen($search) === 0 ) {
            return array();
        }

        //split search terms by space (but save it inside quotes)...
        return str_getcsv(trim($search), ' ');
    }
    
}