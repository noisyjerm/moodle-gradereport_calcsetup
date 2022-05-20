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

    /** @var \stdClass */
    private $item;

    /** @var \stdClass */
    private $rule;

    /**
     * calculation constructor.
     * @param \gradereport_calcsetup\gradecategory $gradecategory
     */
    public function __construct($gradecategory) {
        $this->items = $gradecategory->get_data();
        $this->rule = $gradecategory->get_rule();
        $this->item = $gradecategory->get_item();
    }

    /**
     * Outputs the calculation string.
     */
    public function display() {
        // Prepare the data.
        $data = (array) $this->item;
        $data['items'] = $this->items;
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
        $template = $this->rule->calc;
        $mustache = new \core\output\mustache_engine(array());

        echo $mustache->render($template, $data);
    }

}
