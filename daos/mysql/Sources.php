<?PHP

namespace daos\mysql;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @package    daos
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
     * @param string $tags
     * @param string $spout the source type
     * @param mixed $params depends from spout
     */
    public function add($title, $tags, $spout, $params) {
        // sanitize tag list
        $tags = implode(',', preg_split('/\s*,\s*/', trim($tags), -1, PREG_SPLIT_NO_EMPTY));

        \F3::get('db')->exec('INSERT INTO '.\F3::get('db_prefix').'sources (title, tags, spout, params, error) VALUES (:title, :tags, :spout, :params, "")',
                    array(
                        ':title'  => trim($title),
                        ':tags'   => $tags,
                        ':spout'  => $spout,
                        ':params' => htmlentities(json_encode($params))
                    ));
 
        $res = \F3::get('db')->exec('SELECT LAST_INSERT_ID() as lastid');
        return $res[0]['lastid'];
    }
    
    
    /**
     * edit source
     *
     * @return void
     * @param int $id the source id
     * @param string $title new title
     * @param string $tags new tags
     * @param string $spout new spout
     * @param mixed $params the new params
     */
    public function edit($id, $title, $tags, $spout, $params) {
        // sanitize tag list
        $tags = implode(',', preg_split('/\s*,\s*/', trim($tags), -1, PREG_SPLIT_NO_EMPTY));

        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'sources SET title=:title, tags=:tags, spout=:spout, params=:params, error=\'\' WHERE id=:id',
                    array(
                        ':title'  => trim($title),
                        ':tags'  => $tags,
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
        \F3::get('db')->exec('DELETE FROM '.\F3::get('db_prefix').'sources WHERE id=:id',
                    array(':id' => $id));
        
        // delete items of this source
        \F3::get('db')->exec('DELETE FROM '.\F3::get('db_prefix').'items WHERE source=:id',
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
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'sources SET error=:error WHERE id=:id',
                    array(
                        ':id'    => $id,
                        ':error' => $error
                    ));
    }


    /**
     * sets the last updated timestamp
     *
     * @return void
     * @param int $id the source id
     */
    public function saveLastUpdate($id) {
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'sources SET lastupdate=:lastupdate WHERE id=:id',
                    array(
                        ':id'         => $id,
                        ':lastupdate' => time()
                    ));
    }


    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function getByLastUpdate() {
        $ret = \F3::get('db')->exec('SELECT id, title, tags, spout, params, error FROM '.\F3::get('db_prefix').'sources ORDER BY lastupdate ASC');
        $spoutLoader = new \helpers\SpoutLoader();
        for($i=0;$i<count($ret);$i++)
            $ret[$i]['spout_obj'] = $spoutLoader->get( $ret[$i]['spout'] );
        return $ret;
    }
    
    
    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function get() {
        $ret = \F3::get('db')->exec('SELECT id, title, tags, spout, params, error FROM '.\F3::get('db_prefix').'sources ORDER BY error DESC, lower(title) ASC');
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
    
    
    /**
     * returns all tags
     *
     * @return mixed all sources
     */
    public function getAllTags() {
        $result = \F3::get('db')->exec('SELECT tags FROM '.\F3::get('db_prefix').'sources');
        $tags = array();
        foreach($result as $res)
            $tags = array_merge($tags, explode(",",$res['tags']));
        $tags = array_unique($tags);
        return $tags;
    }
    /**
     * returns tags of a source
     *
     * @param integer $id 
     * @return mixed tags of a source
     */
    public function getTags($id) {
      $result = \F3::get('db')->exec('SELECT tags FROM '.\F3::get('db_prefix').'sources WHERE id=:id',
                                     array(
                                           ':id' => $id
                                           ));
        $tags = array();
        $tags = array_merge($tags, explode(",",$result[0]['tags']));
        $tags = array_unique($tags);
        return $tags;
    }

    /**
     * test if a source is already present using title, spout and params.
     * if present returns the id, else returns 0
     *
     * @return integer id if any record is found.
     * @param  string  $title
     * @param  string  $spout the source type
     * @param  mixed   $params depends from spout     
     * @return mixed   all sources
     */
    public function checkIfExists($title, $spout, $params) {
         // Check if a entry exists with same title, spout and params
         $result = \F3::get('db')->exec('SELECT id FROM '.\F3::get('db_prefix').'sources WHERE title=:title AND spout=:spout AND params=:params',
                 array(
                     ':title'  => trim($title),
                     ':spout'  => $spout,
                     ':params' => htmlentities(json_encode($params))
                ));
         if ($result) {
             return $result[0]['id'];
         }
         return 0;
     }   
}
