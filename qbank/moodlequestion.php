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
 * lib
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The class mmogameqbank_moodlequestion extends mmogameqbank and has the code for accessing questions of Moodle
 *
 * @package    mmogameqbank_moodlequestion
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogameqbank_moodlequestion extends mmogameqbank {
    /**
     * Loads data for question id.
     *
     * @param int $id
     * @param bool $loadextra
     * @param string $fields
     * @return false|mixed|object
     */
    public function load(int $id, bool $loadextra = true, string $fields = ''): mixed {
        /** @var object $params */
        $params = $this->mmogame->get_params();
        $needname = $params != false && $params->debugname;
        if ($fields == '') {
            $fields = 'qbe.id, q.id as questionid,q.qtype,q.name,q.questiontext as definition';
            if ($needname) {
                $fields .= ',q.name';
            }
        }
        $db = $this->mmogame->get_db();
        $sql = "SELECT $fields ".
            " FROM {$db->prefix}question q, {$db->prefix}question_bank_entries qbe,{$db->prefix}question_versions qv ".
            " WHERE qbe.id=qv.questionbankentryid AND qv.questionid=q.id AND qbe.id=? ".
            " ORDER BY qv.version DESC";
        $recs = $db->get_records_sql( $sql, [$id], 0, 1);
        $ret = false;
        foreach ($recs as $rec) {
            $ret = $rec;
        }

        if ($ret === false) {
            return false;
        }

        if ($this->mmogame->get_rgame()->striptags) {
            $ret->definition = strip_tags( $ret->definition);
        }
        if ($needname) {
            $ret->definition = $ret->definition.' ['.$ret->name.']';
        }

        if (!$loadextra) {
            return $ret;
        }

        return $this->load2( $ret);
    }

    /**
     * Loads data from table question_answers.
     *
     * @param object $query
     * @return object $query
     */
    private function load2(object $query): object {
        $recs = $this->mmogame->get_db()->get_records_select( 'question_answers', 'question=?',
            [$query->questionid], 'fraction DESC', 'id,answer,fraction');
        unset( $query->correctid);
        $first = true;
        $striptags = $this->mmogame->get_rgame()->striptags;

        foreach ($recs as $rec) {
            if ($striptags) {
                $rec->answer = strip_tags( $rec->answer);
            }

            if ($query->qtype == 'shortanswer') {
                $query->concept = $rec->answer;
                break;
            }
            if (!isset( $query->answers)) {
                $query->answers = [];
            }
            if (!isset( $query->correctid)) {
                $query->correctid = $rec->id;
            }
            $info = new stdClass();
            $info->id = $rec->id;
            $info->answer = $rec->answer;
            $info->fraction = $rec->fraction;
            if ($first) {
                $first = false;
                $query->concept = $rec->id;
            }
            $query->answers[] = $info;
        }
        if ($query->qtype == 'multichoice') {
            $rec = $this->mmogame->get_db()->get_record_select( 'qtype_multichoice_options', 'questionid=?',
                [$query->id]);
            $query->multichoice = $rec;
        }

        return $query;
    }

    /**
     * Copy data from questions to $ret that is used to json call.
     *
     * @param object $mmogame
     * @param array $ret
     * @param int $num
     * @param int $id
     * @param string $layout
     * @param bool $fillconcept
     * @return false|mixed|object
     */
    public function load_json(object $mmogame, array &$ret, int $num, int $id, string $layout, bool $fillconcept): mixed {
        $rec = $this->load( $id);

        $ret['qtype'.$num] = $rec->qtype;
        $definition = $rec->definition;

        $ret['definition'.$num] = $definition;
        if ($this->is_shortanswer( $rec)) {
            if ($fillconcept) {
                $ret['concept'.$num] = $rec->concept;
            }
        } else if ($this->is_multichoice( $rec)) {
            $ret['single'] = $rec->multichoice->single;
            $l = $layout == '' ? [] : explode( ',', $layout);

            for ($i = 1; $i <= count( $rec->answers); $i++) {
                if (!in_array($i, $l)) {
                    $l[] = $i;
                }
            }

            $n = 0;
            foreach ($rec->answers as $info) {
                $n++;
                $ret['answer'.$num.'_'.$n] = $info->answer;
                $ret['answerid'.$num.'_'.$n] = $info->id;
            }
            $ret['answers'.$num] = $n;
        }

        return $rec;
    }

    /**
     * Reads all records with id in $ids from databases.
     *
     * @param string $ids
     * @param bool $loadextra
     * @param string $fields
     * @return array
     */
    protected function loads(string $ids, bool $loadextra = true, string $fields='id,qtype,questiontext as definition'): array {

        $recs = $this->mmogame->get_db()->get_records_select( 'question', "id IN ($ids)", null, '', $fields);

        if ($recs !== false && $loadextra) {
            foreach ($recs as $rec) {
                $this->load2($rec);
            }
        }

        return $recs;
    }

    /**
     * Check if $useranswer is the correct answer
     *
     * @param object $query
     * @param string $useranswer
     * @param object $mmogame
     * @param float $fraction
     *
     * @return true or false
     */
    public function is_correct(object $query, string $useranswer, object $mmogame, float &$fraction): bool {
        if ($query->qtype == 'shortanswer') {
            return $this->is_correct_shortanswer( $query, $useranswer, $mmogame);
        } else {
            return $this->is_correct_multichoice( $query, $useranswer, $mmogame, $fraction);
        }
    }

    /**
     * Check if $useranswer is the correct answer (internal function for short answers)
     *
     * @param object $query
     * @param string $useranswer
     * @param object $mmogame
     *
     * @return true or false
     */
    protected function is_correct_shortanswer(object $query, string $useranswer, object $mmogame): bool {
        if ($mmogame->get_rgame()->casesensitive) {
            return $query->concept == $useranswer;
        } else {
            return strtoupper( $query->concept) == strtoupper( $useranswer);
        }
    }

    /**
     * Check if $useranswer is the correct answer (internal function for multichoice answers)
     *
     * @param object $query
     * @param string $useranswer
     * @param object $mmogame
     * @param float $fraction
     *
     * @return true or false
     */
    protected function is_correct_multichoice(object $query, string $useranswer, object $mmogame, float &$fraction): bool {
        if ($query->multichoice->single) {
            return $this->is_correct_multichoice_single1( $query, $useranswer, $mmogame, $fraction);
        } else {
            return $this->is_correct_multichoice_single0( $query, $useranswer, $fraction);
        }
    }

    /**
     * Check if $useranswer is the correct answer (internal function for multichoice answers with one correct answer)
     *
     * @param object $query
     * @param string $useranswer
     * @param object $mmogame
     * @param float $fraction
     *
     * @return true or false
     */
    protected function is_correct_multichoice_single1(object $query, string $useranswer, object $mmogame, float &$fraction): bool {
        $fraction = null;
        foreach ($query->answers as $answer) {
            if (intval( $answer->id) == intval( $useranswer)) {
                $fraction = $answer->fraction;
                break;
            }
        }

        return abs( $fraction - 1) < 0.0000001;
    }

    /**
     * Check if $useranswer is the correct answer (internal function for multichoice answers with many correct answers)
     *
     * @param object $query
     * @param string $useranswer
     * @param float $fraction
     * @return true or false
     */
    protected function is_correct_multichoice_single0(object $query, string $useranswer, float &$fraction): bool {
        $fraction = 0.0;
        $aids = explode( ',', $useranswer);
        foreach ($query->answers as $answer) {
            if (!in_array($answer->id, $aids)) {
                continue;
            }
            $fraction += $answer->fraction;
        }

        return abs( $fraction - 1) < 0.0000001;
    }

    /**
     * Return the layout (a string that is needed to put the answers in the correct order)
     *
     * @param object $query
     * @return string|null
     */
    public function get_layout(object $query): ?string {
        if ($query->qtype != 'multichoice') {
            return null;
        }
        $items = [];
        $n = count( $query->answers);
        for ($i = 1; $i <= $n; $i++) {
            $items[] = $i;
        }
        $layout = '';
        while (count( $items)) {
            $pos = random_int( 0, count( $items) - 1);
            if ($layout != '') {
                $layout .= ',';
            }
            $layout .= $items[$pos];
            array_splice( $items, $pos, 1);
        }
        return $layout;
    }

    /**
     * Check if question is multichoice
     *
     * @param object $query
     * @return true or false
     */
    public static function is_multichoice(object $query): bool {
        return $query->qtype == 'multichoice';
    }

    /**
     * Check if question is shortanswer
     *
     * @param object $query
     * @return true or false
     */
    public static function is_shortanswer(object $query): bool {
        return $query->qtype == 'shortanswer';
    }

    /**
     * Return the id of all questions
     *
     * @return array|false
     */
    public function get_queries_ids(): bool|array {
        $rgame = $this->mmogame->get_rgame();
        $qtypes = "'shortanswer'";
        $qtypes .= ($qtypes != '' ? ',' : '')."'multichoice'";

        if ( $qtypes == '') {
            return false;
        }
        $categoryids = $rgame->qbankparams != '' ? $rgame->qbankparams : '0';
        $where = "qtype IN ($qtypes)";
        $db = $this->mmogame->get_db();
        $table = "{$db->prefix}question q";

        $table .= ",{$db->prefix}question_bank_entries qbe,{$db->prefix}question_versions qv  ";
        if (!str_contains($categoryids, ',')) {
            $where2 = ' qbe.questioncategoryid='.$categoryids;
        } else {
            $where2 = ' qbe.questioncategoryid IN ('.$categoryids.')';
        }
        $where .= ' AND qbe.id=qv.questionbankentryid AND qv.questionid=q.id AND '.$where2;

        $recs = $db->get_records_sql( "SELECT q.id,qtype,qbe.id as qbeid FROM $table WHERE $where ORDER BY qv.version DESC");

         $map = [];
        foreach ($recs as $rec) {
            if (array_key_exists( $rec->qbeid, $map)) {
                continue;
            }
            $map[$rec->qbeid] = $rec->qbeid;
        }

        return $map;
    }
}
