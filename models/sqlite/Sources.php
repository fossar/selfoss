<?PHP

namespace models\sqlite;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @package    models
 * @copyright  Copyright (c) Harald Lapp <harald.lapp@gmail.com>
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 */
class Sources extends \models\mysql\Sources {

    /**
     * add new source
     *
     * @return int new id
     * @param string $title
     * @param string $spout the source type
     * @param mixed $params depends from spout
     */
    public function add($title, $spout, $params) {
        \DB::sql('INSERT INTO sources (title, spout, params) VALUES (:title, :spout, :params)',
                    array(
                        ':title'  => $title,
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
        \DB::sql('SELECT id, title, spout, params, error FROM sources ORDER BY lower(title) ASC');
        $ret = \F3::get('DB->result');
        $spoutLoader = new \helpers\SpoutLoader();
        for($i=0;$i<count($ret);$i++)
            $ret[$i]['spout_obj'] = $spoutLoader->get( $ret[$i]['spout'] );
        return $ret;
    }
}
