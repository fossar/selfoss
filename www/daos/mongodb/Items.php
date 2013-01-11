<?PHP

namespace daos\mongodb;

/**
 * Class for accessing persistent saved items -- mongodb
 *
 * @package     daos
 * @copyright   Copyright (c) Harald Lapp (http://octris.org/)
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Harald Lapp <harald.lapp@gmail.com>
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
        $items = new \M2('items');
        $items->load(array('_id' => new \MongoId($lastid)));
        $items->unread = false;
        $items->save();
    }
    
    /**
     * starr item
     *
     * @return void
     * @param int $id the item
     */
    public function starr($id) {
        $items = new \M2('items');
        $items->load(array('_id' => new \MongoId($id)));
        $items->starred = true;
        $items->save();
    }
    
    /**
     * unstarr item
     *
     * @return void
     * @param int $id the item
     */
    public function unstarr($id) {
        $items = new \M2('items');
        $items->load(array('_id' => new \MongoId($id)));
        $items->starred = false;
        $items->save();
    }
    
    /**
     * add new item
     *
     * @return void
     * @param mixed $values
     */
    public function add($values) {
        $items = new \M2('items');
        $items->datetime  = new \MongoDate(strtotime($values['datetime']));
        $items->title     = $values['title'];
        $items->content   = $values['content'];
        $items->unread    = true;
        $items->starred   = false;
        $items->source    = \MongoDBRef::create('sources', new \MongoId($values['source']));
        $items->thumbnail = $values['thumbnail'];
        $items->icon      = $values['icon'];
        $items->uid       = $values['uid'];
        $items->link      = $values['link'];
        $items->save();
    }
    
    /**
     * checks whether an item with given
     * uid exists or not
     *
     * @return bool
     * @param string $uid
     */
    public function exists($uid) {
        $items = new \M2('items');
        $cnt   = $items->found(array('uid' => $uid));

        return ($cnt > 0);
    }
    
    /**
     * cleanup old items
     *
     * @return void
     * @param DateTime $date date to delete all items older than this value
     */
    public function cleanup(\DateTime $date) {
        $db = \F3::get('DB');

        $cn = $db->selectCollection('items');
        $cn->remove(array(
            'starred' => false,
            'date'    => array('$lt' => new \MongoDate($date->getTimestamp()))
        ));
    }
    
    /**
     * returns items
     *
     * @return mixed items as array
     * @param mixed $options search, offset and filter params
     */
    public function get($options = array()) {
        $cond = array();
        
        if ($options['starred'] !== false) {
            $cond['starred'] = true;
        }
        if ($options['search'] !== false) {
            $regexp = new \MongoRegEx('/' . preg_quote($options['search'], '/') . '/i');

            $cond['$or'] = array(
                array('title'   => $regexp),
                array('content' => $regexp)
            );
        }
        
        $items = new \M2('items');
        
        // first check whether more items are available
        $cnt = $items->found($cond);
        
        $this->hasMore = ($options['offset'] + $options['items'] < $cnt);
        
        // fetch items
        $result = $items->find($cond, array('datetime' => -1), $options['items'], $options['offset']);
        $return = array();
        
        foreach ($result as $tmp) {
            if (\MongoDBRef::isRef($tmp->source)) {
                $source = \F3::get('DB')->getDBRef($tmp->source);
                $title  = $source['title'];
            } else {
                $title = '';
            }
            
            $return[] = array(
                'id'          => (string)$tmp->_id,
                'datetime'    => strftime('%Y-%m-%d %H:%M:%S', $tmp->datetime->sec),
                'title'       => $tmp->title,
                'content'     => $tmp->content,
                'unread'      => (int)$tmp->unread,
                'starred'     => (int)$tmp->starred,
                'source'      => $tmp->source,
                'thumbnail'   => $tmp->thumbnail,
                'icon'        => $tmp->icon,
                'uid'         => $tmp->uid,
                'link'        => $tmp->link,
                'sourcetitle' => $title
            );
        }
        
        return $return;
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
        $items  = new \M2('items');
        $thumbs = $items->find(array('thumbnail' => array('$ne' => '')));

        $ret = array();
        
        foreach ($thumbs as $thumb) $ret[] = $thumb->thumbnail;
        
        return $ret;
    }
    
    /**
     * return all icons
     *
     * @return string[] array with all icons
     */
    public function getIcons() {
        $items = new \M2('items');
        $icons = $items->find(array('icon' => array('$ne' => '')));

        $ret = array();
        
        foreach ($icons as $icon) $ret[] = $icon->icon;
        
        return $ret;
    }
    
    /**
     * return all thumbnails
     *
     * @return bool true if thumbnail is still in use
     * @param string $thumbnail name
     */
    public function hasThumbnail($thumbnail) {
        $items = new \M2('items');
        
        $amount = $items->found(array('thumbnail' => $thumbnail));
        
        if ($amount == 0) {
            \F3::get('logger')->log('thumbnail not found: '.$thumbnail, \DEBUG);
        }

        return ($amount > 0);
    }
    
    /**
     * return all icons
     *
     * @return bool true if icon is still in use
     * @param string $icon file
     */
    public function hasIcon($icon) {
        $items = new \M2('items');
        
        return ($items->found(array('icon' => $icon)) > 0);
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
