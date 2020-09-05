<?php

namespace helpers;

/**
 * Helper class for anonymizing urls
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Anonymizer {
    /**
     * @return bool whether or not we should anonymize urls
     */
    private static function shouldAnonymize() {
        return true;
    }

    /**
     * @return string the anonymizer string if we should anonymize otherwise blank
     */
    public static function getAnonymizer() {
        return self::shouldAnonymize() ? trim(\F3::get('anonymizer')) : '';
    }
}
