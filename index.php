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

$courseid = required_param('id', PARAM_INT);
$categoryid = optional_param('catid', null, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/calcsetup/index.php', array('id' => $courseid)));
$PAGE->set_pagelayout('report');

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}

require_login($course);


$context = context_course::instance($course->id);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pageheader', 'gradereport_calcsetup'));
// This is the normal requirements.
require_capability('gradereport/calcsetup:view', $context);
require_capability('moodle/grade:viewall', $context);
require_capability('moodle/grade:edit', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'gradereport_calcsetup'));


$context = context_course::instance($course->id);
require_capability('gradereport/calcsetup:view', $context);
// Get the data.
$gradecategory = new \gradereport_calcsetup\gradecategory($courseid, $categoryid);
// Show category info.
$catinfo = new \gradereport_calcsetup\output\catinfo($gradecategory);
echo $OUTPUT->render($catinfo);
// Show the table.
$reporttable = new \gradereport_calcsetup\output\summarytable("gradebook", $gradecategory, $courseid);
$reporttable->display();
// Show the caclulation.
$calculation = new \gradereport_calcsetup\output\calculation($gradecategory);
$calculation->export_for_template($OUTPUT);

echo $OUTPUT->footer();
