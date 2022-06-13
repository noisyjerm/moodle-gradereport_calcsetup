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
 * Table for displaying calculation setup rules
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup\output;

use core\files\type\icon;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/grade/constants.php');


/**
 * Table for displaying grade item calculation data
 */
class rulestable extends \flexible_table implements \renderable {

    private $pluginname = 'gradereport_calcsetup';

    /**
     * rulestable constructor.
     * @param $uniqueid
     * @throws \coding_exception
     */
    public function __construct($uniqueid) {
        global $CFG;
        parent::__construct($uniqueid);

        $this->baseurl = new \moodle_url("$CFG->wwwroot/admin/settings.php");
        $this->set_attribute('class', 'generaltable itemsettings');

        $this->define_columns([
                'name' => 'name',
                'idnumber' => 'idnumber',
                'descr' => 'descr',
                'actions' => 'actions',
            ]
        );
        $this->define_headers([
            0 => get_string('rule', $this->pluginname),
            1 => get_string('idnumber'),
            2 => get_string('description'),
            3 => get_string('actions')
        ]);
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
        $this->is_downloadable(false);
    }

    /**
     * Displays the table with the given set of templates
     * @param array $templates
     */
    public function display() {
        global $DB, $OUTPUT;

        $rules = $DB->get_records('gradereport_calcsetup_rules', null, '', 'name, idnumber, descr');

        $this->setup();

        foreach ($rules as $result) {
            $result->actions = $this->get_actions_col($result);
            $this->add_data_keyed($result);
        }

        $this->finish_output();
    }

    /**
     * @param $record
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function get_actions_col($record) {
        global $OUTPUT;
        $url = new \moodle_url('managerules.php', array('hide' => 1, 'sesskey' => sesskey()));
        $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/show', get_string('hide')));
        $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/edit', get_string('edit')));

        return implode(' ', $buttons);
    }

}
