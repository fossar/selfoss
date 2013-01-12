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
     */
    public function saveTagColor($tag,$color) {
	
	}
	
	
	/**
     * save given tag with random color
     *
     * @return void
     */
    public function autocolorTag($tag) {
		// tag color allready defined
		if($this->hasTag($tag))
			return;
		
		
	}
	
	
	/**
     * returns all tags with color
     *
     * @return array of all tags
     */
    public function get() {
	
	}
	
	
	/**
     * remove all unused tag color definitions
     *
     * @return void
     */
    public function cleanup() {
	
	}
	
	
	/**
     * returns whether a color is used or not
     *
     * @return boolean true if color is used by an tag
     */
    private function isColorUsed($color) {
		\DB::sql('SELECT COUNT(*) AS amount FROM tags WHERE color=:color',
                    array(':color' => $uid));
        $res = \F3::get('DB->result');
        return $res[0]['amount']>0;
	}
	
	
	/**
     * check whether tag color is defined.
     *
     * @return boolean true if color is used by an tag
     */
    private function hasTag($tag) {
		\DB::sql('SELECT COUNT(*) AS amount FROM tags WHERE tag=:tag',
                    array(':tag' => $uid));
        $res = \F3::get('DB->result');
        return $res[0]['amount']>0;
	}
	
}
