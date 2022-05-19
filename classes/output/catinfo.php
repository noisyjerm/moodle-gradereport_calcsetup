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


use mod_h5pactivity\local\attempt as activity_attempt;
use renderable;
use templatable;
use renderer_base;
use moodle_url;
use user_picture;
use stdClass;

/**
 * Class to help display report link in mod_h5pactivity.
 *
 * @copyright 2020 Ferran Recio
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catinfo implements renderable, templatable {

    private $data;
    private $courseid;

    public function __construct($gradecategory) {
        $this->data = $gradecategory->get_item();
        $this->data->rulename = $gradecategory->get_rule()->name;
        $this->courseid = $gradecategory->get_courseid();
        $this->item = $gradecategory->get_item();
    }

    public function export_for_template(renderer_base $output) {
        $this->data->categories = $this->get_catselector();

        $this->data->display = $this->get_displaytypename($this->data->display);
        return $this->data;
    }

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
