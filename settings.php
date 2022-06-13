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
 * Settings for Grade calculation tool
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
if ($hassiteconfig) {
    $ADMIN->add(
        'gradereports',
        new admin_externalpage(
            'managecalcrules',
            'Grade calculation tool - Manage rules',
            "$CFG->wwwroot/grade/report/calcsetup/managerules.php",
            'moodle/site:config'
        )
    );

    $manageurl = new \moodle_url('/grade/report/calcsetup/managerules.php');
    $settings->add(new admin_setting_heading(
        'about',
        get_string('about', 'gradereport_calcsetup'),
        get_string('plugindescription', 'gradereport_calcsetup', $manageurl->out())
    ));
}
