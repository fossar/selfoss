<?PHP

namespace daos\sqlite;

/**
 * Sqlite specific statements
 *
 * @package    daos
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class Statements extends \daos\mysql\Statements {

   /**
    * wrap insert statement to return id
    *
    * @param sql statement
    * @param sql params
    * @return id after insert
    */
    public static function insert($query, $params) {
        \F3::get('db')->exec($query, $params);
        $res = \F3::get('db')->exec('SELECT last_insert_rowid() as lastid');
        return $res[0]['lastid'];
    }


   /**
    * check if CSV column matches a value.
    *
    * @param CSV column to check
    * @param value to search in CSV column
    * @return full statement
    */
    public static function csvRowMatches($column, $value) {
        return "(',' || $column || ',') LIKE ('%,' || $value || ',%')";
    }
}
