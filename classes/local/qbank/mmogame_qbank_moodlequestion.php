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

namespace mod_mmogame\local\qbank;

use mod_mmogame\local\mmogame;
use stdClass;

/**
 * The class mmogame_qbank_moodlequestion extends mmogame_qbank and has the code for accessing questions of Moodle
 *
 * @package    mmogame_qbank_moodlequestion
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mmogame_qbank_moodlequestion extends mmogame_qbank {
    /**
     * Loads data for question id.
     *
     * @param int $id
     * @param bool $loadextra
     * @param string $fields
     * @return ?stdClass
     */
    public function load(int $id, bool $loadextra = true, string $fields = ''): ?stdClass {
        if ($fields == '') {
            $fields = 'qbe.id, q.id as questionid,q.qtype,q.name,q.questiontext as definition';
        }
        $db = $this->mmogame->get_db();
        $sql = "SELECT $fields ".
            " FROM {question} q, {question_bank_entries} qbe,{question_versions} qv ".
            " WHERE qbe.id=qv.questionbankentryid AND qv.questionid=q.id AND qbe.id=? ".
            " ORDER BY qv.version DESC";
        $recs = $db->get_records_sql( $sql, [$id], 0, 1);
        if (count( $recs) == 0) {
            return null;
        }
        $ret = reset( $recs);

        if ($this->mmogame->get_rgame()->striptags) {
            $ret->definition = strip_tags( $ret->definition);
        }

        if (!$loadextra) {
            return $ret;
        }

        return $this->load2( $ret);
    }

    /**
     * Loads data from table question_answers.
     *
     * @param stdClass $query
     * @return stdClass $query
     */
    private function load2(stdClass $query): stdClass {
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
                [$query->questionid]);
            $query->multichoice = $rec;
        }

        return $query;
    }

    /**
     * Copy data from questions to $ret that is used to JSON call.
     *
     * @param mmogame $mmogame
     * @param array $ret
     * @param string $num
     * @param int $id
     * @param ?string $layout
     * @param bool $fillconcept
     * @return stdClass
     */
    public function load_json(mmogame $mmogame, array &$ret, string $num, int $id, ?string $layout, bool $fillconcept): stdClass {
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

            $answers = $answerids = [];
            foreach ($rec->answers as $info) {
                $answers[] = $info->answer;
                $answerids[] = $info->id;
            }
            $ret['answers'.$num] = $answers;
            $ret['answerids'.$num] = $answerids;
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

        [$insql, $inparams] = $this->mmogame->get_db()->get_in_or_equal( explode( ',', $ids));
        $recs = $this->mmogame->get_db()->get_records_select( 'question', $insql, $inparams, '', $fields);

        if ($loadextra) {
            foreach ($recs as $rec) {
                $this->load2($rec);
            }
        }

        return $recs;
    }

    /**
     * Check if $useranswer is the correct answer
     *
     * @param stdClass $query
     * @param ?string $useranswer
     * @param ?int $useranswerid
     * @param mmogame $mmogame
     * @param float $fraction
     *
     * @return true or false
     */
    public function is_correct(stdClass $query, ?string $useranswer, ?int $useranswerid, mmogame $mmogame, float &$fraction): bool {
        if ($query->qtype == 'shortanswer') {
            return $this->is_correct_shortanswer( $query, $useranswer, $mmogame);
        } else {
            return $this->is_correct_multichoice( $query, $useranswer, $useranswerid, $fraction);
        }
    }

    /**
     * Check if $useranswer is the correct answer (internal function for short answers)
     *
     * @param stdClass $query
     * @param string $useranswer
     * @param mmogame $mmogame
     *
     * @return true or false
     */
    protected function is_correct_shortanswer(stdClass $query, string $useranswer, mmogame $mmogame): bool {
        if ($mmogame->get_rgame()->casesensitive) {
            return $query->concept == $useranswer;
        } else {
            return strtoupper( $query->concept) == strtoupper( $useranswer);
        }
    }

    /**
     * Check if $useranswer is the correct answer (internal function for multichoice answers)
     *
     * @param stdClass $query
     * @param string|null $useranswer
     * @param ?int $useranswerid
     * @param float $fraction
     *
     * @return true or false
     */
    protected function is_correct_multichoice(stdClass $query, ?string $useranswer, ?int $useranswerid, float &$fraction): bool {
        if ($query->multichoice->single) {
            return $this->is_correct_multichoice_single1( $query, $useranswerid, $fraction);
        } else {
            return $this->is_correct_multichoice_single0( $query, $useranswer, $fraction);
        }
    }

    /**
     * Check if $useranswer is the correct answer (internal function for multichoice answers with one correct answer)
     *
     * @param stdClass $query
     * @param ?int $useranswerid
     * @param float $fraction
     *
     * @return true or false
     */
    protected function is_correct_multichoice_single1(stdClass $query, ?int $useranswerid, float &$fraction): bool {
        $fraction = null;
        foreach ($query->answers as $answer) {
            if (intval( $answer->id) == $useranswerid) {
                $fraction = $answer->fraction;
                break;
            }
        }

        return abs( $fraction - 1) < 0.0000001;
    }

    /**
     * Check if $useranswer is the correct answer (internal function for multichoice answers with many correct answers)
     *
     * @param stdClass $query
     * @param string $useranswer
     * @param float $fraction
     * @return true or false
     */
    protected function is_correct_multichoice_single0(stdClass $query, string $useranswer, float &$fraction): bool {
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
     * @param stdClass $query
     * @return ?string
     */
    public function get_layout(stdClass $query): ?string {
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
     * Check if the question is multi choice
     *
     * @param stdClass $query
     * @return true or false
     */
    public static function is_multichoice(stdClass $query): bool {
        return $query->qtype == 'multichoice';
    }

    /**
     * Check if the question is shortanswer
     *
     * @param stdClass $query
     * @return true or false
     */
    public static function is_shortanswer(stdClass $query): bool {
        return $query->qtype == 'shortanswer';
    }

    /**
     * Return the id of all questions
     *
     * @return ?array
     */
    public function get_queries_ids(): ?array {
        $rgame = $this->mmogame->get_rgame();
        $qtypes = [];
        $qtypes[] = 'shortanswer';
        $qtypes[] = 'multichoice';

        if ( count($qtypes) === 0) {
            return null;
        }
        $db = $this->mmogame->get_db();

        $categoryids = $rgame->qbankparams != '' ? $rgame->qbankparams : '0';
        [$insql1, $inparams1] = $db->get_in_or_equal( $qtypes);
        [$insql2, $inparams2] = $db->get_in_or_equal( explode( ',', $categoryids));
        $sql = "SELECT q.id,qtype,qbe.id as qbeid
            FROM {question} q,{question_bank_entries} qbe,{question_versions} qv
            WHERE qtype $insql1 AND qbe.id=qv.questionbankentryid AND qv.questionid=q.id
            AND qbe.questioncategoryid $insql2
            ORDER BY qv.version DESC";
        $recs = $db->get_records_sql( $sql, array_merge($inparams1, $inparams2));

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
