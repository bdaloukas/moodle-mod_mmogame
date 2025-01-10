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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is the renderable for report overview
 *
 * @package    mmogametype_quiz
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mmogametype_quiz\output\overview;

/**
 * Renderable class to store data of the quiz attempts report.
 */
class overview_renderable implements \renderable {
    /** @var int $id: The course module id. */
    public $id;
    /** @var int $id: The auserid. */
    public $auserid;
    /** @var int $id: The records with result. */
    public $records;
    /** @var int $id: True if user asked for exporting to cvs format */
    public $cvs;

    /**
     * The constructor
     *
     * @param ?array $records
     * @param ?int $id
     * @param ?int $auserid
     * @param ?string $cvs
     */
    public function __construct($records = [], $id = null, $auserid = null, $cvs = null) {
        $this->id = $id;
        $this->auserid = $auserid;
        $this->records = $records;
        $this->cvs = !($cvs === null);
    }
}
