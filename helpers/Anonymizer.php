<?PHP

namespace helpers;

/**
 * Helper class for anonymizing urls
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Anonymizer {

    /**
     * @return TRUE or FALSE - whether or not we should anonymize urls
     */
    private static function shouldAnonymize() {
        return TRUE;
    }


    /**
     * anonymizes the url unless the anonymize parameter is set to boolean false
     * @return anonymized string
     * @param string $url which is the url to anonymize
     */
    public static function anonymize($url) {
        return self::getAnonymizer() . $url;
    }


    /**
     * @return the anonymizer string if we should anonymize otherwise blank
     */
    public static function getAnonymizer() {
        return self::shouldAnonymize() ? trim(\F3::get('anonymizer')) : '';
    }

}
