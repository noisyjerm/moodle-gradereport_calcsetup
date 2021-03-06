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
 * Page for displaying calculation setup rules
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

$pageurl = new \moodle_url('/grade/report/calcsetup/managerules.php');
$PAGE->set_url($pageurl);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);

// This is the normal requirements.
require_capability('gradereport/calcsetup:view', $context);
require_capability('moodle/grade:viewall', $context);
require_capability('moodle/grade:edit', $context);

$title = get_string('managerules', 'gradereport_calcsetup');
$PAGE->set_title(get_string('pageheader', 'gradereport_calcsetup') . ' - ' . $title);
$PAGE->requires->js_call_amd('gradereport_calcsetup/managerules', 'init', [$pageurl->out()]);

$PAGE->set_pagelayout('report');
$PAGE->set_heading($title);

navigation_node::require_admin_tree();

$table = new gradereport_calcsetup\output\rulestable('rulestable');
$newrulebutton = new single_button(
    new \moodle_url('editrule.php', ['id' => 0]),
    get_string('newrule', 'gradereport_calcsetup'),
    'get'
);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$table->display();
echo $OUTPUT->render($newrulebutton);
echo $OUTPUT->footer();
