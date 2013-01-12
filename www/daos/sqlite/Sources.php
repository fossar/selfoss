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
    public function add($title, $tags, $spout, $params) {
        \DB::sql('INSERT INTO sources (title, tags, spout, params) VALUES (:title, :tags, :spout, :params)',
                    array(
                        ':title'  => $title,
                        ':tags'  => $tags,
                        ':spout'  => $spout,
                        ':params' => htmlentities(json_encode($params))
                    ));
        
        \DB::sql('SELECT last_insert_rowid() as lastid');
        $res = \F3::get('DB->result');
        return $res[0]['lastid'];
    }

    
    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function get() {
        \DB::sql('SELECT id, title, tags, spout, params, error FROM sources ORDER BY lower(title) ASC');
        $ret = \F3::get('DB->result');
        $spoutLoader = new \helpers\SpoutLoader();
        for($i=0;$i<count($ret);$i++)
            $ret[$i]['spout_obj'] = $spoutLoader->get( $ret[$i]['spout'] );
        return $ret;
    }
}
