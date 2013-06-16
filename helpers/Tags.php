<?PHP
namespace helpers;

/**
 * Helper class for tags handling
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags {

    /**
     * return tag => color array
     *
     * @return tag color array
     * @param array $tags
     */
    public static function convertToAssocArray($tags) {
        $assocTags = array();
        foreach($tags as $tag)
            $assocTags[$tag['tag']] = $tag['color'];
        return $assocTags;
    }

}