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
 * External services to update calculation setup rules
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup\external;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/grade/grade_item.php");
require_once("$CFG->libdir/mathslib.php");

/**
 * Class gradereport_calcsetup_updatecalc
 * @package gradereport_calcsetup\external
 */
class gradereport_calcsetup_rules extends \external_api {

    /**
     * @return \external_function_parameters
     */
    public static function get_rules_parameters() {
        return new \external_function_parameters (
            array(
                'id'       => new \external_value(PARAM_INT, 'Rule Id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * @param $courseid
     * @param $gradeitemid
     * @param $formula
     * @return false[]
     */
    public static function get_rules($ruleid) {
        global $DB;
        // Get rules.
        $rules = $DB->get_records('gradereport_calcsetup_rules', ['visible' => 1], '', 'id,idnumber,name,descr' );

        foreach ($rules as $rule) {
            $rule->selected = $rule->id == $ruleid ? true : false;
        }

        // Prepend 'No rule'.
        $norule = (object) [
            'id' => 0,
            'idnumber' => 'norule',
            'name' => get_string('norule', 'gradereport_calcsetup'),
            'descr' => '',
            'selected' => is_null($ruleid)
        ];
        array_unshift($rules, $norule);

        return ['rules' => $rules];
    }

    /**
     * @return \external_single_structure
     */
    public static function get_rules_returns() {
        $rules = new \external_single_structure(array(
                'id' => new \external_value(PARAM_INT, 'DB Id of the rule', true, 0),
                'idnumber' => new \external_value(PARAM_RAW, 'Identifier of the rule', true, 0),
                'name' => new \external_value(PARAM_RAW, 'Name'),
                'descr' => new \external_value(PARAM_RAW, 'Description'),
                'selected' => new \external_value(PARAM_BOOL, 'Is this the rule applied to this category'),
        ));
        return new \external_single_structure(['rules' => new \external_multiple_structure($rules), 'list of rules']);

    }



    /**
     * @return \external_function_parameters
     */
    public static function hide_rule_parameters() {
        return new \external_function_parameters (
            array(
                'id'       => new \external_value(PARAM_INT, 'Rule Id', VALUE_REQUIRED),
                'action'   => new \external_value(PARAM_INT, 'What action to perform', VALUE_REQUIRED)
            )
        );
    }

    /**
     * @param int $id The database table id of the rule
     * @param int $action Are we hiding (0) or showing (1) this rule
     * @return bool[]
     * @throws \dml_exception
     */
    public static function hide_rule($id, $action) {
        global $DB;
        $rule = $DB->get_record('gradereport_calcsetup_rules', ['id' => $id]);
        $rule->visible = $action;
        $DB->set_field('gradereport_calcsetup_rules', 'visible', $action, ['id' => $id] );
        $wasset = $DB->get_field('gradereport_calcsetup_rules', 'visible', ['id' => $id]);
        $success = $wasset !== $action;
        return ['success' => $success];
    }

    /**
     * @return \external_single_structure
     */
    public static function hide_rule_returns() {
        return new \external_single_structure(array(
            'success' => new \external_value(PARAM_BOOL, 'Was the edit successful')
        ));
    }



    /**
     * @return \external_function_parameters
     */
    public static function delete_rule_parameters() {
        return new \external_function_parameters (
            array(
                'id' => new \external_value(PARAM_INT, 'Rule Id', VALUE_REQUIRED)
            )
        );
    }

    /**
     * @param int $id The database table id of the rule
     * @return bool[]
     * @throws \dml_exception
     */
    public static function delete_rule($id) {
        global $DB;
        $DB->delete_records('gradereport_calcsetup_rules', ['id' => $id]);
        $success = !$DB->record_exists('gradereport_calcsetup_rules', ['id' => $id]);
        return ['success' => $success];
    }

    /**
     * @return \external_single_structure
     */
    public static function delete_rule_returns() {
        return new \external_single_structure(array(
            'success' => new \external_value(PARAM_BOOL, 'Was the deletion successful')
        ));
    }


    /**
     * @return \external_function_parameters
     */
    public static function get_coreitemfields_parameters() {
        return new \external_function_parameters (
            array(
                'editableonly' => new \external_value(PARAM_BOOL, 'Rule Id', VALUE_OPTIONAL),
            )
        );
    }

    /**
     * @return array
     */
    public static function get_coreitemfields($editableonly) {
        $fields = [
             ['property' => 'id', 'locked' => true],
             ['property' => 'courseid', 'locked' => true],
             ['property' => 'categoryid', 'locked' => true],
             ['property' => 'itemname', 'locked' => true],
             ['property' => 'itemtype', 'locked' => true],
             ['property' => 'itemmodule', 'locked' => true],
             ['property' => 'iteminstance', 'locked' => true],
             ['property' => 'itemnumber', 'locked' => true],
             ['property' => 'iteminfo'],
             ['property' => 'idnumber'],
             ['property' => 'calculation'],
             ['property' => 'gradetype', 'locked' => true],
             ['property' => 'grademax', 'validation' => 'number'],
             ['property' => 'grademin', 'validation' => 'number'],
             ['property' => 'scaleid', 'validation' => 'number'],
             ['property' => 'outcomeid', 'validation' => 'number'],
             ['property' => 'gradepass', 'validation' => 'number'],
             ['property' => 'multfactor', 'validation' => 'number'],
             ['property' => 'plusfactor', 'validation' => 'number'],
             ['property' => 'aggregationcoef', 'validation' => 'number'],
             ['property' => 'aggregationcoef2', 'validation' => 'number'],
             ['property' => 'sortorder', 'locked' => true],
             ['property' => 'display'],
             ['property' => 'decimals', 'validation' => 'number'],
             ['property' => 'hidden', 'validation' => 'number'],
             ['property' => 'locked', 'locked' => true],
             ['property' => 'locktime', 'locked' => true],
             ['property' => 'needsupdate'],
             ['property' => 'weightoverride', 'validation' => 'number'],
             ['property' => 'timecreated', 'locked' => true],
             ['property' => 'timemodified', 'locked' => true],
             ['property' => 'itemgroup']
        ];

        if ($editableonly) {
            $i = 0;
            foreach ($fields as $key => $field) {
                if ($field['locked']) {
                    array_splice($fields, $i, 1);
                } else {
                    $i ++;
                }
            }
        }

        return ['fields' => $fields];
    }


    /**
     * @return \external_single_structure
     */
    public static function get_coreitemfields_returns() {
        $fields = new \external_single_structure([
            'locked' => new \external_value(PARAM_BOOL, 'Can this field be edited', VALUE_OPTIONAL),
            'property' => new \external_value(PARAM_ALPHANUMEXT, 'The name of this property')
        ]);

        return new \external_single_structure(['fields' => new \external_multiple_structure($fields), 'list of fields']);
    }

}
