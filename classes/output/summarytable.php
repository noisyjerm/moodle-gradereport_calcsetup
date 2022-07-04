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
 * Table for displaying grade item calculation data
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup\output;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/grade/constants.php');



/**
 * Table for displaying grade item calculation data
 */
class summarytable extends \flexible_table implements \renderable {

    /** @var stdClass filters parameters */
    protected $filterparams;

    /** @var \moodle_url  */
    public $baseurl;

    /** @var int  */
    protected $courseid;

    /** @var array|\grade_item  */
    protected $items = [];

    /**  @var \gradereport_calcsetup\gradecategory*/
    protected $gradecategory;

    /** @var array */
    private $corefields;

    /**
     * table_report constructor.
     * @param string $uniqueid
     * @param null $filterparams
     * @throws coding_exception
     */
    public function __construct($uniqueid, $gradecategory, $courseid = 0) {
        global $CFG;
        parent::__construct($uniqueid);

        $this->courseid = $courseid;
        $this->gradecategory = $gradecategory;
        $this->items = $this->gradecategory->get_gradeitems();
        $this->corefields = $this->gradecategory->get_rule()->get_core_fields();

        $this->set_attribute('class', 'generaltable itemsettings');
        $this->baseurl = new \moodle_url("$CFG->wwwroot/grade/report/index.php");

        $columndata = $this->gradecategory->get_rule()->get_columns();

        $this->define_columns($this->get_column_ids($columndata));
        $this->define_headers($this->get_headers($columndata));
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
        $this->is_downloadable(false);
    }

    /**
     * Displays the table
     */
    public function display() {
        global $OUTPUT;
        if (empty($this->items)) {
            echo $OUTPUT->box(get_string('nodata', 'gradereport_calcsetup'),
                'generalbox boxaligncenter');
            return;
        }

        $this->setup();

        $fields = $this->gradecategory->get_rule()->get_columns();

        foreach ($this->items as $result) {
            $decimals = $result->get_decimals();
            $data = array(
                $this->get_name($result),
            );

            foreach ($fields as $field) {
                $fieldname = $field->property;

                $val = isset($result->$fieldname) ? $result->$fieldname : '';

                // Overwrite and add with core definition.
                if (isset($this->corefields[$fieldname])) {
                    $corefield = $this->corefields[$fieldname];
                    foreach ($corefield as $prop => $value) {
                        $field->$prop = $value;
                    }
                }
                // Format.
                if (isset($field->validation) && $field->validation === 'number') {
                    $val = number_format($val, $decimals);
                }

                // Calculation.
                if ($fieldname === 'calculation') {
                    $val = $result->get_calculation();
                }

                // Set properties for editing or disabling.
                $attr = [];
                if (empty($field->editable) || !empty($field->locked)) {
                    $attr['disabled'] = 'disabled';
                } else {
                    $attr['name'] = $field->editable ? 'items[' . $fieldname . '_' . $result->id . ']' : '';;
                }

                // Output textbox (input) or menu (select).
                if (isset($field->options)) {
                    $attr['class'] = 'custom-select';
                    $options = '';
                    foreach ($field->options as $option) {
                        $attrs = ['value' => $option->val];
                        if ($option->val == $val) {
                            $attrs['selected'] = 'selected';
                        }
                        $options .= \html_writer::tag('option', $option->name, $attrs);
                    }
                    $data[] = \html_writer::tag('select', $options, $attr);
                } else {
                    $attr['value'] = $val;
                    $data[] = \html_writer::empty_tag('input', $attr);
                }
            };

            $class = $result->itemtype;

            $this->add_data($data, $class);
        }
        $this->finish_output();
    }

    /**
     * Gets name of grade item. Differs depending on type is category or grade item
     * @param \grade_item $result
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function get_name($result) {
        $name = "<span>$result->itemname</span>";
        $missing = get_string('missingtotal', 'gradereport_calcsetup');
        if ($result->itemtype === 'category') {
            $link = new \moodle_url('index.php', ['id' => $this->courseid, 'catid' => $result->thiscatid] );
            $name = "<a href='$link'>$result->fullname</a>";
            $name .= $result->itemname == '' ? "<p>$missing</p>" : "<p>$result->itemname</p>";
        }
        return $name;
    }

    /**
     * Get a simple array of column names.
     * @param array $columndata
     * @return string[]
     */
    private function get_column_ids($columndata) {
        $cols = ['name'];
        foreach ($columndata as $col) {
            $cols[] = $col->property;
        }

        return $cols;
    }

    /**
     * Get tale column headers.
     * @param array $columndata
     * @return array
     * @throws \coding_exception
     */
    private function get_headers($columndata) {
        $headers = [
            get_string('gradeitem', 'core_grades')
        ];

        foreach ($columndata as $col) {
            $title = $col->title;
            if (is_object($title)) {
                $component = isset($title->component) ? $title->component : 'core';
                $title = get_string($title->identifier, $component);
            }
            $headers[] = $title;
        }

        return $headers;
    }

}
