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
 * Grade Calculation Setup Grade Report view
 *
 * @package   gradereport_calcsetup
 * @copyright 2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../../config.php');
require_once($CFG->dirroot.'/grade/lib.php');

$courseid = required_param('id', PARAM_INT);
$categoryid = optional_param('catid', null, PARAM_INT);
$rule = optional_param('rule', '', PARAM_ALPHANUM);

$pageurl = new moodle_url('/grade/report/calcsetup/index.php', array('id' => $courseid));
$PAGE->set_url($pageurl);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);


$context = context_course::instance($course->id);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pageheader', 'gradereport_calcsetup'));
$PAGE->requires->js_call_amd('gradereport_calcsetup/calcsetup', 'init', [$pageurl->out()]);

// This is the normal requirements.
require_capability('gradereport/calcsetup:view', $context);
require_capability('moodle/grade:viewall', $context);
require_capability('moodle/grade:edit', $context);

// Last selected report session tracking.
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'calcsetup';

$reportname = get_string('pluginname', 'gradereport_calcsetup');
print_grade_page_head($courseid, 'report', 'calcsetup', $reportname);

$context = context_course::instance($course->id);
require_capability('gradereport/calcsetup:view', $context);

$event = \gradereport_calcsetup\event\grade_report_viewed::create(
    array(
        'context' => $context,
        'courseid' => $courseid,
    )
);
$event->trigger();

// Get the data.
$gradecategory = new \gradereport_calcsetup\gradecategory($courseid, $categoryid, $rule);

// Save the info.
if ($rule !== '') {
    $gradecategory->get_rule()->apply();
    $event = \gradereport_calcsetup\event\grade_item_updated::create(
        array(
            'context' => $context,
            'courseid' => $courseid,
            'objectid' => $gradecategory->get_item()->id, // Todo. Make item classy.
            'other' => [
                'itemname' => $gradecategory->get_item()->fullname,
                'itemtype' => $gradecategory->get_item()->itemtype,
                'itemmodule' => null,
                'itemrule' => $gradecategory->get_rule()->get_name(),
            ],
        )
    );
    $event->trigger();
}
// Show category info.
$catinfo = new \gradereport_calcsetup\output\catinfo($gradecategory);
echo $OUTPUT->render($catinfo);
// Show the table.
$reporttable = new \gradereport_calcsetup\output\summarytable("gradebook", $gradecategory, $courseid);
$reporttable->display();
// Show the caclulation.
$calculation = new \gradereport_calcsetup\output\calculation($gradecategory);
$calculation->display();

echo $OUTPUT->footer();
