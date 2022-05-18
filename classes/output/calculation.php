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
use renderer_base;
use renderable;
use templatable;
use stdClass;

require_once($CFG->dirroot. "/grade/report/calcsetup/constants.php");

class calculation {
    public function __construct($gradecategory) {
        $this->data = $gradecategory->get_data();
        $this->rule = $gradecategory->get_rule();
        $this->item = $gradecategory->get_item();
    }

    // Todo: maybe refactor this to avoid confuse with the real export for template.
    public function export_for_template(renderer_base $output) {

        $data = (array) $this->item;
        // Todo: implement the grouping.
        $groupmod = [];
        $groupcat = [];

        foreach ($this->data as $item) {
            if ($item->itemtype === 'category') {
                $groupcat[] = $item;
            }
            if ($item->itemtype === 'mod') {
                $groupmod[] = $item;
            }
        }

        if ($last = end($groupcat)) {
            $last->last = true;
        }

        $data['group_mod'] = $groupmod;
        $data['group_cat'] = $groupcat;

        $data['items'] = $this->data;
        if ($last = end($this->data)) {
            $last->last = true;
        }

        $template = $this->rule->calc;

        $mustache = new \core\output\mustache_engine(array());

        // Todo: return not echo.
        echo $mustache->render($template, $data);
    }

}
