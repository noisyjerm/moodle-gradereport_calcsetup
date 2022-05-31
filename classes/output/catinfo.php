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
 * Outputs summary of current grade category.
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup\output;


use renderable;
use templatable;
use renderer_base;

/**
 * Class catinfo
 * @package gradereport_calcsetup\output
 */
class catinfo implements renderable, templatable {

    /** * @var  */
    private $data;

    /** @var */
    private $courseid;

    /** @var */
    private $catid;

    /**
     * catinfo constructor.
     * @param \gradereport_calcsetup\gradecategory $gradecategory
     */
    public function __construct($gradecategory) {
        $this->data = $gradecategory->get_item();
        $this->data->rulename = $gradecategory->get_rule()->get_idnumber();
        $this->courseid = $gradecategory->get_courseid();
        $this->catid = $gradecategory->get_catid();
    }

    /**
     * @param renderer_base $output
     * @return array|mixed|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $this->data->categories = $this->get_catselector();
        $this->data->display = $this->get_displaytypename($this->data->display);
        $this->data->rules = $this->get_rules();

        $url = new \moodle_url('/grade/report/calcsetup/index.php', ['id' => $this->courseid, 'catid' => $this->catid]);
        $this->data->actionurl = $url->out(false);
        $this->data->sesskey = sesskey();

        return $this->data;
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    private function get_catselector() {
        global $DB;
        $sql = 'SELECT gi.*, gc.fullname, gc.depth, c.fullname coursename FROM {grade_items} gi
                LEFT JOIN {grade_categories} gc ON gc.id = gi.iteminstance
                JOIN {course} c ON c.id = gi.courseid
                WHERE gi.courseid = ?
                AND itemtype IN (?, ?)
                AND gi.gradetype > 0
                AND gi.grademax > 0
                ORDER BY gi.sortorder';
        $categories = $DB->get_records_sql($sql, [$this->courseid, 'category', 'course']);
        foreach ($categories as $category) {
            if ($category->itemtype === 'course') {
                $category->fullname = $category->coursename;
            }
            if ($category->id === $this->data->id) {
                $category->selected = true;
            }
            $category->indent = str_repeat('&nbsp;&nbsp;', $category->depth);
        }
        return array_values($categories);
    }

    /**
     * @return array
     * @throws \dml_exception
     */
    private function get_rules() {
        global $DB;
        $rules = $DB->get_records('gradereport_calcsetup_rules');

        foreach ($rules as $rule) {
            if ($rule->idnumber === $this->data->rulename) {
                $rule->selected = true;
            }
        }

        return array_values($rules);
    }

    /**
     * @param $display
     * @return \lang_string|string
     * @throws \coding_exception
     */
    private function get_displaytypename($display) {
        switch($display) {
            case GRADE_DISPLAY_TYPE_REAL:
                return get_string('real', 'grades');
            case GRADE_DISPLAY_TYPE_REAL_PERCENTAGE:
                return get_string('realpercentage', 'grades');
            case GRADE_DISPLAY_TYPE_REAL_LETTER:
                return get_string('realletter', 'grades');
            case GRADE_DISPLAY_TYPE_PERCENTAGE:
                return get_string('percentage', 'grades');
            case GRADE_DISPLAY_TYPE_PERCENTAGE_REAL:
                return get_string('percentagereal', 'grades');
            case GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER:
                return get_string('percentageletter', 'grades');
            case GRADE_DISPLAY_TYPE_LETTER    :
                return get_string('letter', 'grades');
            case GRADE_DISPLAY_TYPE_LETTER_REAL:
                return get_string('letterreal', 'grades');
            case GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE:
                return get_string('letterpercentage', 'grades');
            default:
                // Todo: look up what the default is.
                return get_string('default', 'grades');
        }
    }
}
