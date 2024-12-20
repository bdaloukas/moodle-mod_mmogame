<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is the entry point to the game module. All pages are rendered from here
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is the base class for database access.
 */
class mmogame_database {

};


/**
 * This class extends the mmogame_database with code explicit with Moodle.
 */
class mmogame_database_moodle extends mmogame_database {

    /**
     * The prefix of tables.
     *
     * @var string $prefix
     */
    public $prefix;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;

        $this->prefix = $CFG->prefix;
    }

    /**
     * This function insert a record in database.
     *
     * @param string $table
     * @param array $a
     * @return true if the insertions is ok, otherwise false.
     */
    public function insert_record($table, $a) {
        global $DB;

        $rec = new StdClass;
        foreach ($a as $name => $value) {
            $rec->$name = $value;
        }
        return $DB->insert_record( $table, $rec);
    }

    /**
     * For rare cases when you also need to specify the ID of the record to be inserted.
     *
     * @param string $table
     * @param array $a
     * @param int $returnid
     * @param object $customsequence
     * @return true if the insertions is ok, otherwise false.
     */
    public function insert_record_raw($table, $a, $returnid, $customsequence) {
        global $DB;

        $rec = new StdClass;
        foreach ($a as $name => $value) {
            $rec->$name = $value;
        }
        return $DB->insert_record_raw( $table, $rec, $returnid, false, $customsequence);
    }

    /**
     * If you need to perform a complex update using arbitrary SQL, you can use the low level "execute" method.
        Only use this when no specialised method exists.
     *
     * @param string $sql
     * @param array $params
     */
    public function execute($sql, $params=null) {
        global $DB;

        $DB->execute( $sql, $params);
    }

    /**
     * Return a single database record as an object where the given conditions are used in the WHERE clause.
     *
     * @param string $table The name of the database table.
     * @param string $select The SQL condition to use in the WHERE clause.
     * @param array|null $params Optional parameters for the SQL condition.
     * @param string $fields Fields to return, defaults to '*'.
     * @return object|false The database record as an object, or false if not found.
     */
    public function get_record_select($table, $select, ?array $params=null, $fields='*') {
        global $DB;

        return $DB->get_record_select( $table, $select, $params, $fields);
    }

    /**
     * Returns the first record that matches the given criteria.
     *
     * @param string $table The name of the database table.
     * @param string $select The SQL condition for the WHERE clause.
     * @param array|null $params Optional parameters for the SQL condition.
     * @param string $sort Optional sorting order.
     * @param string $fields The fields to return, default is '*'.
     * @return object|false The first matching database record as an object, or false if no record is found.
     */
    public function get_record_select_first($table, $select, ?array $params=null, $sort='', $fields='*') {
        global $DB;

        $recs = $DB->get_records_select( $table, $select, $params, $sort, $fields, 0, 1);
        foreach ($recs as $rec) {
            return $rec;
        }
        return false;
    }

    /**
     * Returns a list of records as an array of objects where the specified conditions are used in the WHERE clause.
     *
     * @param string $table The name of the database table.
     * @param string $select The SQL condition for the WHERE clause.
     * @param array|null $params Optional parameters for the SQL condition.
     * @param string $sort Optional sorting order.
     * @param string $fields The fields to return, default is '*'.
     * @param int $limitfrom The starting point of records to return, default is 0.
     * @param int $limitnum The number of records to return, default is 0 (no limit).
     * @return array An array of database records as objects.
     */
    public function get_records_select($table, $select, ?array $params=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0) {
        global $DB;

        return $DB->get_records_select( $table, $select, $params, $sort, $fields, $limitfrom, $limitnum);
    }

    /**
     * Count the records in a table where the given conditions are used in the WHERE clause.
     *
     * @param string $table The name of the table to count records from.
     * @param string $select The SQL SELECT statement used for counting records.
     * @param array|null $params Optional parameters for the SELECT statement.
     * @param string $countitem The COUNT item to be used, defaults to "COUNT('*')".
     * @return int The number of records that match the given conditions.
     */
    public function count_records_select($table, $select, ?array $params=null, $countitem="COUNT('*')") {
        global $DB;

        return $DB->count_records_select( $table, $select, $params, $countitem);
    }

    /**
     * Returns a single database record as an object using a custom SELECT query.
     *
     * @param string $sql The custom SQL SELECT query to execute.
     * @param array|null $params Optional parameters for the SQL query.
     * @return object|false The database record as an object, or false if no record is found.
     */
    public function get_record_sql($sql, ?array $params=null) {
        global $DB;

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Returns a list of records as an array of objects using a custom SELECT query.
     *
     * @param string $sql The custom SQL SELECT query to execute.
     * @param array|null $params Optional parameters for the SQL query.
     * @param int $limitfrom The starting point of records to return, default is 0.
     * @param int $limitnum The number of records to return, default is 0 (no limit).
     * @return array An array of database records as objects.
     */
    public function get_records_sql($sql, ?array $params=null, $limitfrom=0, $limitnum=0) {
        global $DB;

        return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * Update a record in the table. The data object must have the property "id" set.
     *
     * @param string $table
     * @param array $a
     */
    public function update_record($table, $a) {
        global $DB;

        $rec = new StdClass;
        foreach ($a as $name => $value) {
            $rec->$name = $value;
        }

        $DB->update_record( $table, $rec);
    }

    /**
     * Deletes records from the specified table where the given conditions are used in the WHERE clause.
     *
     * @param string $table The name of the database table.
     * @param string $select The SQL condition for the WHERE clause.
     * @param array|null $params Optional parameters for the SQL condition.
     * @return void
     */
    public function delete_records_select($table, $select, ?array $params=null) {
        global $DB;

        $DB->delete_records_select($table, $select, $params);
    }

    /**
     * Returns the equivalent of if in database.
     *
     * @param string $condition
     * @param string $iftrue
     * @param string $iffalse
     * @return string
     */
    public function iif($condition, $iftrue, $iffalse) {
        return "IF($condition,$iftrue,$iffalse)";
    }
}
