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
 * Defines the database abstraction contract used by MMOGame.
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mmogame\local\database;

use stdClass;

/**
 * Base database abstraction for Moodle-compatible MMOGame data access.
 */
abstract class mmogame_database {
    /**
     * Inserts a row into a plugin database table.
     *
     * @param string               $table Unprefixed or prefixed table name.
     * @param array<string, mixed> $a     Column values to insert.
     * @return int|null Inserted record ID, or null when the insert fails.
     */
    abstract public function insert_record(string $table, array $a): ?int;

    /**
     * Retrieves a single row using a WHERE fragment.
     *
     * @param string                                             $table  Unprefixed or prefixed table name.
     * @param string                                             $select WHERE fragment without the WHERE keyword.
     * @param array<string, mixed>|array<int, mixed>|null         $params Bound SQL parameters.
     * @param string                                             $fields Comma-separated field list to return.
     * @return stdClass|null Matching row, or null when no row is found.
     */
    abstract public function get_record_select(
        string $table,
        string $select,
        ?array $params = null,
        string $fields = '*'
    ): ?stdClass;

    /**
     * Retrieves multiple rows using a WHERE fragment.
     *
     * @param string                                             $table     Unprefixed or prefixed table name.
     * @param string                                             $select    WHERE fragment without the WHERE keyword.
     * @param array<string, mixed>|array<int, mixed>|null         $params    Bound SQL parameters.
     * @param string                                             $sort      ORDER BY fragment without the ORDER BY keyword.
     * @param string                                             $fields    Comma-separated field list to return.
     * @param int                                                $limitfrom Offset for limited result sets.
     * @param int                                                $limitnum  Maximum number of rows to return. Zero means no limit.
     * @return array<int|string, object> Matching rows.
     */
    abstract public function get_records_select(
        string $table,
        string $select,
        ?array $params = null,
        string $sort = '',
        string $fields = '*',
        int $limitfrom = 0,
        int $limitnum = 0
    ): array;

    /**
     * Counts rows using a WHERE fragment.
     *
     * @param string                                             $table     Unprefixed or prefixed table name.
     * @param string                                             $select    WHERE fragment without the WHERE keyword.
     * @param array<string, mixed>|array<int, mixed>|null         $params    Bound SQL parameters.
     * @param string                                             $countitem SQL count expression.
     * @return int Number of matching rows.
     */
    abstract public function count_records_select(
        string $table,
        string $select,
        ?array $params = null,
        string $countitem = "COUNT('*')"
    ): int;

    /**
     * Retrieves a single row using a raw SELECT query accepted by the implementation.
     *
     * @param string                                             $sql    SELECT query.
     * @param array<string, mixed>|array<int, mixed>|null         $params Bound SQL parameters.
     * @return stdClass|null Matching row, or null when no row is found.
     */
    abstract public function get_record_sql(string $sql, ?array $params = null): ?stdClass;

    /**
     * Returns a list of records as an array of objects using a custom SELECT query.
     *
     * @param string $sql The custom SQL SELECT query to execute.
     * @param ?array $params Optional parameters for the SQL query.
     * @param int $limitfrom The starting point of records to return, default is 0.
     * @param int $limitnum The number of records to return, default is 0 (no limit).
     * @return array An array of database records as objects.
     */
    abstract public function get_records_sql(string $sql, ?array $params = null, int $limitfrom = 0, int $limitnum = 0): array;

    /**
     * Updates a row in a plugin database table.
     *
     * The values array must contain an id field used as the update condition.
     *
     * @param string               $table Unprefixed or prefixed table name.
     * @param array<string, mixed> $a     Column values to update, including id.
     * @return void
     */
    abstract public function update_record(string $table, array $a): void;

    /**
     * Deletes rows using a WHERE fragment.
     *
     * @param string                                             $table  Unprefixed or prefixed table name.
     * @param string                                             $select WHERE fragment without the WHERE keyword.
     * @param array<string, mixed>|array<int, mixed>|null         $params Bound SQL parameters.
     * @return bool True when the delete query succeeds.
     */
    abstract public function delete_records_select(string $table, string $select, ?array $params = null): bool;

    /**
     * Builds an SQL comparison fragment for one or more values.
     *
     * Implementations return an SQL fragment and the matching bound parameters.
     *
     * @param array<int, mixed> $items        Values for the comparison.
     * @param int              $type         Placeholder mode.
     * @param string           $prefix       Prefix for named placeholders.
     * @param bool             $equal        True for IN or = comparisons, false for NOT IN or <> comparisons.
     * @param bool             $onemptyitems Whether empty item lists are allowed.
     * @return array{0: string, 1: array<int, mixed>|array<string, mixed>} SQL fragment and bound parameters.
     */
    abstract public function get_in_or_equal(
        array $items,
        int $type = SQL_PARAMS_QM,
        string $prefix = 'param',
        bool $equal = true,
        bool $onemptyitems = false
    ): array;
}
