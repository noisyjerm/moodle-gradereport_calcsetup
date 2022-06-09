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
 * External services to update grade item properties.
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
        // get rules
        $rules = $DB->get_records('gradereport_calcsetup_rules', [], '', 'id,idnumber,name,descr' );

        foreach ($rules as $rule) {
           $rule->selected = $rule->id == $ruleid ? true : false;
        }

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

}
