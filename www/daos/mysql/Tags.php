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
			\DB::sql('UPDATE tags SET color=:color WHERE tag=:tag',
                    array(':tag'   => $tag,
						  ':color' => $color));
		} else {
			\DB::sql('INSERT INTO tags (
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
		// tag color allready defined
		if($this->hasTag($tag))
			return;
		
		// get unused random color
		while(true) {
			$color = $this->randomColor();
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
		\DB::sql('SELECT 
                    tag, color
                   FROM tags 
                   ORDER BY LOWER(tag);');
        return \F3::get('DB->result');
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
		\DB::sql('SELECT COUNT(*) AS amount FROM tags WHERE color=:color',
                    array(':color' => $color));
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
                    array(':tag' => $tag));
        $res = \F3::get('DB->result');
        return $res[0]['amount']>0;
	}
	
	
	/**
     * delete tag
     *
     * @return void
     * @param string $tag
     */
    public function delete($tag) {
        \DB::sql('DELETE FROM tags WHERE tag=:tag',
                    array(':tag' => $tag));
    }
	
	
	/**
     * generate random color
     *
     * @return string random color in format #123456
     */
	private function randomColor() {
		return "#" . $this->randomColorPart() . $this->randomColorPart() . $this->randomColorPart();
	}
	
	/**
     * generate random number between 0-255 in hex
     *
     * @return string random color part
     */
	private function randomColorPart() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
	}
}
