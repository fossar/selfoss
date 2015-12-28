<?PHP

namespace daos\mysql;

/**
 * Class for accessing persistent saved items -- mysql
 *
 * @package    daos
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
    protected $hasMore = false;

    
    /**
     * mark item as read
     *
     * @return void
     * @param int $id
     */
    public function mark($id) {
        if($this->isValid('id', $id)===false)
            return;
        
        if(is_array($id))
            $id = implode(",", $id);
        
        // i used string concatenation after validating $id
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET '.$this->stmt->isFalse('unread').' WHERE id IN (' . $id . ')');
    }
    
    
    /**
     * mark item as unread
     *
     * @return void
     * @param int $id
     */
    public function unmark($id) {
        if(is_array($id)) {
            $id = implode(",", $id);
        } else if(!is_numeric($id)) {
            return;
        }
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET '.$this->stmt->isTrue('unread').' WHERE id IN (:id)',
                    array(':id' => $id));
    }
    
    
    /**
     * starr item
     *
     * @return void
     * @param int $id the item
     */
    public function starr($id) {
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET '.$this->stmt->isTrue('starred').' WHERE id=:id',
                    array(':id' => $id));
    }
    
    
    /**
     * unstarr item
     *
     * @return void
     * @param int $id the item
     */
    public function unstarr($id) {
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET '.$this->stmt->isFalse('starred').' WHERE id=:id',
                    array(':id' => $id));
    }


    /**
     * mark item as shared (used for source scoring)
     *
     * @return void
     * @param int $id the item
     */
    public function shared($id) {
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET '.$this->stmt->isTrue('shared').' WHERE id=:id',
                    array(':id' => $id));
    }


    /**
     * unshare item (used for source scoring)
     *
     * @return void
     * @param int $id the item
     */
    public function unshared($id) {
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET '.$this->stmt->isFalse('shared').' WHERE id=:id',
                    array(':id' => $id));
    }


    /**
     * mark item as opened (used for source scoring)
     *
     * @return void
     * @param int $id the item
     */
    public function opened($id) {
        \F3::get('db')->exec('UPDATE '.\F3::get('db_prefix').'items SET opened=1 WHERE id=:id',
                    array(':id' => $id));
    }


    /**
     * add new item
     *
     * @return void
     * @param mixed $values
     */
    public function add($values) {
        \F3::get('db')->exec('INSERT INTO '.\F3::get('db_prefix').'items (
                    datetime, 
                    title, 
                    content, 
                    unread, 
                    starred, 
                    source, 
                    thumbnail, 
                    icon, 
                    uid,
                    link,
                    author
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
                    :link,
                    :author
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
                    ':link'        => $values['link'],
                    ':author'      => $values['author']
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
        $res = \F3::get('db')->exec('SELECT COUNT(*) AS amount FROM '.\F3::get('db_prefix').'items WHERE uid=:uid',
                    array( ':uid' => array($uid, \PDO::PARAM_STR) ) );
        return $res[0]['amount']>0;
    }
    
    
    /**
     * search whether given ids are already in database or not
     * 
     * @return array with all existing ids from itemsInFeed (array (id => true))
     * @param array $itemsInFeed list with ids for checking whether they are already in database or not
     */
    public function findAll($itemsInFeed) {
        $itemsFound = array();
        if( count($itemsInFeed) < 1 )
            return $itemsFound;

        array_walk($itemsInFeed, function( &$value ) { $value = \F3::get('db')->quote($value); });
        $query = 'SELECT uid AS uid FROM '.\F3::get('db_prefix').'items WHERE uid IN ('. implode(',', $itemsInFeed) .')';
        $res = \F3::get('db')->query($query);
        if ($res) {
            $all = $res->fetchAll();
            foreach ($all as $row) {
                $uid = $row['uid'];
                $itemsFound[$uid] = true;
            }
        }
        return $itemsFound;
    }

    
    /**
     * cleanup orphaned and old items
     *
     * @return void
     * @param DateTime $date date to delete all items older than this value [optional]
     */
    public function cleanup(\DateTime $date = NULL) {
        \F3::get('db')->exec('DELETE FROM '.\F3::get('db_prefix').'items
            WHERE source NOT IN (
                SELECT id FROM '.\F3::get('db_prefix').'sources)');
        if ($date !== NULL)
            \F3::get('db')->exec('DELETE FROM '.\F3::get('db_prefix').'items
                WHERE '.$this->stmt->isFalse('starred').' AND datetime<:date',
                    array(':date' => $date->format('Y-m-d').' 00:00:00')
            );
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
        $order = 'DESC';
                
        // only starred
        if(isset($options['type']) && $options['type']=='starred')
            $where .= ' AND '.$this->stmt->isTrue('starred');
            
        // only unread
        else if(isset($options['type']) && $options['type']=='unread'){
            $where .= ' AND '.$this->stmt->isTrue('unread');
            if(\F3::get('unread_order')=='asc'){
                $order = 'ASC';
            }
        }
        
        // search
        if(isset($options['search']) && strlen($options['search'])>0) {
            $search = implode('%', \helpers\Search::splitTerms($options['search']));
            $params[':search'] = $params[':search2'] = $params[':search3'] = array("%".$search."%", \PDO::PARAM_STR);
            $where .= ' AND (items.title LIKE :search OR items.content LIKE :search2 OR sources.title LIKE :search3) ';
        }
        
        // tag filter
        if(isset($options['tag']) && strlen($options['tag'])>0) {
            $params[':tag'] = array( "%,".$options['tag'].",%" , \PDO::PARAM_STR );
            if ( \F3::get( 'db_type' ) == 'mysql' ) {
              $source_tags_where = " WHERE ( CONCAT( ',' , sources.tags , ',' ) LIKE _utf8 :tag COLLATE utf8_bin ) ";
            } else {
              $source_tags_where = " WHERE ( (',' || sources.tags || ',') LIKE :tag ) ";
            }
            // Get source's id containing tag
            $resultset = \F3::get('db')->exec('SELECT id FROM sources'.$source_tags_where, $params);
            // Build source ids list
            $source_list = array();
            foreach ($resultset as $source)
                $sources_list[] = $source['id'];
            $where .= " AND source in (".implode(',',$sources_list).")";
        }

        // source filter
        elseif(isset($options['source']) && strlen($options['source'])>0) {
            $params[':source'] = array($options['source'], \PDO::PARAM_INT);
            $where .= " AND items.source=:source ";
        }

        // update time filter
        if(isset($options['updatedsince']) && strlen($options['updatedsince'])>0) {
            $params[':updatedsince'] = array($options['updatedsince'], \PDO::PARAM_STR);
            $where .= " AND items.updatetime > :updatedsince ";
        }

        // set limit
        if(!is_numeric($options['items']) || $options['items']>200)
            $options['items'] = \F3::get('items_perpage');
        
        // set offset
        if(is_numeric($options['offset'])===false)
            $options['offset'] = 0;
        
        // first check whether more items are available
        $result = \F3::get('db')->exec('SELECT items.id
                   FROM '.\F3::get('db_prefix').'items
                   WHERE 1=1 '.$where.'
                   LIMIT ' . ($options['offset']+$options['items']) . ', 1', $params);
        $this->hasMore = count($result);

        // Build list of items WITHOUT using any join
        // which is a perf killer if you start having a quite big items number
        $items_list = \F3::get('db')->exec('SELECT 
                    id, datetime, title, content, unread, starred, shared, source, thumbnail, icon, uid, link, updatetime, author
                   FROM '.\F3::get('db_prefix').'items AS items
                   WHERE 1=1 '.$where.' 
                   ORDER BY datetime '.$order.' 
                   LIMIT ' . $options['offset'] . ', ' . $options['items'], $params);
        // Iterate on each item and get source informations
        // from databse only if needed (ie. if not already retrieved)
        $sources_list = array();
        foreach ($items_list as $key => $item) {
            if (array_key_exists($item['source'], $sources_list)) {
                $items_list[$key] = array_merge($items_list[$key], $sources_list[$item['source']]);
            }else{
                $source = \F3::get('db')->exec('SELECT 
                        title as sourcetitle, tags
                       FROM '.\F3::get('db_prefix').'sources
                       WHERE id='.$item['source']);
                $sources_list[$item['source']] = $source[0];
                $items_list[$key] = array_merge($items_list[$key], $sources_list[$item['source']]);
            }
        }
        // We have the list of items, let's return it
        return $items_list;
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
        $result = \F3::get('db')->exec('SELECT thumbnail 
                   FROM '.\F3::get('db_prefix').'items 
                   WHERE thumbnail!=""');
        foreach($result as $thumb)
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
        $result = \F3::get('db')->exec('SELECT icon 
                   FROM '.\F3::get('db_prefix').'items 
                   WHERE icon!=""');
        foreach($result as $icon)
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
        $res = \F3::get('db')->exec('SELECT count(*) AS amount
                   FROM '.\F3::get('db_prefix').'items 
                   WHERE thumbnail=:thumbnail',
                  array(':thumbnail' => $thumbnail));
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
        $res = \F3::get('db')->exec('SELECT id
                   FROM items
                   WHERE icon=:icon limit 1',
                  array(':icon' => $icon));
        return sizeof($res)>0;
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
            
            if(is_array($value)) {
                $return = true;
                foreach($value as $id) {
                    if(is_numeric($id)===false) {
                        $return = false;
                        break;
                    }
                }
            }
            break;
        }
        
        return $return;
    }
    
    
    /**
     * returns the icon of the last fetched item.
     *
     * @return bool|string false if none was found
     * @param number $sourceid id of the source
     */
    public function getLastIcon($sourceid) {
        if(is_numeric($sourceid)===false)
            return false;
        
        $res = \F3::get('db')->exec('SELECT icon
                   FROM '.\F3::get('db_prefix').'items
                   WHERE source=:sourceid
                     AND icon!=\'\'
                     AND icon IS NOT NULL
                   ORDER BY ID DESC
                   LIMIT 1',
                    array(':sourceid' => $sourceid));
        if(count($res)==1)
            return $res[0]['icon'];
            
        return false;
    }
    
    
    /**
     * returns the amount of entries in database which are unread
     *
     * @return int amount of entries in database which are unread
     */
    public function numberOfUnread() {
        $res = \F3::get('db')->exec('SELECT count(*) AS amount
                   FROM '.\F3::get('db_prefix').'items 
                   WHERE '.$this->stmt->isTrue('unread'));
        return $res[0]['amount'];
    }
   
    
    /**
    * returns the amount of total, unread, starred entries in database
    *
    * @return array mount of total, unread, starred entries in database
    */
    public function stats() {
        $res = \F3::get('db')->exec('SELECT
            COUNT(*) AS total,
            '.$this->stmt->sumBool('unread').' AS unread,
            '.$this->stmt->sumBool('starred').' AS starred
            FROM '.\F3::get('db_prefix').'items;');
        return $res[0];
    }

    /**
     * Get Items score from source (used for source scoring)
     */
    public function getForScore($sourceid, $sourceupdate) {
        return \F3::get('db')->exec( 'SELECT count(*) AS count,
                                             sum(starred) AS stars,
                                             sum(shared)  AS shares,
                                             sum(opened)  AS opens
                                      FROM '.\F3::get('db_prefix').'items
                                      WHERE source = :source
                                        AND updatetime > :update',
                                     array(':source' => $sourceid, ':update' => date('Y-m-d H:m:s', $sourceupdate))
                                   );
    }

}
