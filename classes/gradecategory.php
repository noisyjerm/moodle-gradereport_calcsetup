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
 * Prepares data for grade category
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. "/grade/report/calcsetup/constants.php");

class gradecategory {
    /** @var int */
    private $courseid;

    /** @var \stdClass */
    private $item;

    /** @var \stdClass */
    private $iteminfo;

    /** @var \stdClass */
    private $rule;

    /** @var \stdClass */
    private $data;

    /**
     * gradecategory constructor.
     * @param int $courseid
     * @param int $categoryid
     * @param string $rulename
     */
    public function __construct($courseid = 0, $categoryid = 0, $rulename = '') {
        $this->courseid = $courseid;
        $this->data = $this->get_rawdata($courseid, $categoryid);
        // Todo: make item a class.
        $this->item = $this->set_item(array_shift($this->data));

        // Extract the rule.
        $this->iteminfo = self::extract_iteminfo($this->item);
        $this->rule = new \gradereport_calcsetup\rule($rulename, $this->data, $this->item);

        // Add the custom data.
        $this->add_customdata($this->data);
    }

    /**
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * @return array|\stdClass
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * @return mixed|\stdClass|null
     */
    public function get_iteminfo() {
        return $this->iteminfo;
    }

    /**
     * @return mixed
     */
    public function get_item() {
        return $this->item;
    }

    /**
     * @return rule|\stdClass
     */
    public function get_rule() {
        return $this->rule;
    }

    /**
     * @param $item
     * @return mixed|null
     */
    public static function extract_iteminfo($item) {
        $data = null;
        if (isset($item->iteminfo)) {
            $info = $item->iteminfo;
            $datas = [];

            $rulematch = preg_match('/' . PATTERN['open'] . '.+'. addcslashes(PATTERN['close'], '/') . '/', $info, $datas);
            if ($rulematch) {
                $data = str_replace(PATTERN['open'], '', $datas[0]);
                $data = str_replace(PATTERN['close'], '', $data);
                $data = json_decode($data);
            }
        }

        return $data;
    }

    /**
     * @param \stdClass $item
     * @param string $prop
     * @param string $val
     * @return string|string[]|null
     */
    public static function insert_iteminfo($item, $prop, $val) {
        $iteminfo = self::extract_iteminfo($item);

        if (empty($iteminfo)) {
            // We append.
            $iteminfo = new \stdClass();
            $iteminfo->$prop = $val;
            $iteminfo = PATTERN['open'] . json_encode($iteminfo) . PATTERN['close'];
        } else {
            // We insert.
            $iteminfo->$prop = $val;
            $pattern = '/' . PATTERN['open'] . '.+'. addcslashes(PATTERN['close'], '/') . '/';
            $iteminfo = preg_replace($pattern, PATTERN['open'] . json_encode($iteminfo) . PATTERN['close'], $item->iteminfo);
        }

        return $iteminfo;
    }

    /**
     * @param $item
     * @return mixed
     */
    private function set_item($item) {
        if ($item->itemtype === 'course') {
               $item->fullname = $item->coursename;
        }

        $total = 0;
        foreach ($this->data as $gi) {
            $total += $gi->grademax;
        }
        $item->total = $total;
        return $item;
    }

    /**
     * @param $courseid
     * @param $categoryid
     * @return array
     * @throws \dml_exception
     */
    private function get_rawdata($courseid, $categoryid) {
        global $DB;

        if (is_null($categoryid)) {
            $categoryid = $DB->get_field(
                'grade_items',
                'iteminstance',
                ['courseid' => $courseid, 'itemtype' => 'course']
            );
        }

        $sql = "SELECT gi.*, gc.depth, gc2.depth itemdepth, gc.id thiscatid, gc.fullname, c.fullname coursename
                FROM {grade_items} gi
                LEFT JOIN {grade_categories} gc ON gc.id = gi.iteminstance
                LEFT JOIN {grade_categories} gc2 ON gc2.id = gi.categoryid
                LEFT JOIN {course} c ON c.id = gi.courseid
                WHERE gi.courseid = ?
                AND gi.grademax > 0
                AND gi.gradetype > 0
                AND (gc.parent = ? OR gi.categoryid = ? OR gi.iteminstance = ?)
                ORDER BY gi.sortorder";

        $items = $DB->get_records_sql($sql, [$courseid, $categoryid, $categoryid, $categoryid]);

        return $items;
    }

    /**
     * @param $items
     */
    private function add_customdata($items) {
        // Now add the custom columns.
        $cols = $this->get_rule()->get_customcolumns();
        foreach ($cols as $col) {
            $attr = $col->id;
            foreach ($items as $item) {
                $datas = [];

                $pattern = '/' . PATTERN['open'] . '.+'. addcslashes(PATTERN['close'], '/') . '/';
                $match = preg_match($pattern, $item->iteminfo, $datas);
                if ($match) {
                    $rawdata = str_replace(PATTERN['open'], '', $datas[0]);
                    $rawdata = str_replace(PATTERN['close'], '', $rawdata);
                    $data = json_decode($rawdata);
                    $item->$attr = isset($data->$attr) ? $data->$attr : '';
                } else {
                    $item->$attr = null;
                }
            }
        }

    }

}
