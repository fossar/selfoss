<?PHP

namespace daos\pgsql;

/**
 * Class for accessing persistant saved sources -- postgresql
 *
 * @package     daos
 * @copyright   Copyright (c) Michael Jackson <michael.o.jackson@gmail.com>
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Michael Jackson <michael.o.jackson@gmail.com>
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
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

        $res = \F3::get('db')->exec('INSERT INTO sources (title, tags, filter, spout, params) VALUES (:title, :tags, :filter, :spout, :params) RETURNING id',
                    array(
                        ':title'  => trim($title),
                        ':tags'  => $tags,
                        ':filter' => $filter,
                        ':spout'  => $spout,
                        ':params' => htmlentities(json_encode($params))
                    ));
        
        return $res[0]['id'];
    }
}
