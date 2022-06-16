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
 * Create or change a calculation setup rules
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

global $DB;

require_login();

$ruleid = optional_param('id', 0, PARAM_INT);

$pageurl = new \moodle_url('/grade/report/calcsetup/editrule.php');
$context = context_system::instance();

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

// This is the normal requirements.
require_capability('gradereport/calcsetup:view', $context);
require_capability('moodle/grade:viewall', $context);
require_capability('moodle/grade:edit', $context);

$title = $ruleid
    ? get_string('editrule', 'gradereport_calcsetup')
    : get_string('newrule', 'gradereport_calcsetup');
$PAGE->set_title(get_string('pageheader', 'gradereport_calcsetup') . ' - ' . $title);
$PAGE->set_pagelayout('report');
$PAGE->set_heading($title);

navigation_node::require_admin_tree();

$editform = new \gradereport_calcsetup\output\editrule_form(null, ['ruleid' => $ruleid]);
$returnurl = new moodle_url($CFG->wwwroot . '/grade/report/calcsetup/managerules.php');

if ($editform->is_cancelled()) {
    // Return to manage rules page.
    redirect($returnurl);
} else if ($updatedrule = $editform->get_data()) {
    if ($updatedrule->id) {
        // Update.
        $DB->update_record('gradereport_calcsetup_rules', $updatedrule);
    } else {
        // Create.
        $DB->insert_record('gradereport_calcsetup_rules', $updatedrule);
    }

    redirect($returnurl);
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);

    $editform->display();
    echo $OUTPUT->footer();
}
