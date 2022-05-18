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
 * Pre-populates some rules to get us started
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/grade/report/calcsetup/constants.php");

/**
 * Code run after the calcsetup grade report database tables have been created.
 * @return bool
 */
function xmldb_gradereport_calcsetup_install() {
    global $DB;

    // Pre-populate a couple of rules.
    $actions = [
        (object)['action' => 'weight', 'val' => 1, 'cond' => 'group-category'],
        (object)['action' => 'group', 'valtype' => VALTYPE_COL, 'val' => 'itemtype', 'cond' => 'all']
    ];
    $cols = [
        (object)['name' => 'Weighting', 'id' => 'weight', 'valtype' => VALTYPE_FREE],
        (object)['name' => 'Required', 'id' => 'req', 'valtype' => VALTYPE_COL, 'val' => 'gradepass'],
    ];

    $data = new \stdClass();
    $data->name = 'Complex Course';
    $data->idnumber = 'complexcourse';
    $data->desc = 'Does things';
    $data->calc = '=IF(AND({{#group-mod}}[[{{idnumber}}]]>={{weight}}{{^last}},{{/last}}{{/group-mod}}),
                       sum({{#group-category}}{{req}}{{^last}},{{/last}}{{/group-category}},0)';
    $data->actions = json_encode($actions);
    $data->cols = json_encode($cols);

    // Add initial data.
    $DB->insert_record('gradereport_calcsetup_rules', $data);

    return true;
}
