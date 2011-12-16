<?PHP

namespace models\mysql;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @package    models
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Sources extends Database {

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
 
        \DB::sql('SELECT LAST_INSERT_ID() as lastid');
        $res = \F3::get('DB->result');
        return $res[0]['lastid'];
    }
    
    
    /**
     * edit source
     *
     * @return void
     * @param int $id the source id
     * @param string $title new title
     * @param string $spout new spout
     * @param mixed $params the new params
     */
    public function edit($id, $title, $spout, $params) {
        \DB::sql('UPDATE sources SET title=:title, spout=:spout, params=:params WHERE id=:id',
                    array(
                        ':title'  => $title,
                        ':spout'  => $spout,
                        ':params' => htmlentities(json_encode($params)),
                        ':id'     => $id
                    ));
    }
    
    
    /**
     * delete source
     *
     * @return void
     * @param int $id
     */
    public function delete($id) {
        \DB::sql('DELETE FROM sources WHERE id=:id',
                    array(':id' => $id));
        
        // delete items of this source
        \DB::sql('DELETE FROM items WHERE source=:id',
                    array(':id' => $id));
    }
    
    
    /**
     * save error message
     *
     * @return void
     * @param int $id the source id
     * @param string $error error message
     */
    public function error($id, $error="") {
        \DB::sql('UPDATE sources SET error=:error WHERE id=:id',
                    array(
                        ':id'    => $id,
                        ':error' => $error
                    ));
    }
    
    
    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function get() {
        \DB::sql('SELECT id, title, spout, params, error FROM sources ORDER BY title ASC');
        $ret = \F3::get('DB->result');
        $spoutLoader = new \helpers\SpoutLoader();
        for($i=0;$i<count($ret);$i++)
            $ret[$i]['spout_obj'] = $spoutLoader->get( $ret[$i]['spout'] );
        return $ret;
    }
    
    /**
     * test if the value of a specified field is valid
     *
     * @return  bool
     * @param   string      $name
     * @param   mixed       $value
     */
    public function isValid($name, $value) {
        $return = false;
        
        switch ($name) {
        case 'id':
            $return = is_numeric($value);
            break;
        }
        
        return $return;
    }
}
