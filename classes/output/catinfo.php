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
 */
class catinfo implements renderable, templatable {

    /** * @var  */
    private $item;

    /** @var */
    private $courseid;

    /** @var */
    private $catid;

    /** @var array */
    private $fields;

    /** @var array */
    private $corefields;

    /**
     * catinfo constructor.
     * @param \gradereport_calcsetup\gradecategory $gradecategory
     */
    public function __construct($gradecategory) {
        $this->item = $gradecategory->get_item();
        $this->item->rulename = $gradecategory->get_rule()->get_idnumber();
        $this->item->ruledescription = $gradecategory->get_rule()->get_description();

        $this->courseid = $gradecategory->get_courseid();
        $this->catid = $gradecategory->get_catid();
        $this->fields = $gradecategory->get_rule()->get_displayoptions();
        $this->corefields = $gradecategory->get_rule()->get_core_fields();
    }

    /**
     * @param renderer_base $output
     * @return array|mixed|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $this->item->categories = $this->get_catselector();
        $this->item->rules = $this->get_rules();

        $url = new \moodle_url('/grade/report/calcsetup/index.php', ['id' => $this->courseid, 'catid' => $this->catid]);
        $this->item->actionurl = $url->out(false);
        $this->item->sesskey = sesskey();

        // Format the numbers.
        $decimals = $this->item->get_decimals();

        $fields = [];
        foreach ($this->fields as $field) {
            $title = $field->title;
            $property = $field->property;
            $val = isset($this->item->$property) ? $this->item->$property : '';
            if (is_object($title)) {
                $component = isset($title->component) ? $title->component : 'core';
                $field->title = get_string($title->identifier, $component);
            }

            // Overwrite and add with core definition.
            if (isset($this->corefields[$property])) {
                $corefield = $this->corefields[$property];
                foreach ($corefield as $prop => $value) {
                    $field->$prop = $value;
                }
            }

            // Format.
            if (isset($field->validation) && $field->validation === 'number') {
                $val = number_format($val, $decimals);
            }

            // Calculation.
            if ($property === 'calculation') {
                $field->property = $this->item->get_calculation();
            } else {
                $field->property = $val;
            }

            // Flag the selected option.
            if (isset($field->options)) {
                $field->hasoptions = true;
                foreach ($field->options as $option) {
                    if ($option->val == $val) {
                        $option->selected = true;
                        break;
                    }
                }
            }

            $field->editable = !empty($field->editable) && !isset($field->locked);

            $field->name = $field->editable ? $property . '_' . $this->item->id : '';
            $fields[] = $field;
        }

        $this->item->fields = $fields;

        return $this->item;
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
            if ($category->id === $this->item->id) {
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
            if ($rule->idnumber === $this->item->rulename) {
                $rule->selected = true;
            }
        }

        return array_values($rules);
    }

}
