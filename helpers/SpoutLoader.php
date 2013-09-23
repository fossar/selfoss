<?PHP

namespace helpers;

/**
 * Helper class for loading spouts (special spouts which
 * defines an spout for this application)
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class SpoutLoader {
    
    /**
     * array of available spouts
     *
     * @var bool|array
     */
    public $spouts = false;
    
    
    /**
     * returns all available spouts
     *
     * @return array available spouts
     */
    public function all() {
        $this->readSpouts();
        return $this->spouts;
    }
    
    
    /**
     * returns a given spout object
     *
     * @return mixed|boolean an instance of the spout, false if this spout doesn't exist
     * @param string $spout a given spout type
     */
    public function get($spout) {
        $this->readSpouts();
        if(!array_key_exists($spout, $this->spouts))
            return false;
        else
            return $this->spouts[$spout];
    }
    
    
    
    //
    // private helpers
    //
    
    
    /**
     * reads all spouts
     *
     * @return void
     */
    protected function readSpouts() {
        if($this->spouts===false) {
            $this->spouts = $this->loadClass('spouts', 'spouts\spout');
            
            // sort spouts by name
            uasort($this->spouts, array('self', 'compareSpoutsByName'));
        }
    }
    
    
    /**
     * returns all classes which extends a given class
     *
     * @return array with classname (key) and an instance of a class (value)
     * @param string $location the path where all spouts in
     * @param string $parentclass the parent class which files must extend
     */
    protected function loadClass($location, $parentclass) {
        $return = array();
        
        foreach(scandir($location) as $dir) {
            if(is_dir($location . '/' . $dir) && substr($dir,0,1)!=".") {
                
                // search for spouts
                foreach(scandir($location . "/" . $dir) as $file) {
                    
                    // only scan visible .php files
                    if(is_file($location . "/" . $dir . "/" . $file) && substr($file,0,1)!="." && strpos($file,".php")!==false) {
                        
                        // create reflection class
                        $classname = $location."\\".$dir."\\".str_replace(".php","",$file);
                        $class = new \ReflectionClass($classname);
                        
                        // register widget
                        if($class->isSubclassOf(new \ReflectionClass($parentclass)))
                            $return[$classname] = $class->newInstance();
                    }
                }
                
            }
        }
        
        return $return;
    }
    
    
    /**
     * compare spouts by name
     * @param \spouts\spout $spout1 Spout 1
     * @param \spouts\spout $spout2 Spout 2
     * @return int
     */
    private static function compareSpoutsByName($spout1, $spout2) {
        return strcasecmp($spout1->name, $spout2->name);
    }
}