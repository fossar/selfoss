<?PHP

namespace daos\pgsql;

/**
 * PostgreSQL specific statements
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
        $res = \F3::get('db')->exec("$query RETURNING id", $params);
        return $res[0]['id'];
    }


   /**
    * null first for order by clause
    *
    * @param column to concat
    * @param order
    * @return full statement
    */
    public static function nullFirst($column, $order) {
        if( $order == 'DESC' )
            $nulls = 'LAST';
        else if( $order == 'ASC' )
            $nulls = 'FIRST';
        return "$column $order NULLS $nulls";
    }


   /**
    * sum statement for boolean columns
    *
    * @param boolean column to concat
    * @return full statement
    */
    public static function sumBool($column) {
        return "SUM($column::int)";
    }


   /**
    * bool true statement
    *
    * @param column to check for truth
    * @return full statement
    */
    public static function isTrue($column) {
        return "$column=true";
    }


   /**
    * bool false statement
    *
    * @param column to check for false
    * @return full statement
    */
    public static function isFalse($column) {
        return "$column=false";
    }


   /**
    * check if CSV column matches a value.
    *
    * @param CSV column to check
    * @param value to search in CSV column
    * @return full statement
    */
    public static function csvRowMatches($column, $value) {
        return "$value=ANY(string_to_array($column, ','))";
    }
}
