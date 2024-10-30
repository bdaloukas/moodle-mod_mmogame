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

defined('MOODLE_INTERNAL') || die;

require_once( dirname(__FILE__).'/qbank/qbank.php');

define( 'MMOGAME_ALONE_STATE_NONE', 0);
define( 'MMOGAME_ALONE_STATE_PLAY', 1);
define( 'MMOGAME_ALONE_STATE_LAST', 1);

define( 'MMOGAME_ADUEL_STATE_NONE', 0);
define( 'MMOGAME_ADUEL_STATE_PLAY', 1);
define( 'MMOGAME_ADUEL_STATE_LAST', 1);

define( 'MMOGAME_ERRORCODE_NOQUERIES', 'noqueries');

/**
 * The class mmogame is the base class for all games
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogame {
    /** @var db: database to be used. */
    protected $db;
    /** @var rgame: the record of table mmogame. */
    protected $rgame;
    /** @var auserid: the user (table mmogame_aa_users). */
    protected $auserid = 0;
    /** @var qbank: question bank to be used. */
    protected $qbank = false;
    /** @var error: saves the error code. */
    protected $error = '';
    /** @var timelimit: maximum time in seconds for answer. */
    protected $timelimit = 0;
    /** @var rstate: the record of table mmogame_aa_states. */
    protected $rstate;
    /** @var params: parameters. */
    protected $params;

    /**
     * Constructor.
     *
     * @param object $db (the database)
     * @param object $rgame (a record from table mmogame)
     */
    public function __construct($db, $rgame) {
        $this->db = $db;

        if ($rgame->numgame == 0) {
            $this->db->update_record( 'mmogame', ['id' => $rgame->id, 'numgame' => 1]);
            $rgame = $this->db->get_record_select( 'mmogame', 'id=?', [$rgame->id]);
        }

        $this->rgame = $rgame;

        $this->rstate = $this->db->get_record_select( 'mmogame_aa_states', 'mmogameid=? AND numgame=?',
            [$this->rgame->id, $this->rgame->numgame]);
        if ($this->rstate == false) {
            $id = $this->db->insert_record( 'mmogame_aa_states',
                ['mmogameid' => $this->rgame->id,
                'numgame' => $this->rgame->numgame, 'state' => 0,
                ]);
            $this->rstate = $this->db->get_record_select( 'mmogame_aa_states', 'id=?', [$id]);
        }

        if ($rgame->qbank != '') {
            require_once( dirname(__FILE__).'/qbank/'.$rgame->qbank.'.php');
            $name = 'mmogameqbank_'.$rgame->qbank;
            $this->qbank = new $name( $this);
        }
    }

    /**
     * Return the params variable.
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * Sets the variable code.
     @param string $code
     */
    public function set_errorcode($code) {
        $this->error = $code;
    }

    /**
     * Returns the variable error.
     */
    public function get_errorcode() {
        return $this->error;
    }

    /**
     * Returns the variable timelimit.
     */
    public function get_timelimit() {
        return $this->timelimit;
    }

    /**
     * Return the variable db.
     */
    public function get_db() {
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
        return $this->rstate->state;
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
    public function get_auserid() {
        return $this->auserid;
    }

    /**
     * Return the coresponding record of variable auserid.
     */
    public function get_ruser() {
        return $this->db->get_record_select( 'mmogame_aa_users', 'id=?', [$this->auserid]);
    }

    /**
     * Return coresponding auserid from guid (login without password).
     * @param object $db
     * @param string $guid
     * @param boolean $create
     */
    public static function get_auserid_from_guid($db, $guid, $create = true) {
        $rec = $db->get_record_select( 'mmogame_aa_users_guid', 'guid=?', [$guid]);
        if ($rec === false) {
            if ($create == false) {
                return false;
            }
            $userid = $db->insert_record( 'mmogame_aa_users_guid', ['guid' => $guid, 'lastlogin' => time()]);
        } else {
            $userid = $rec->id;
        }

        return self::get_auserid_from_db($db, 'guid', $userid, $create);
    }

    /**
     * Return coresponding auserid from a Moodle user.
     * @param object $db
     * @param int $userid
     * @param boolean $create
     */
    public static function get_auserid_from_moodle($db, $userid, $create) {

        return self::get_auserid_from_db($db, 'moodle', $userid, $create);
    }

    /**
     * Return coresponding auserid from a users in the table mmogame_aa_users_code.
     * @param object $db
     * @param string $code
     */
    public static function get_auserid_from_usercode($db, $code) {
        $rec = $db->get_record_select( 'mmogame_aa_users_code', 'code=?', [$code]);
        if ($rec === false) {
            return false;
        }

        return self::get_auserid_from_db($db, 'usercode', $rec->id, true);
    }

    /**
     * Return coresponding auserid from a user.
     * @param object $db
     * @param string $kind (the kind of user e.g. Moodle, GUID)
     * @param string $userid
     * @param boolean $create
     */
    public static function get_auserid_from_db($db, $kind, $userid, $create) {
        $rec = $db->get_record_select( 'mmogame_aa_users', 'kind = ? AND instanceid=?', [$kind, $userid]);

        if ($rec != false) {
            return $rec->id;
        }

        if (!$create) {
            return false;
        }

        return $db->insert_record( 'mmogame_aa_users',
            ['kind' => $kind, 'instanceid' => $userid, 'lastlogin' => time(), 'lastip' => self::get_ip()]);
    }

    /**
     * Return coresponding auserid from a user (details are in object variable $data).
     * @param object $db
     * @param object $data
     * @return int
     */
    public static function get_asuerid_from_object($db, $data) {
        if (isset( $data->kinduser)) {
            if ($data->kinduser == 'usercode') {
                return self::get_auserid_from_usercode($db, $data->user);
            } else if ($data->kinduser == 'guid') {
                return self::get_auserid_from_guid( $db, $data->user);
            } else if ($data->kinduser == 'moodle') {
                return self::get_auserid_from_db( $db, 'moodle', $data->user, true);
            }
        } else {
            return self::get_auserid_from_guid( $db, $data->user, true);
        }

        return false;
    }

    /**
     * Marks user as loged in.
     * @param int $auserid
     */
    public function login_user($auserid) {
        $this->db->update_record( 'mmogame_aa_users',
            ['id' => $auserid, 'lastlogin' => time(), 'lastip' => self::get_ip()]);

        $this->auserid = $auserid;
    }

    /**
     * Returns a game object
     *
     * @param object $db
     * @param int $id
     * @return object
     */
    public static function getgame($db, $id) {
        $rgame = $db->get_record_select('mmogame', "id=?", [$id]);
        if ($rgame === false) {
            return false;
        }
        require_once( "type/{$rgame->type}/{$rgame->type}_{$rgame->model}.php");

        $class = 'mmogame_'.$rgame->type;
        return $class::get_new($db, $rgame);
    }

    /**
     * Fills the array $ret with info values`
     *
     * @param string $table
     * @param int $mainid
     * @param object $data
     * @param array $ret
     */
    public function json_items_table($table, $mainid, $data, &$ret) {
        $recs = $this->db->get_records_select( $table, 'mainid=?', [$mainid]);

        $ret = [];
        $num = 1;
        foreach ($recs as $rec) {
            $squestion = $this->qbank->load_json( $this->rgame->course, $this->db, $ret, $num++, $rec->squestionid,
                $files, false, $data->maxwidth, $data->maxheight);
        }
        $ret['items'] = $num - 1;

        return $recs;
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
     * @param int $auserid
     */
    protected function get_avatar_default($auserid) {
        // Compute default avatar.
        $db = $this->db;

        // Uses the same avatar of a previous game.
        $sql = "SELECT g.id, g.avatarid,a.numused ".
            " FROM {$db->prefix}mmogame_aa_grades g, {$db->prefix}mmogame_aa_avatars a ".
            " WHERE g.mmogameid=? AND g.numgame<>? AND auserid=? ".
            " AND NOT EXISTS( ".
                "SELECT * FROM {$db->prefix}mmogame_aa_grades g2 WHERE g2.numgame=g.numgame AND ".
                " g2.mmogameid=g.mmogameid AND g2.avatarid=g.avatarid AND g2.id<>g.id)";
            " ORDER BY g.id DESC, a.numused, a.randomkey";
        $recs = $db->get_records_sql( $sql, [$this->rgame->id, $this->rgame->numgame, $auserid], 0, 1);
        if (count( $recs) != 0) {
            foreach ($recs as $rec) {
                $db->update_record( 'mmogame_aa_avatars',
                    ['id' => $rec->avatarid, 'numused' => $rec->numused + 1, 'randomkey' => mt_rand()]);
                return $rec->avatarid;
            }
        }

        // Ones that is no used in this numgame.
        $sql = "SELECT a.id, numused FROM {$db->prefix}mmogame_aa_avatars a ".
            " LEFT JOIN {$db->prefix}mmogame_aa_grades g ON g.avatarid=a.id AND g.mmogameid=? AND g.numgame=?".
            " WHERE g.id IS NULL ".
            " ORDER BY a.numused,a.randomkey";
        $recs = $db->get_records_sql( $sql, [$this->rgame->id, $this->rgame->numgame], 0, 1);
        if (count( $recs) == 0) {
            // All avatar are used in this numgame (players > avatars).
            $sql = "SELECT id, numuser FROM {$db->prefix}mmogame_aa_avatars ORDER BY numused, randomkey";
            $recs = $db->get_records_sql( $sql, 0, 1);
        }
        if (count( $recs) != 0) {
            foreach ($recs as $rec) {
                $avatarid = $rec->id;
                $db->update_record( 'mmogame_aa_avatars',
                    ['id' => $avatarid, 'numused' => $rec->numused + 1, 'randomkey' => mt_rand()]);
                break;
            }
        }

        return $avatarid;
    }

    /**
     * Returns the grade for user auserid
     *
     * @param int $auserid
     */
    public function get_grade($auserid) {

        $db = $this->db;

        $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND auserid=?',
            [$this->rgame->id, $this->rgame->numgame, $auserid]);
        if ($rec != false) {
            return $rec;
        }

        $avatarid = 0;
        $usercode = 0;
        $colorpaletteid = null;
        $nickname = '';
        $recuser = $db->get_record_select( 'mmogame_aa_users', 'id=?', [$auserid]);
        if ($recuser != false) {
            if ($recuser->kind == 'usercode') {
                $reccode = $db->get_record_select( 'mmogame_aa_users_code', 'id=?', [$recuser->instanceid]);
                if ($reccode != false && $reccode->code != 0) {
                    $usercode = $reccode->code;
                }
            }
        }

        $grades = $db->get_records_select( 'mmogame_aa_grades', 'mmogameid=? AND auserid=? AND numgame < ?',
            [$this->rgame->id, $auserid, $this->rgame->numgame], 'numgame DESC', '*', 0, 1);
        foreach ($grades as $grade) {
            $colorpaletteid = $grade->colorpaletteid;
            $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND avatarid=?',
                [$instance->id, $this->rgame->numgame, $grade->avatarid]);
            if ($rec === false) {
                $avatarid = $grade->avatarid;
            }
            $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND nickname=?',
                [$this->rgame->id, $this->rgame->numgame, $grade->nickname]);
            if ($rec === false) {
                $nickname = $grade->nickname;
            }
            if ($usercode == 0) {
                $rec = $db->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND usercode=?',
                    [$this->rgame->id, $this->rgame->numgame, $grade->usercode]);
                if ($rec === false) {
                    $usercode = $grade->usercode;
                }
            }

            break;
        }

        if ($avatarid == 0) {
            $avatarid = $this->get_avatar_default( $auserid);
        }
        if ($colorpaletteid == null || $colorpaletteid == 0) {
            $rec = $this->db->get_record_select( 'mmogame_aa_colorpalettes',
                'category="Game Design" AND name="Valheim / UI Redesign"', null, 'id as minid');
            if ($rec === false) {
                $rec = $this->db->get_record_select( 'mmogame_aa_colorpalettes', '', null, 'min(id) as minid');
            }
            $colorpaletteid = $rec->minid;
        }

        if ($usercode == 0) {
            $count = $db->count_records_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=?',
                [$this->rgame->id, $this->rgame->numgame]);
            $max = 10 + 10 * $count;
            $n = 0;
            for (;;) {
                $usercode = mt_rand( $max / 10, $max);
                $rec = $this->get_db()->get_record_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND auserid=?',
                    [$this->rgame->id, $this->rgame->numgame, $auserid]);
                if ($rec == false) {
                    break;
                }
                $max *= 10;
                if (++$n > 7) {
                    break;
                }
            }
        }

        $a = ['mmogameid' => $this->rgame->id, 'numgame' => $this->rgame->numgame, 'auserid' => $auserid,
            'avatarid' => $avatarid, 'usercode' => $usercode, 'nickname' => $nickname, 'timemodified' => time(),
            'colorpaletteid' => $colorpaletteid, 'sumscore' => 0,
            ];
        $id = $db->insert_record( 'mmogame_aa_grades', $a);

        return $db->get_record_select( 'mmogame_aa_grades', 'id=?', [$id]);
    }

    /**
     * Returns info about avatar for the user auserid.
     *
     * @param int $auserid
     */
    public function get_avatar_info($auserid) {
        $sql = "SELECT g.*, a.directory, a.filename, a.id as aid, c.color1, c.color2, c.color3, c.color4, c.color5".
            " FROM {$this->db->prefix}mmogame_aa_grades g LEFT JOIN {$this->db->prefix}mmogame_aa_avatars a ON g.avatarid=a.id".
            " LEFT JOIN {$this->db->prefix}mmogame_aa_colorpalettes c ON c.id=g.colorpaletteid ".
            " WHERE g.mmogameid=? AND g.numgame=? AND g.auserid=?";
        $grade = $this->db->get_record_sql( $sql, [$this->rgame->id, $this->rgame->numgame, $auserid]);
        if ($grade === false) {
            $grade = $this->get_grade( $auserid);
            if ($grade === false) {
                return false;
            }
            $grade = $this->db->get_record_sql( $sql, [$this->rgame->id, $this->rgame->numgame, $auserid]);
        }

        if ($grade->aid == null) {
            $this->db->update_record( 'mmogame_aa_grades',
                ['id' => $grade->id, 'avatarid' => $this->get_avatar_default( $auserid)]);
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
     */
    public function get_rank_alone($auserid, $field) {
        $grade = $this->get_grade( $auserid);

        $value = $grade->$field;
        if ($value == null) {
            $value = 0;
        }

        return $this->db->count_records_select( 'mmogame_aa_grades', 'mmogameid=? AND numgame=? AND '.$field.' > ?',
            [$this->rgame->id, $this->rgame->numgame, $value]) + 1;
    }

    /**
     * Returns IP address of the client.
     *
     * @return string
     */
    public static function get_ip() {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Returns the available avatars for user auserid.
     *
     * @param int $auserid
     */
    public function get_avatars($auserid) {
        $info = $this->get_avatar_info( $auserid);

        $where = 'ishidden = 0 AND '.
            "id NOT IN (SELECT avatarid ".
            "FROM {$this->db->prefix}mmogame_aa_grades WHERE mmogameid=? AND numgame=? AND auserid<>?)";
        $grades = $this->db->get_records_select( 'mmogame_aa_avatars', $where,
            [$this->rgame->id, $info->numgame, $info->auserid]);
        $ret = [];
        foreach ($grades as $grade) {
            $ret[$grade->id] = $grade->directory.'/'.$grade->filename;
        }

        return $ret;
    }

    /**
     * Set the nickname and avatar for the user auserid.
     *
     * @param int $auserid
     * @param string $nickname
     * @param int $avatarid
     */
    public function set_avatar($auserid, $nickname, $avatarid) {
        $info = $this->get_avatar_info( $auserid);

        $a = [];
        if ($avatarid > 0) {
            $rec = $this->db->get_record_select( 'mmogame_aa_avatars', 'id=?', [$avatarid]);
            if ($rec !== false) {
                $rec = $this->db->get_record_select( 'mmogame_aa_grades',
                    'mmogameid=? AND numgame=? AND avatarid=? AND auserid<>?',
                    [$this->rgame->id, $this->rgame->numgame, $avatarid, $auserid]);
                if ($rec === false) {
                    $a['avatarid'] = $avatarid;
                }
            }
        }

        $count = $this->db->count_records_select( 'mmogame_aa_grades',
            'mmogameid=? AND numgame=? AND nickname=? AND auserid <> ?',
            [$this->rgame->id, $this->rgame->numgame, $nickname, $auserid]);
        if ($count == 0) {
            $a['nickname'] = $nickname;
        }

        if (count( $a) > 0) {
            $a['id'] = $info->id;
            $this->db->update_record( 'mmogame_aa_grades', $a);
        }
    }

    /**
     * Returns the available color palettes for the user auserid.
     *
     * @param int $auserid
     * @return array with id in key and 5 colors at value
     */
    public function get_palettes($auserid) {
        $info = $this->get_avatar_info( $auserid);

        $recs = $this->db->get_records_select( 'mmogame_aa_colorpalettes', '', null, 'hue');
        $ret = [];
        foreach ($recs as $rec) {
            $ret[$rec->id] = [$rec->colorsort1, $rec->colorsort2, $rec->colorsort3, $rec->colorsort4, $rec->colorsort5];
        }

        return $ret;
    }

    /**
     * Set the colorpaletterid for the user auserid.
     *
     * @param int $auserid
     * @param int $colorpaletteid
     */
    public function set_colorpalette($auserid, $colorpaletteid) {
        $info = $this->get_avatar_info( $auserid);
        $this->db->update_record( 'mmogame_aa_grades', ['id' => $info->id, 'colorpaletteid' => $colorpaletteid]);
    }

    /**
     * Writes filecontents in the state file.
     *
     * @param int $state
     * @param string $filecontents
     */
    public function save_state_file($state, $filecontents) {
        global $CFG;

        // Creates a random upload directory in temp.
        $newdir = $CFG->dataroot."/temp/mmogame";
        if (!file_exists( $newdir)) {
            mkdir( $newdir);
        }
        $newdir .= '/states';
        if (!file_exists( $newdir)) {
            mkdir( $newdir);
        }
        $file = $this->rgame->fastjson === null ? 0 : $this->rgame->fastjson;
        $newdir .= '/'.substr( $file, -2);
        if (!file_exists( $newdir)) {
            mkdir( $newdir);
        }

        if ($filecontents != false) {
            $file = "$newdir/$file-$state.txt";
            if (!file_exists( $file) || file_get_contents( $file) != $filecontents) {
                file_put_contents( $file, $filecontents);
            }
        }

        $file = "{$newdir}/{$this->rgame->fastjson}.txt";
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
    public function save_state($state, $statecontents, $filecontents, $timefastjson) {

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
     * Sorts 5 colors in order of hue and returns the smaller hue.
     *
     * @param int $color1
     * @param int $color2
     * @param int $color3
     * @param int $color4
     * @param int $color5
     * @param int $colorsort1
     * @param int $colorsort2
     * @param int $colorsort3
     * @param int $colorsort4
     * @param int $colorsort5
     * @return float the smaller hue
     */
    public static function compute_hue($color1, $color2, $color3, $color4, $color5, &$colorsort1, &$colorsort2,
        &$colorsort3, &$colorsort4, &$colorsort5) {

        $colors = [$color1, $color2, $color3, $color4, $color5];
        usort( $colors, "mmogame::usort_mmogame_palettes_contrast");
        $colorsort1 = $colors[0];
        $colorsort2 = $colors[1];
        $colorsort3 = $colors[2];
        $colorsort4 = $colors[3];
        $colorsort5 = $colors[4];

        return self::calcualtehue( $colorsort1);
    }


    /**
     * Compare the contrast of $a and $b and returns -1,0 or 1.
     *
     * @param int $a
     * @param int $b
     * @return int (the result of comparison)
     */
    public static function usort_mmogame_palettes_contrast($a, $b) {
        return self::get_contrast( $a) <=> self::get_contrast( $b);
    }

    /**
     * Returns the contract of a color.
     *
     * @param int $color
     * @return int (the contrast)
     */
    public static function get_contrast($color) {
        $red    = ( $color >> 16 ) & 0xFF;    // Red is the Left Most Byte.
        $green  = ( $color >> 8 ) & 0xFF;     // Green is the Middle Byte.
        $blue   = $color & 0xFF;

        return (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;
    }

    /**
     * Returns the hue of a color
     *
     * @param string $color
     * @return double
     */
    public static function calcualtehue($color) {
        $red    = ( $color >> 16 ) & 0xFF;    // Red is the Left Most Byte.
        $green  = ( $color >> 8 ) & 0xFF;     // Green is the Middle Byte.
        $blue   = $color & 0xFF;

        $min = min($red, $green, $blue);
        $max = max($red, $green, $blue);

        switch ($max) {
            case 0:
                // If the max value is 0.
                $hue = 0;
                break;
            case $min:
                // If the maximum and minimum values are the same.
                $hue = 0;
                break;
            default:
                $delta = $max - $min;
                if ($red == $max) {
                    $hue = 0 + ($green - $blue) / $delta;
                } else if ($green == $max) {
                    $hue = 2 + ($blue - $red) / $delta;
                } else {
                    $hue = 4 + ($red - $green) / $delta;
                }
                $hue *= 60;
                if ($hue < 0) {
                    $hue += 360;
                }
        }
        return $hue;
    }

    /**
     * Update state in database.
     *
     * @param int $state
     */
    public function update_state($state) {
        $this->rstate->state = $state;
        $this->db->update_record( 'mmogame_aa_states', ['id' => $this->rstate->id, 'state' => $state]);
    }

    /**
     * If nickname is empty creates a new one based on the $filename.
     *
     * @param string $nickname
     * @param string $filename
     * @return string (the repaired nickname)
     */
    public static function repair_nickname($nickname, $filename) {
        if ($nickname != '' && $nickname != null) {
            return $nickname;
        }

        $pos = strrpos( $filename, '.');

        return $pos != false ? substr( $filename, 0, $pos) : $filename;
    }

    /**
     * Returns a new unique pin of mmogame with id=$mmogameid
     *
     * @param int $mmogameid
     * @param object $db
     * @param int $digits (number of digits for new pin)
     * @return int (the new pin)
     */
    public static function get_newpin($mmogameid, $db, $digits) {
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
}
