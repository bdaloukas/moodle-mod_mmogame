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
 * This file contains the backup activity for the mmogame module
 *
 * @package    mod_mmogame
 * @copyright  2024 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mmogame/backup/moodle2/backup_mmogame_stepslib.php');

/**
 * mmogame backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_mmogame_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // The mmogame only has one structure step.
        $this->add_step(new backup_mmogame_activity_structure_step('mmogame_structure', 'mmogame.xml'));

        // Process all the annotated questions to calculate the question
        // categories needing to be included in backup for this activity
        // plus the categories belonging to the activity context itself.
        $this->add_step( new mmogame_backup_calculate_question_categories('activity_question_categories'));

        // Clean backup_temp_ids table from questions. We already
        // have used them to detect question_categories and aren't
        // needed anymore.
        $this->add_step(new backup_delete_temp_questions('clean_temp_questions'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param object $content
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of choices.
        $search = "/(".$base."\/mod\/mmogame\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MMOGAMEINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = "/(".$base."\/mod\/mmogame\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MMOGAMEVIEWBYID*$2@$', $content);

        return $content;
    }
}

/**
 * mmogame backup class that computes all question categories that are used in the game
 */
class mmogame_backup_calculate_question_categories extends backup_calculate_question_categories {
    /**
     * Define execution
     */
    protected function define_execution() {
        static::calculate_question_categories($this->get_backupid(), $this->task->get_contextid());
    }

    /**
     * Calculates question categories based on table mmogame_aa_stats
     *
     * @param int $backupid
     * @param int $contextid
     */
    protected static function calculate_question_categories($backupid, $contextid) {
        global $DB;

        $context = $DB->get_record_select( 'context', 'id=?', [$contextid]);
        $cm = $DB->get_record_select( 'course_modules', 'id=?', [$context->instanceid]);

        $sql = "SELECT g.*, m.name as modulename
          FROM {context} c, {course_modules} cm, {mmogame} g, {modules} m
          WHERE c.id=? AND cm.id=c.instanceid AND g.id=cm.instance AND cm.module=m.id AND m.name=?";
        $game = $DB->get_record_sql( $sql, [$contextid, 'mmogame']);
        if ($game === false) {
            return;
        }

        $sql = "SELECT DISTINCT qbe.questioncategoryid as id
            FROM {mmogame_aa_stats} stats,{question_versions} qv, {question_bank_entries} qbe
            WHERE stats.mmogameid=?
            AND qv.questionid=stats.queryid
            AND qbe.id=qv.questionbankentryid AND qbe.id=qv.questionbankentryid";
        $recs = $DB->get_records_sql( $sql, [$cm->instance]);
        $ids = [];
        foreach ($recs as $rec) {
            $ids[$rec->id] = $rec->id;
        }

        if ($game->qbank == 'moodlequestion') {
            $a = explode( ',', $game->qbankparams);
            foreach ($a as $id) {
                $id = intval( $id);
                $ids[$id] = $id;
            }
        }

        foreach ($ids as $id) {
            $rec = new stdClass();
            $rec->backupid = $backupid;
            $rec->itemname = 'question_category';
            $rec->itemid = $id;
            $DB->insert_record( 'backup_ids_temp', $rec);
        }
    }
}
