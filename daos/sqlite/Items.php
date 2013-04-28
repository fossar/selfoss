<?PHP

namespace daos\sqlite;

/**
 * Class for accessing persistent saved items -- sqlite
 *
 * @package    daos
 * @copyright  Copyright (c) Harald Lapp <harald.lapp@gmail.com>
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
// class Items extends Database {
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
            \F3::get('db')->exec('DELETE FROM items WHERE starred=0 AND datetime<:date',
                    array(':date' => $date->format('Y-m-d').' 00:00:00'));
    }

}
