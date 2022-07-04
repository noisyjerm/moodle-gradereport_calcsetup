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
 */
class gradereport_calcsetup_validatecalc extends \external_api {

    /**
     * Validate incoming parameters
     * @return \external_function_parameters
     */
    public static function get_calculation_valid_parameters() {
        return new \external_function_parameters (
            array(
                'courseid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
                'id'       => new \external_value(PARAM_INT, 'Grade item Id', VALUE_REQUIRED),
                'formula'  => new \external_value(PARAM_RAW, 'A grade calculation formula.', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Check if the entered calculation formula can be used.
     * @param integer $courseid
     * @param integer $gradeitemid
     * @param string $formula
     * @return false[]
     */
    public static function get_calculation_valid($courseid, $gradeitemid, $formula) {
        $gradeitem = \grade_item::fetch(array('id' => $gradeitemid, 'courseid' => $courseid));
        $formula = preg_replace('/\s+/', '', $formula);
        $calculation = \calc_formula::unlocalize(stripslashes($formula));
        $result = $gradeitem->validate_formula($calculation);
        if (is_string($result)) {
            $result = false;
        }

        return ['valid' => $result];
    }

    /**
     * Describe the returned data structure.
     * @return \external_single_structure
     */
    public static function get_calculation_valid_returns() {
        return new \external_single_structure(
            array(
                'valid' => new \external_value(PARAM_BOOL, 'Calculation is possible'),
            )
        );
    }

}
