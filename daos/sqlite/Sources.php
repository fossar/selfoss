<?PHP

namespace daos\sqlite;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @package    daos
 * @copyright  Copyright (c) Harald Lapp <harald.lapp@gmail.com>
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Sources extends \daos\mysql\Sources {

    /**
     * add new source
     *
     * @return int new id
     * @param string $title
     * @param string $tags
     * @param string $spout the source type
     * @param mixed $params depends from spout
     */
    public function add($title, $tags, $filter, $spout, $params) {
        // sanitize tag list
        $tags = implode(',', preg_split('/\s*,\s*/', trim($tags), -1, PREG_SPLIT_NO_EMPTY));

        \F3::get('db')->exec('INSERT INTO sources (title, tags, filter, spout, params) VALUES (:title, :tags, :filter, :spout, :params)',
                    array(
                        ':title'  => trim($title),
                        ':tags'  => $tags,
                        ':filter' => $filter,
                        ':spout'  => $spout,
                        ':params' => htmlentities(json_encode($params))
                    ));
        
        $res = \F3::get('db')->exec('SELECT last_insert_rowid() as lastid');
        return $res[0]['lastid'];
    }
}
