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
class Tags extends Database {

    /**
     * save given tag color
     *
     * @return void
     * @param string $tag
     * @param string $color
     */
    public function saveTagColor($tag, $color) {
        if($this->hasTag($tag)===true) {
            \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'tags SET color=:color WHERE tag=:tag',
                    array(':tag'   => $tag,
                          ':color' => $color));
        } else {
            \F3::get('db')->exec('INSERT INTO '.\F3::get('db_prefix').'tags (
                    tag, 
                    color
                  ) VALUES (
                    :tag, 
                    :color
                  )',
                 array(
                    ':tag'   => $tag,
                    ':color' => $color,
                 ));
        }
    }
    
    
    /**
     * save given tag with random color
     *
     * @return void
     * @param string $tag
     */
    public function autocolorTag($tag) {
        if(strlen(trim($tag))==0)
            return;
        
        // tag color allready defined
        if($this->hasTag($tag))
            return;
        
        // get unused random color
        while(true) {
            $color = \helpers\Color::randomColor();
            if($this->isColorUsed($color)===false)
                break;
        }
        
        $this->saveTagColor($tag, $color);
    }
    
    
    /**
     * returns all tags with color
     *
     * @return array of all tags
     */
    public function get() {
        return \F3::get('db')->exec('SELECT 
                    tag, color
                   FROM '.\F3::get('db_prefix').'tags 
                   ORDER BY LOWER(tag);');
    }
    
    
    /**
     * remove all unused tag color definitions
     *
     * @return void
     * @param array $tags available tags
     */
    public function cleanup($tags) {
        $tagsInDb = $this->get();
        foreach($tagsInDb as $tag) {
            if(in_array($tag['tag'], $tags)===false) {
                $this->delete($tag['tag']);
            }
        }
    }
    
    
    /**
     * returns whether a color is used or not
     *
     * @return boolean true if color is used by an tag
     */
    private function isColorUsed($color) {
        $res = \F3::get('db')->exec('SELECT COUNT(*) AS amount FROM '.\F3::get('db_prefix').'tags WHERE color=:color',
                    array(':color' => $color));
        return $res[0]['amount']>0;
    }
    
    
    /**
     * check whether tag color is defined.
     *
     * @return boolean true if color is used by an tag
     */
    public function hasTag($tag) {
        if ( \F3::get( 'db_type' ) == 'mysql' ) {
            $where = 'WHERE tag = _utf8 :tag COLLATE utf8_bin';
        } else {
            $where = 'WHERE tag=:tag';
        }
        $res = \F3::get('db')->exec('SELECT COUNT(*) AS amount FROM '.\F3::get('db_prefix').'tags '.$where,
                    array(':tag' => $tag));
        return $res[0]['amount']>0;
    }
    
    
    /**
     * delete tag
     *
     * @return void
     * @param string $tag
     */
    public function delete($tag) {
        \F3::get('db')->exec('DELETE FROM '.\F3::get('db_prefix').'tags WHERE tag=:tag',
                    array(':tag' => $tag));
    }
}
