<?PHP

namespace models;

/**
 * Class for accessing persistent saved sources
 *
 * @package    models\mysql
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 */

class Sources extends Database {
    /**
     * Instance of backend specific sources class
     *
     * @var     object
     */
    private $backend = null;
    
    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $class = 'models\\' . \F3::get('db_type') . '\\Sources';
        
        $this->backend = new $class();
        
        parent::__construct();
    }


    /**
     * add new source
     *
     * @return int new id
     * @param string $title
     * @param string $spout the source type
     * @param mixed $params depends from spout
     */
    public function add($title, $spout, $params) {
        return $this->backend->add($title, $spout, $params);
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
        $this->backend->edit($id, $title, $spout, $params);
    }
    
    
    /**
     * delete source
     *
     * @return void
     * @param int $id
     */
    public function delete($id) {
        $this->backend->delete($id);
    }
    
    
    /**
     * save error message
     *
     * @return void
     * @param int $id the source id
     * @param string $error error message
     */
    public function error($id, $error="") {
        $this->backend->error($id, $error);
    }
    
    
    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function get() {
        return $this->backend->get();
    }

    /**
     * validate new data for a given source
     *
     * @return bool|mixed true on succes or array of 
     * errors on failure
     * @param string $title
     * @param string $spout
     * @param mixed $params
     * 
     * @author Tobias Zeising
     */
    public function validate($title, $spout, $params) {
        $result = array();
    
        // title
        if(strlen(trim($title))==0)
            $result['title'] = 'no text for title given';
    
        // spout type
        $spoutLoader = new \helpers\SpoutLoader();
        $spout = $spoutLoader->get($spout);
        if($spout==false) {
            $result['spout'] = 'invalid spout type';
    
        // check params
        } else {
            // params given but not expectet
            if($spout->params===false) {
                if(is_array($spout->params) && count($spout->params)>0) {
                    $result['spout'] = 'this spout doesn\'t excpect any param';
                }
            }
        
            if($spout->params==false) {
                if(count($result)>0)
                    return $result;
                return true;
            }
        
            // required but not given params
            foreach($spout->params as $id=>$param) {
                if($param['required']===false)
                    continue;
                $found = false;
                foreach($params as $userParamId=>$userParamValue)
                    if($userParamId==$id)
                        $found = true;
                if($found==false)
                    $result[$id] = 'param '.$param['title'].' required but not given';
            }
        
            // given params valid?
            foreach($params as $id=>$value) {
                $validation = $spout->params[$id]['validation'];
                if(!is_array($validation))
                    $validation = array($validation);
            
                foreach($validation as $validate) {
                    if($validate=='alpha' && !preg_match("[A-Za-Z._\b]+",$value))
                        $result[$id] = 'only alphabetic characters allowed for '.$spout->params[$id]['title'];
                    else if($validate=='email' && !preg_match('/^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/',$value))
                        $result[$id] = $spout->params[$id]['title'].' is not a valid email address';
                    else if($validate=='numeric' && !is_numeric($value))
                        $result[$id] = 'only numeric values allowed for '.$spout->params[$id]['title'];
                    else if($validate=='int' && intval($value)!=$value)
                        $result[$id] = 'only integer values allowed for '.$spout->params[$id]['title'];
                    else if($validate=='alnum' && !preg_match("[A-Za-Z0-9._\b]+",$value))
                        $result[$id] = 'only alphanumeric values allowed for '.$spout->params[$id]['title'];
                    else if($validate=='notempty' && strlen(trim($value))==0)
                        $result[$id] = 'empty value for '.$spout->params[$id]['title'].' not allowed';
                }
            }
        }
    
        if(count($result)>0)
            return $result;
        return true;
    }
    
    /**
     * test if the value of a specified field is valid
     *
     * @return  bool
     * @param   string      $name
     * @param   mixed       $value
     */
    public function isValid($name, $value) {
        return $this->backend->isValid($name, $value);
    }
}
