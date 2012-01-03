<?PHP

namespace models\mysql;

/**
 * Class for accessing persistent saved items -- mysql
 *
 * @package    models
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Harald Lapp <harald.lapp@gmail.com>
 */
class Items extends Database {

    /**
     * indicates whether last run has more
     * results or not
     * @var bool
     */
    private $hasMore = false;

    /**
     * mark items as read
     * all items with id bigger than given
     * id will be marked
     *
     * @return void
     * @param int $lastid
     */
    public function mark($lastid) {
        \DB::sql('UPDATE items SET unread=0 WHERE id>=:id',
                    array(':id' => $lastid));
    }
    
    
    /**
     * starr item
     *
     * @return void
     * @param int $id the item
     */
    public function starr($id) {
        \DB::sql('UPDATE items SET starred=1 WHERE id=:id', 
                    array(':id' => $id));
    }
    
    
    /**
     * unstarr item
     *
     * @return void
     * @param int $id the item
     */
    public function unstarr($id) {
        \DB::sql('UPDATE items SET starred=0 WHERE id=:id',
                    array(':id' => $id));
    }
    
    
    /**
     * add new item
     *
     * @return void
     * @param mixed $values
     */
    public function add($values) {
        \DB::sql('INSERT INTO items (
                    datetime, 
                    title, 
                    content, 
                    unread, 
                    starred, 
                    source, 
                    thumbnail, 
                    icon, 
                    uid,
                    link
                  ) VALUES (
                    :datetime, 
                    :title, 
                    :content, 
                    :unread,
                    :starred, 
                    :source, 
                    :thumbnail, 
                    :icon, 
                    :uid,
                    :link
                  )',
                 array(
                    ':datetime'    => $values['datetime'],
                    ':title'       => $values['title'],
                    ':content'     => $values['content'],
                    ':thumbnail'   => $values['thumbnail'],
                    ':icon'        => $values['icon'],
                    ':unread'      => 1,
                    ':starred'     => 0,
                    ':source'      => $values['source'],
                    ':uid'         => $values['uid'],
                    ':link'        => $values['link']
                 ));
    }
    
    
    /**
     * checks whether an item with given
     * uid exists or not
     *
     * @return bool
     * @param string $uid
     */
    public function exists($uid) {
        \DB::sql('SELECT COUNT(*) AS amount FROM items WHERE uid=:uid',
                    array(':uid' => $uid));
        $res = \F3::get('DB->result');
        return $res[0]['amount']>0;
    }
    
    
    /**
     * cleanup old items
     *
     * @return void
     * @param DateTime $date date to delete all items older than this value
     */
    public function cleanup(\DateTime $date) {
        \DB::sql('DELETE FROM items WHERE starred=0 AND datetime<:date',
                    array(':date' => $date->format('Y-m-d').' 00:00:00'));
    }
    
    
    /**
     * returns items
     *
     * @return mixed items as array
     * @param mixed $options search, offset and filter params
     */
    public function get($options = array()) {
        $params = array();
        $where = '';
        
        if($options['starred']!==false)
            $where .= ' AND starred=1 ';
            
        if($options['search']!==false && strlen($options['search'])>0) {
            $params[':search'] = $params[':search2'] = array("%".$options['search']."%", \PDO::PARAM_STR);
            $where .= ' AND (items.title LIKE :search OR items.content LIKE :search2) ';
        }
        
        if(!is_numeric($options['items']) || $options['items']>200)
            $options['items'] = \F3::get('items_perpage');
        
        // first check whether more items are available
        \DB::sql('SELECT items.id
                   FROM items
                   WHERE 1=1 '.$where.' 
                   LIMIT ' . ($options['offset']+$options['items']) . ', 1', $params);
        $result = \F3::get('DB->result');
        $this->hasMore = count($result);

        // fetch items
        \DB::sql('SELECT 
                    items.id, datetime, items.title AS title, content, unread, starred, source, thumbnail, icon, uid, link, sources.title as sourcetitle
                   FROM items, sources 
                   WHERE items.source=sources.id '.$where.' 
                   ORDER BY items.id DESC 
                   LIMIT ' . $options['offset'] . ', ' . $options['items'], $params);
        return \F3::get('DB->result');
    }
    
    
    /**
     * returns whether more items for last given
     * get call are available
     *
     * @return bool
     */
    public function hasMore() {
        return $this->hasMore;
    }
    
    
    /**
     * return all thumbnails
     *
     * @return string[] array with thumbnails
     */
    public function getThumbnails() {
        $thumbnails = array();
        \DB::sql('SELECT thumbnail 
                   FROM items 
                   WHERE thumbnail!=""');
        foreach(\F3::get('DB->result') as $thumb)
            $thumbnails[] = $thumb['thumbnail'];
        return $thumbnails;
    }
    
    
    /**
     * return all icons
     *
     * @return string[] array with all icons
     */
    public function getIcons() {
        $icons = array();
        \DB::sql('SELECT icon 
                   FROM items 
                   WHERE icon!=""');
        foreach(\F3::get('DB->result') as $icon)
            $icons[] = $icon['icon'];
        return $icons;
    }
    
    
    /**
     * return all thumbnails
     *
     * @return bool true if thumbnail is still in use
     * @param string $thumbnail name
     */
    public function hasThumbnail($thumbnail) {
        \DB::sql('SELECT count(*) AS amount
                   FROM items 
                   WHERE thumbnail=:thumbnail',
                  array(':thumbnail' => $thumbnail));
        $res = \F3::get('DB->result');
        $amount = $res[0]['amount'];
        if($amount==0)
            \F3::get('logger')->log('thumbnail not found: '.$thumbnail, \DEBUG);
        return $amount>0;
    }
    
    
    /**
     * return all icons
     *
     * @return bool true if icon is still in use
     * @param string $icon file
     */
    public function hasIcon($icon) {
        \DB::sql('SELECT count(*) AS amount
                   FROM items 
                   WHERE icon=:icon',
                  array(':icon' => $icon));
        $res = \F3::get('DB->result');
        return $res[0]['amount']>0;
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
