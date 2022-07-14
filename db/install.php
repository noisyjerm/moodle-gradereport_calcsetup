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

/**
 * Code run after the calcsetup grade report database tables have been created.
 * @return bool
 */
function xmldb_gradereport_calcsetup_install() {
    global $DB;

    // Pre-populate a couple of rules.
    $actions = [
        (object)['set' => 'gradetype', 'to' => '2', 'when' => "itemtype", "op" => "equals", "val" => "category"],
        (object)['set' => 'scaleid', 'to' => '2', 'when' => "gradetype", "op" => "equals", "val" => "2"],
        (object)['set' => 'gradepass', 'to' => '2', 'when' => "scaleid", "op" => "equals", "val" => "2"]
    ];
    $fields = [
        (object)[
            'title' => (object)['identifier' => 'gradepass', 'component' => 'core_grades'],
            'property' => 'gradepass',
            'editable' => true
        ]
    ];
    $cols = [
        (object)['title' => (object)['identifier' => 'idnumber'], 'property' => 'idnumber', 'editable' => true],
        (object)[
            'title' => (object)['identifier' => 'gradepass',
            'component' => 'core_grades'],
            'property' => 'gradepass',
            'editable' => true
        ],
        (object)['title' => (object)['identifier' => 'scale'], 'property' => 'scaleid', 'editable' => false]
    ];

    $data = new \stdClass();
    $data->name = 'Course achievement';
    $data->idnumber = 'achievecourse';
    $data->descr = 'This course will use the Default competence scale.

Grade items within this category (course) that are set to this scale will have a grade to pass set to 2, competent.

Note: If the category aggregation should be set to something other than "Natural".';
    $data->visible = true;
    $data->calc = '=min({{#items}}[[{{idnumber}}]]{{^last}},{{/last}}{{/items}})';
    $data->actions = json_encode($actions);
    $data->fields = json_encode($fields);
    $data->cols = json_encode($cols);

    // Add initial data.
    $DB->insert_record('gradereport_calcsetup_rules', $data);

    $actions = [
        (object)['set' => 'gradepass', 'to' => '2', 'when' => "scaleid", "op" => "equals", "val" => "2"]
    ];
    $cols = [
        (object)['title' => (object)['identifier' => 'idnumber'], 'property' => 'idnumber', 'editable' => true],
        (object)[
            'title' => (object)['identifier' => 'grademax', 'component' => 'core_grades'],
            'property' => 'grademax',
            'editable' => false
        ],
        (object)[
            'title' => (object)['identifier' => 'gradepass', 'component' => 'core_grades'],
            'property' => 'gradepass',
            'editable' => true
        ]
    ];
    $data->name = 'Category achievement';
    $data->idnumber = 'achievecategory';
    $data->descr = 'This category will use the Default competence scale.
    Grade items within this category that are set to this scale will have a grade to pass set to 2, competent.';
    $data->calc = '=min({{#items}}[[{{idnumber}}]]{{^last}},{{/last}}{{/items}})';
    $data->actions = json_encode($actions);
    $data->fields = json_encode($fields);
    $data->cols = json_encode($cols);

    $DB->insert_record('gradereport_calcsetup_rules', $data);
    return true;
}
