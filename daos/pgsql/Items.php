<?PHP

namespace daos\pgsql;

/**
 * Class for accessing persistant saved items -- postgresql
 *
 * @package     daos
 * @copyright   Copyright (c) Michael Jackson <michael.o.jackson@gmail.com>
 * @license     GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Michael Jackson <michael.o.jackson@gmail.com>
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items extends \daos\mysql\Items { 
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
            $where .= ' AND starred=true ';
            
        // only unread
        else if(isset($options['type']) && $options['type']=='unread'){
            $where .= ' AND unread=true ';
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
            $where .= " AND ( (',' || sources.tags || ',') LIKE :tag ) ";
        }
        // source filter
        elseif(isset($options['source']) && strlen($options['source'])>0) {
            $params[':source'] = array($options['source'], \PDO::PARAM_INT);
            $where .= " AND items.source=:source ";
        }
        
        // set limit
        if(!is_numeric($options['items']) || $options['items']>200)
            $options['items'] = \F3::get('items_perpage');
        
        // first check whether more items are available
        $result = \F3::get('db')->exec('SELECT items.id
                   FROM items, sources
                   WHERE items.source=sources.id '.$where.' 
                   LIMIT 1 OFFSET ' . ($options['offset']+$options['items']), $params);
        $this->hasMore = count($result);

        // get items from database
        return \F3::get('db')->exec('SELECT 
                    items.id, datetime, items.title AS title, content, unread, starred, source, thumbnail, icon, uid, link, author, sources.title as sourcetitle, sources.tags as tags
                   FROM items, sources 
                   WHERE items.source=sources.id '.$where.' 
                   ORDER BY items.datetime '.$order.' 
                   LIMIT ' . $options['items'] . ' OFFSET ' . $options['offset'], $params);
    }
    
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
        \F3::get('db')->exec('UPDATE items SET unread=false WHERE id IN (' . $id . ')');
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
        \F3::get('db')->exec('UPDATE items SET unread=true WHERE id IN (:id)',
                    array(':id' => $id));
    }
    
    /**
     * returns the amount of entries in database which are unread
     *
     * @return int amount of entries in database which are unread
     */
    public function numberOfUnread() {
        $res = \F3::get('db')->exec('SELECT count(*) AS amount
                   FROM items 
                   WHERE unread=true');
        return $res[0]['amount'];
    }
    
    /**
     * star item
     *
     * @return void
     * @param int $id the item
     */
    public function starr($id) {
        \F3::get('db')->exec('UPDATE items SET starred=true WHERE id=:id', 
                    array(':id' => $id));
    }
    
    
    /**
     * un-star item
     *
     * @return void
     * @param int $id the item
     */
    public function unstarr($id) {
        \F3::get('db')->exec('UPDATE items SET starred=false WHERE id=:id',
                    array(':id' => $id));
    }
    /**
     * returns the amount of entries in database which are starred
     *
     * @return int amount of entries in database which are starred
     */
    public function numberOfStarred() {
        $res = \F3::get('db')->exec('SELECT count(*) AS amount
                   FROM items 
                   WHERE starred=true');
        return $res[0]['amount'];
    }
    
    /**
     * returns the amount of entries in database per tag
     *
     * @return int amount of entries in database per tag
     */
    public function numberOfUnreadForTag($tag) {
        $select = 'SELECT count(*) AS amount FROM items, sources';
        $where = ' WHERE items.source=sources.id AND unread=true';
        if ( \F3::get( 'db_type' ) == 'mysql' ) {
            $where .= " AND ( CONCAT( ',' , sources.tags , ',' ) LIKE :tag ) ";
        } else {
            $where .= " AND ( (',' || sources.tags || ',') LIKE :tag ) ";
        }
        $res = \F3::get('db')->exec( $select . $where,
            array(':tag' => "%,".$tag.",%"));
        return $res[0]['amount'];
    }
    
    
    /**
     * returns the amount of unread entries in database per source
     *
     * @return int amount of entries in database per tag
     */
    public function numberOfUnreadForSource($sourceid) {
        $res = \F3::get('db')->exec(
            'SELECT count(*) AS amount FROM items WHERE source=:source AND unread=true',
            array(':source' => $sourceid));
        return $res[0]['amount'];
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
        
        $res = \F3::get('db')->exec('SELECT icon FROM items WHERE source=:sourceid AND icon IS NOT NULL ORDER BY ID DESC LIMIT 1',
                    array(':sourceid' => $sourceid));
        if(count($res)==1)
            return $res[0]['icon'];
            
        return false;
    }

    /**
     * cleanup orphaned and old items
     *
     * @return void
     * @param DateTime $date date to delete all items older than this value [optional]
     */
    public function cleanup(\DateTime $date = NULL) {
        \F3::get('db')->exec('DELETE FROM items WHERE id IN (
                                SELECT items.id FROM items LEFT JOIN sources
                                ON items.source=sources.id WHERE sources.id IS NULL)');
        if ($date !== NULL)
            \F3::get('db')->exec('DELETE FROM items WHERE starred=false AND datetime<:date',
                    array(':date' => $date->format('Y-m-d').' 00:00:00'));
    }
}
