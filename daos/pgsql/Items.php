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
