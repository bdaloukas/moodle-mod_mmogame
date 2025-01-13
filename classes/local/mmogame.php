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
 * MMOGame class
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mmogame\local;

use coding_exception;
use mod_mmogame\local\database\mmogame_database;
use mod_mmogame\local\qbank\mmogame_qbank;
use stdClass;

/**
 * The class mmogame is the base class for all games
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogame {
    /** @var mmogame_database $db: database to be used. */
    protected mmogame_database $db;
    /** @var object $rgame: the record of table mmogame. */
    protected object $rgame;
    /** @var int $auserid: the user (table mmogame_aa_users). */
    protected int $auserid = 0;
    /** @var mmogame_qbank $qbank: question bank to be used. */
    protected mmogame_qbank $qbank;

    /** @var string $error: saves the error code. */
    protected string $error = '';

    /** @var int $timelimit: maximum time in seconds for answer. */
    protected int $timelimit = 0;

    /** @var bool|object $rstate: the record of table mmogame_aa_states. */
    protected $rstate;

    /**
     * Constructor.
     *
     * @param mmogame_database $db (the database)
     * @param object $rgame (a record from table mmogame)
     */
    public function __construct(mmogame_database $db, object $rgame) {
        $this->db = $db;

        if ($rgame->numgame == 0) {
            $this->db->update_record( 'mmogame', ['id' => $rgame->id, 'numgame' => 1]);
            $rgame = $this->db->get_record_select( 'mmogame', 'id=?', [$rgame->id]);
        }

        $this->rgame = $rgame;

        $this->rstate = $this->db->get_record_select( 'mmogame_aa_states', 'mmogameid=? AND numgame=?',
            [$this->rgame->id, $this->rgame->numgame]);
        if ($this->rstate === null) {
            $id = $this->db->insert_record( 'mmogame_aa_states',
                ['mmogameid' => $this->rgame->id,
                'numgame' => $this->rgame->numgame, 'state' => 0,
                ]);
            $this->rstate = $this->db->get_record_select( 'mmogame_aa_states', 'id=?', [$id]);
        }

        if ($rgame->qbank != '') {
            $classname = 'mod_mmogame\local\qbank\mmogame_qbank_'.$rgame->qbank;
            $this->qbank = new $classname( $this);
        }
    }

    /**
     * Sets the variable code.
     @param string $code
     */
    public function set_errorcode($code): void {
        $this->error = $code;
    }

    /**
     * Returns the variable error.
     */
    public function get_errorcode(): string {
        return $this->error;
    }

    /**
     * Returns the variable timelimit.
     */
    public function get_timelimit(): int {
        return $this->timelimit;
    }

    /**
     * Return the variable db.
     */
    public function get_db(): object {
        return $this->db;
    }

    /**
     * Return the variable rgame.
     */
    public function get_rgame() {
        return $this->rgame;
    }

    /**
     * Return the variable rstate.
     */
    public function get_rstate() {
        return $this->rstate;
    }

    /**
     * Return the variable rgame->id.
     */
    public function get_id() {
        return $this->rgame->id;
    }

    /**
     * Return the variable rgame->model.
     */
    public function get_model() {
        return $this->rgame->model;
    }

    /**
     * Return the variable rgame->numgame.
     */
    public function get_numgame() {
        return $this->rgame->numgame;
    }

    /**
     * Return the variable rstate->state.
     */
    public function get_state() {
        return $this->rstate !== false ? $this->rstate->state : 0;
    }

    /**
     * Return the variable rgame->type.
     */
    public function get_type() {
        return $this->rgame->type;
    }

    /**
     * Return the variable qbank.
     */
    public function get_qbank() {
        return $this->qbank;
    }

    /**
     * Return the variable auserid.
     */
    public function get_auserid(): int {
        return $this->auserid;
    }

    /**
     * Return an empty string. It is overwring.
     */
    public static function get_table_attempts(): string {
        return '';
    }

    /**
     * Return coresponding auserid from guid (login without a password).
     * @param object $db
     * @param string $guid
     * @param bool $create
     * @return bool|int
     */
    public static function get_auserid_from_guid(object $db, string $guid, bool $create = true) {
        $rec = $db->get_record_select( 'mmogame_aa_users_guid', 'guid=?', [$guid]);
        if ($rec === false) {
            if (!$create) {
                return false;
            }
            $userid = $db->insert_record( 'mmogame_aa_users_guid', ['guid' => $guid, 'lastlogin' => time()]);
        } else {
            $userid = $rec->id;
        }

        return self::get_auserid_from_db($db, 'guid', $userid, $create);
    }

    /**
     * Return coresponding auserid from a users in the table mmogame_aa_users_code.
     * @param object $db
     * @param string $code
     * @return false|int
     */
    public static function get_auserid_from_usercode(object $db, string $code) {
        $rec = $db->get_record_select( 'mmogame_aa_users_code', 'code=?', [$code]);
        if ($rec === false) {
            return false;
        }

        return self::get_auserid_from_db($db, 'usercode', $rec->id, true);
    }

    /**
     * Return the corresponding auserid from a user.
     * @param object $db
     * @param string $kind (the kind of user e.g., Moodle, GUID)
     * @param string $userid
     * @param bool $create
     * @return ?int
     */
    public static function get_auserid_from_db(mmogame_database $db, string $kind, int $userid, bool $create) {
        $rec = $db->get_record_select( 'mmogame_aa_users', 'kind = ? AND instanceid=?', [$kind, $userid]);

        if ($rec !== null) {
            return $rec->id;
        }

        if (!$create) {
            return null;
        }

        return $db->insert_record( 'mmogame_aa_users',
            ['kind' => $kind, 'instanceid' => $userid, 'lastlogin' => time(), 'lastip' => self::get_ip()]);
    }

    /**
     * Return corresponding auserid from input parameters.
     * @param mmogame_database $db
     * @param string $kinduser
     * @param string $user
     * @return int (the id of table mmogame_aa_users)
     */
    public static function get_asuerid(mmogame_database $db, string $kinduser, string $user) {
        if ($kinduser == 'usercode') {
            return self::get_auserid_from_usercode($db, $user);
        } else if ($kinduser == 'guid') {
            return self::get_auserid_from_guid( $db, $user);
        } else {
            return self::get_auserid_from_db( $db, $kinduser, $user, true);
        }
    }

    /**
     * Marks user as loged in.
     * @param int $auserid
     */
    public function login_user(int $auserid) {
        $this->db->update_record( 'mmogame_aa_users',
            ['id' => $auserid, 'lastlogin' => time(), 'lastip' => self::get_ip()]);

        $this->auserid = $auserid;
    }

    /**
     * Returns a game object
     *
     * @param mmogame_database $db
     * @param int $id
     * @return false|mmogame
     * @throws coding_exception
     */
    public static function create(mmogame_database $db, int $id) {
        $rgame = $db->get_record_select('mmogame', "id=?", [$id]);
        if ($rgame === false) {
            return false;
        }

        $classname = 'mmogametype_' . $rgame->type.'\local\mmogametype_' . $rgame->type.'_'.$rgame->model;
        if (!class_exists($classname)) {
            throw new coding_exception("Class $classname does not exist for type: $rgame->type");
        }
        return new $classname($db, $rgame);
    }

    /**
     * Returns the next numattempt of the current game.
     */
    public function compute_next_numattempt() {
        $rec = $this->db->get_record_select( $this->get_table_attempts(), 'mmogameid=? AND numgame=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $this->get_auserid()], 'MAX(numattempt) as maxnum');
        return $rec->maxnum + 1;
    }

    /**
     * Returns the default avatar for user auserid
     * @return int
     */
    protected function get_avatar_default(): int {
        // Compute default avatar.
        $db = $this->db;

        // Ones that are not used in this numgame.
        $sql = "SELECT a.id, numused FROM {mmogame_aa_avatars} a ".
            " LEFT JOIN {mmogame_aa_grades} g ON g.avatarid=a.id AND g.mmogameid=? AND g.numgame=?".
            " WHERE g.id IS NULL ".
            " ORDER BY a.numused,a.randomkey";
        $recs = $db->get_records_sql( $sql, [$this->rgame->id, $this->rgame->numgame], 0, 1);
        if ($recs === false || count($recs) === 0) {
            // All avatars are used in this numgame (players > avatars).
            $recs = $db->get_records_select('mmogame_aa_avatars', '', null, 'numused, randomkey', '*', 0, 1);
            if ($recs === false || count( $recs) == 0) {
                return 0;
            }
        }
        $rec = reset($recs);
        $db->update_record( 'mmogame_aa_avatars',
            ['id' => $rec->id, 'numused' => $rec->numused + 1, 'randomkey' => mt_rand()]);

        return $rec->id;
    }

    /**
     * Returns the grade for user auserid
     *
     * @param int $auserid
     * @return ?stdClass
     */
    public function get_grade(int $auserid): ?stdClass {

        $db = $this->db;

        $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $auserid]);
        if ($rec !== null) {
            return $rec;
        }

        $grades = $db->get_records_select( 'mmogame_aa_grades', 'mmogameid=? AND auserid=? AND numgame < ?',
            [$this->rgame->id, $auserid, $this->rgame->numgame], 'numgame DESC', '*', 1);
        $a = ['mmogameid' => $this->rgame->id, 'numgame' => $this->rgame->numgame, 'auserid' => $auserid,
             'timemodified' => time(), 'sumscore' => 0,
        ];
        if ($grades !== false && count( $grades) > 0) {
            $grade = reset( $grades);
            $a['avatarid'] = $grade->avatarid;
            $a['usercode'] = $grade->usercode;
            $a['nickname'] = $grade->nickname;
            $a['colorpaletteid'] = $grade->colorpaletteid;
        } else {
            $a['avatarid'] = $this->get_avatar_default();
            $recs = $this->db->get_records_select( 'mmogame_aa_colorpalettes', '', [], 'id', 'id', 1);
            $a['colorpaletteid'] = reset( $recs)->id;

            $user = $db->get_record_select( 'mmogame_aa_users', 'id=?', [$auserid]);
            if ($user !== false) {
                if ($user->kind == 'usercode') {
                    $rec = $db->get_record_select( 'mmogame_aa_users_code', 'id=?', [$user->instanceid]);
                    if ($rec !== false && $rec->code != 0) {
                        $a['usercode'] = $rec->code;
                    }
                }
            }
        }
        $id = $db->insert_record( 'mmogame_aa_grades', $a);

        return $db->get_record_select( 'mmogame_aa_grades', 'id=?', [$id]);
    }

    /**
     * Returns info about avatar for the user auserid.
     *
     * @param int $auserid
     * @return false|object
     */
    public function get_avatar_info(int $auserid) {
        $sql = "SELECT g.*, a.directory, a.filename, a.id as aid, c.color1, c.color2, c.color3, c.color4, c.color5".
            " FROM {mmogame_aa_grades} g LEFT JOIN {mmogame_aa_avatars} a ON g.avatarid=a.id".
            " LEFT JOIN {mmogame_aa_colorpalettes} c ON c.id=g.colorpaletteid ".
            " WHERE g.mmogameid=? AND g.numgame=? AND g.auserid=?";
        $grades = $this->db->get_records_sql( $sql, [$this->rgame->id, $this->rgame->numgame, $auserid], 0, 1);
        if (count($grades) == 0) {
            $grade = $this->get_grade( $auserid);
            if ($grade === false) {
                return false;
            }
            $grades = $this->db->get_records_sql( $sql, [$this->rgame->id, $this->rgame->numgame, $auserid], 0, 1);
        }
        $grade = reset( $grades);
        if ($grade->aid == null) {
            $this->db->update_record( 'mmogame_aa_grades',
                ['id' => $grade->id, 'avatarid' => $this->get_avatar_default()]);
            $grade = $this->db->get_record_sql( $sql, [$this->rgame->id, $this->rgame->numgame, $auserid]);
        }
        $grade->avatar = $grade->directory.'/'.$grade->filename;
        $grade->colors = [$grade->color1, $grade->color2, $grade->color3, $grade->color4, $grade->color5];

        return $grade;
    }

    /**
     * Returns the rank for user $auserid based on $field
     *
     * @param int $auserid
     * @param string $field
     * @return int
     */
    public function get_rank(int $auserid, string $field): int {
        $grade = $this->get_grade( $auserid);

        $value = $grade->$field;
        if ($value == null) {
            $value = 0;
        }

        return $this->db->count_records_select( 'mmogame_aa_grades',
                "mmogameid=? AND numgame=? AND $field > ?",
                [$this->rgame->id, $this->rgame->numgame, $value]) + 1;
    }

    /**
     * Returns IP address of the client.
     *
     * @return string
     */
    public static function get_ip(): string {
        return array_key_exists( 'REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     * Returns the available avatars for user auserid.
     *
     * @param int $auserid
     * @return array
     */
    public function get_avatars(int $auserid): array {
        $info = $this->get_avatar_info( $auserid);

        $where = 'ishidden = 0 AND '.
            "id NOT IN (SELECT avatarid ".
            "FROM {mmogame_aa_grades} WHERE mmogameid=? AND numgame=? AND auserid<>?)";
        $grades = $this->db->get_records_select( 'mmogame_aa_avatars', $where,
            [$this->rgame->id, $info->numgame, $info->auserid]);
        $ret = [];
        foreach ($grades as $grade) {
            $ret[$grade->id] = $grade->directory.'/'.$grade->filename;
        }

        return $ret;
    }

    /**
     * Returns the available color palettes for the user auserid.
     *
     * @return array with id in key and 5 colors at value
     */
    public function get_palettes(): array {
        $recs = $this->db->get_records_select( 'mmogame_aa_colorpalettes', '', null, 'hue');
        $ret = [];
        foreach ($recs as $rec) {
            $ret[$rec->id] = [$rec->colorsort1, $rec->colorsort2, $rec->colorsort3, $rec->colorsort4, $rec->colorsort5];
        }

        return $ret;
    }

    /**
     * Writes filecontents in the state file.
     *
     * @param int $state
     * @param string $filecontents
     * @return string (the directory where the data is saved)
     */
    public function save_state_file(int $state, string $filecontents): string {
        global $CFG;

        // Creates an upload directory in temp.
        $file = $this->rgame->fastjson === null ? '00' : $this->rgame->fastjson;
        $newdir = $CFG->dataroot.'/local/mmogame/states/'.substr( $file, -2);
        if (!is_dir( $newdir)) {
            mkdir( $newdir, 0777, true);
        }

        if ($filecontents != '') {
            $file = "$newdir/$file-$state.txt";
            if (!file_exists( $file) || file_get_contents( $file) != $filecontents) {
                file_put_contents( $file, $filecontents);
            }
        }

        $file = sprintf("%s/%s.txt", $newdir, $this->rgame->fastjson);
        if (!file_exists( $file)) {
            file_put_contents( $file, $this->rstate->state.'-'.$this->rgame->timefastjson);
        }

        return $newdir;
    }

    /**
     * Saves state info for fast communication with clients.
     *
     * @param int $state
     * @param string $statecontents
     * @param string $filecontents
     * @param int $timefastjson
     */
    public function save_state(int $state, string $statecontents, string $filecontents, int $timefastjson) {

        $newdir = $this->save_state_file( $state, $filecontents);

        $file = $this->rgame->fastjson;
        file_put_contents( "$newdir/$file.txt", $statecontents);

        for ($i = 0; $i <= 4; $i++) {
            if ($i == $state) {
                continue;
            }
            $f = "$newdir/$file-$i.txt";
            if (file_exists( $f)) {
                unlink( $f);
            }
        }
        if ($timefastjson != 0) {
            $this->rgame->timefastjson = $timefastjson;
            $this->db->update_record( 'mmogame',
                ['id' => $this->rgame->id, 'timefastjson' => $timefastjson]);
        }
    }

    /**
     * Update state in database.
     *
     * @param int $state
     */
    public function update_state(int $state) {
        $this->rstate->state = $state;
        $this->db->update_record( 'mmogame_aa_states', ['id' => $this->rstate->id, 'state' => $state]);
    }

    /**
     * Returns a new unique pin of mmogame with id=$mmogameid
     *
     * @param int $mmogameid
     * @param object $db
     * @param int $digits (number of digits for new pin)
     * @return int (the new pin)
     */
    public static function get_newpin(int $mmogameid, object $db, int $digits): int {
        $min = pow( 10, $digits - 1) + 1;
        $max = pow( 10, $digits) - 1;
        for (;;) {
            $pin = mt_rand( $min, $max);
            if ($mmogameid == 0) {
                return $pin;
            }
            $rec = $db->get_record_select( 'mmogame', 'pin=?', [$pin]);
            if ($rec === false) {
                return $pin;
            }
        }
    }

    /**
     * Deletes info for a given mmogame and auser
     *
     * @param mmogame_database $db
     * @param stdClass $rgame
     * @param int $auserid
     */
    public static function delete_auser(mmogame_database $db, stdClass $rgame, int $auserid) {
        $db->delete_records_select( 'mmogame_aa_grades', 'mmogameid=? AND auserid=?', [$rgame->id, $auserid]);
        $db->delete_records_select( 'mmogame_aa_stats', 'mmogameid=? AND auserid=?', [$rgame->id, $auserid]);
        $db->delete_records_select( 'mmogame_am_aduel_pairs',
            'mmogameid = ? AND (auserid1=? OR auserid2=?)', [$rgame->id, $auserid, $auserid]);

        require_once( 'type/'.$rgame->type.'/'.$rgame->type.'.php');
        $class = 'mmogame_'.$rgame->type;
        $class::delete_auser( $db, $rgame, $auserid);

        $db->delete_records_select( 'mmogame_aa_users', 'id=?', [$auserid]);
    }
}
