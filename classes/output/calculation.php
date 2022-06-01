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
 * Outputs calculation string
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup\output;
defined('MOODLE_INTERNAL') || die();

use gradereport_calcsetup\gradecategory;

require_once($CFG->dirroot. "/grade/report/calcsetup/constants.php");

/**
 * Class calculation
 * @package gradereport_calcsetup\output
 */
class calculation {
    /** @var array */
    private $items;

    /** @var \grade_item */
    private $item;

    /** @var \stdClass */
    private $rule;

    /** @var int */
    private $catid;

    /**
     * calculation constructor.
     * @param \gradereport_calcsetup\gradecategory $gradecategory
     */
    public function __construct($gradecategory) {
        $this->items = $gradecategory->get_gradeitems();
        $this->rule = $gradecategory->get_rule();
        $this->item = $gradecategory->get_item();
        $this->catid = $gradecategory->get_catid();
    }

    /**
     * Outputs the calculation string.
     */
    public function format_calc_string() {
        // Prepare the data.
        $data = (array)$this->item;
        $data['items'] = array_values($this->items);
        if ($last = end($this->items)) {
            $last->last = true;
        }

        // Group the items.
        $itemgroups = [];
        foreach ($this->items as $item) {
            $iteminfo = \gradereport_calcsetup\gradecategory::extract_iteminfo($item);
            if (isset($iteminfo->itemgroup)) {
                $itemgroups[$iteminfo->itemgroup][] = $item;
            }
        }
        foreach ($itemgroups as $group) {
            if ($last = end($group)) {
                $last->last = true;
            }
        }

        $data = array_merge($data, $itemgroups);
        $template = $this->rule->get_calc();
        $mustache = new \core\output\mustache_engine(array());

        return $mustache->render($template, $data);
    }

    /**
     * Outputs the calculation string.
     */
    public function display() {
        $oldcalc = $this->item->get_calculation();
        $newcalc = $this->format_calc_string();
        $newcalclean = preg_replace('/\s+/', '', $newcalc);

        $params = [
            'id' => "gradeitemsform",
            'method' => "post",
            'action' => new \moodle_url(
                '/grade/report/calcsetup/index.php', ['id' => $this->item->courseid, 'catid' => $this->catid]
            ),
        ];
        echo \html_writer::start_tag('form', $params);
            echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'calc']);
            echo \html_writer::empty_tag('input', ['value' => $newcalclean, 'type' => 'hidden', 'name' => 'newcalc']);
            echo \html_writer::start_div();
                echo \html_writer::tag('h5', get_string('current', 'gradereport_calcsetup'));
                echo \html_writer::span($oldcalc);
                echo \html_writer::tag('h5', get_string('new'));
                echo \html_writer::tag('span', $newcalc, ['id' => 'newcalc']);
            echo \html_writer::end_div();
            echo \html_writer::empty_tag('input', [
                'type' => 'submit',
                'value' => get_string('save'),
                'class' => 'btn btn-primary'
            ]);
        echo \html_writer::end_tag('form');
    }
}
