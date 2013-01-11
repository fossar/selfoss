<?PHP

namespace daos\mongodb;

/**
 * Class for accessing persistent saved sources -- mongodb
 *
 * @package     daos
 * @copyright   Copyright (c) Harald Lapp (http://octris.org/)
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Harald Lapp <harald.lapp@gmail.com>
 */
class Sources extends Database {
    /**
     * add new source
     *
     * @return  int                         new id
     * @param   string      $title
     * @param   string      $spout          the source type
     * @param   mixed       $params         depends from spout
     */
    public function add($title, $spout, $params) {
        $sources = new \M2('sources');
        $sources->title  = $title;
        $sources->spout  = $spout;
        $sources->params = htmlentities(json_encode($params));
        $sources->error  = '';
        $sources->save();

        return (string)$sources->_id;
    }
    
    /**
     * edit source
     *
     * @return  void
     * @param   int         $id             the source id
     * @param   string      $title          new title
     * @param   string      $spout          new spout
     * @param   mixed       $params         the new params
     */
    public function edit($id, $title, $spout, $params) {
        $sources = new \M2('sources');
        $sources->load(array('_id' => new \MongoId($id)));
        $sources->title  = $title;
        $sources->spout  = $spout;
        $sources->params = htmlentities(json_encode($params));
        $sources->save();
    }
    
    /**
     * delete source
     *
     * @return void
     * @param int $id
     */
    public function delete($id) {
        $db = \F3::get('DB');

        // delete source
        $cn = $db->selectCollection('sources');
        $cn->remove(array('_id' => new \MongoId($id)));
        
        // delete items of this source
        $cn = $db->selectCollection('items');
        $cn->remove(array('source' => new \MongoDBRef('sources', $id)));
    }
    
    /**
     * save error message
     *
     * @return void
     * @param int $id the source id
     * @param string $error error message
     */
    public function error($id, $error="") {
        $sources = new \M2('sources');
        $sources->load(array('_id' => new \MongoId($id)));
        $sources->error = $error;
        $sources->save();
    }
    
    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function get() {
        $sources = new \M2('sources');
        $ret     = $sources->find(array(), array('title' => -1));

        $spoutLoader = new \helpers\SpoutLoader();

        for ($i = 0, $cnt = count($ret); $i < $cnt; ++$i) {
            $ret[$i] = $ret[$i]->cast();
            
            $ret[$i]['id']        = (string)$ret[$i]['_id'];
            $ret[$i]['spout_obj'] = $spoutLoader->get($ret[$i]['spout']);
            unset($ret[$i]['_id']);
        }

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
            $return = preg_match('/^[a-fA-F0-9]{24}$/', $value);
            break;
        }
        
        return $return;
    }
}
