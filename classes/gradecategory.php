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
use grade_item;

/** @const array 'tag' used in regular expression to find data stored in iteminfo field */
const PATTERN = ['open' => '{{gradereportcalcsetup}}', 'close' => '{{/gradereportcalcsetup}}'];

/**
 * Class gradecategory
 * @package gradereport_calcsetup
 */
class gradecategory {
    /** @var int */
    private $courseid;

    /** @var \grade_item */
    private $item;

    /** @var \stdClass */
    private $iteminfo;

    /** @var \gradereport_calcsetup\rule */
    private $rule;

    /** @var array */
    private $gradeitems;

    /**
     * gradecategory constructor.
     * @param int $courseid
     * @param int $categoryid
     */
    public function __construct($courseid = 0, $categoryid = 0) {
        $this->courseid = $courseid;
        $this->gradeitems = [];
        $gradeitems = $this->get_rawdata($courseid, $categoryid);

        $this->item = $this->set_item(array_shift($gradeitems));
        $grademaxtotal = 0;
        $aggregationcoeftotal = 0;

        foreach ($gradeitems as $item) {
            $grademaxtotal += $item->grademax;
            $aggregationcoeftotal += $item->aggregationcoef;
            $gradeitem = grade_item::fetch(array('id' => $item->id, 'courseid' => $courseid));
            $gradeitem->thiscatid = $item->thiscatid;
            $gradeitem->fullname = $item->fullname;
            $gradeitem->coursename = $item->coursename;
            $this->gradeitems[$item->id] = $gradeitem;
        }
        $this->item->grademax_total = $grademaxtotal;
        $this->item->aggregationcoef_total = $aggregationcoeftotal;

        // Extract the rule.
        $this->iteminfo = self::extract_iteminfo($this->item);
        $rulename = isset($this->iteminfo->rule) ? $this->iteminfo->rule : '';
        $this->rule = new \gradereport_calcsetup\rule($rulename, $this->gradeitems, $this->item);

        // Add the custom data.
        $this->add_customdata([$this->item]);
        $this->add_customdata($this->gradeitems);

    }

    /**
     * Getter for courseid
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Getter for grade item id.
     * @return int
     */
    public function get_itemid() {
        return $this->item->id;
    }

    /**
     * Getter for categroy id
     * @return int
     */
    public function get_catid() {
        return $this->item->iteminstance;
    }

    /**
     * Getter for grade item or items
     * @return array|\grade_item
     */
    public function get_gradeitems($id = 0) {
        if ($id > 0) {
            if (isset($this->gradeitems[$id])) {
                return $this->gradeitems[$id];
            } else if ($this->item->id === $id) {
                return $this->get_item();
            }
        } else {
            return $this->gradeitems;
        }
    }

    /**
     * Getter for iteminfo.
     * @return mixed|\stdClass|null
     */
    public function get_iteminfo() {
        return $this->iteminfo;
    }

    /**
     * Getter for grade item.
     * @return mixed
     */
    public function get_item() {
        return $this->item;
    }

    /**
     * Getter for grade calculation rule.
     * @return rule|\stdClass
     */
    public function get_rule() {
        return $this->rule;
    }

    /**
     * Gets the rule as an object from the JSON string.
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
     * Adds or updates the iteminfo field with the property and value so it can be retrieved later.
     * @param \grade_item $item
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
     * Gets the grade item and normalises the name for consistent usage.
     * @param \stdClass $item
     * @return mixed gradeitem or boolean if doesn't exist
     */
    private function set_item($item) {
        $gradeitem = grade_item::fetch(array('id' => $item->id, 'courseid' => $item->courseid));
        $gradeitem->fullname = $item->itemtype === 'course' ? $item->coursename : $item->fullname;
        return $gradeitem;
    }

    /**
     * Get the grade items as stdClass from the grade items table.
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
     * Get the custom properties from the grade item iteminfo and add them to the object.
     * @param array $items
     */
    private function add_customdata($items) {
        // Now add the custom columns.
        foreach ($items as $item) {

            $datas = [];
            $corefields = array_keys($this->rule->get_core_fields());

            $pattern = '/' . PATTERN['open'] . '.+'. addcslashes(PATTERN['close'], '/') . '/';
            $match = preg_match($pattern, $item->iteminfo, $datas);
            if ($match) {
                $rawdata = str_replace(PATTERN['open'], '', $datas[0]);
                $rawdata = str_replace(PATTERN['close'], '', $rawdata);
                $data = json_decode($rawdata);
                foreach ($data as $key => $val) {
                    // Don't overwrite core fields.
                    if (!in_array($key, $corefields)) {
                        $item->$key = $val;
                    }
                }
            }
        }
    }

    /**
     * Save the form data to the database.
     * @param $data
     * @param $fields
     * @throws \coding_exception
     */
    public function update_items($data, $fields) {
        $last = 'nomatch';
        $corefields = $this->rule->get_core_fields();
        $propsset = 0;
        $changed = [];
        foreach ($data as $key => $value) {
            foreach ($fields as $field) {
                $property = $field->property;

                if (preg_match('/^' . $property . '_([0-9]+)$/', $key, $matches)) {
                    $aid = $matches[1];

                    if (!empty($corefields[$property]->locked)) {
                        // We shouldn't actually be able to get here.
                        \core\notification::warning(get_string('cantupdate', 'gradereport_calcsetup', $property));
                        break;
                    }

                    if (!empty($corefields[$property]->validation) && $corefields[$property]->validation === 'number') {
                        if (!is_numeric($value)) {
                            \core\notification::warning(get_string('wrongtype', 'gradereport_calcsetup', $value));
                            break;
                        }
                    }

                    if ($last !== $aid) {
                        $last = $aid;
                        $propsset = 0;
                        $gradeitem = $this->get_gradeitems($aid);
                    }

                    $custom = !in_array($property, array_keys($corefields));

                    if ($property === 'calculation') {
                        $p = $gradeitem->denormalize_formula($gradeitem->calculation, $gradeitem->courseid);
                    } else {
                        $p = $gradeitem->$property;
                    }

                    if ($p != $value) {
                        $propsset += 1;
                        $changed[$gradeitem->id] = $gradeitem;
                        $gradeitem->propsset = $propsset;
                        if ($custom) {
                            $iteminfo = self::insert_iteminfo($gradeitem, $property, $value);
                            $gradeitem->iteminfo = $iteminfo;
                            $gradeitem->$property = $value;
                        } else {
                            $gradeitem->$property = $value;
                        }
                    }
                }
            }
        }

        foreach ($changed as $gradeitem) {
            $gradeitem->update();
            \core\notification::success(get_string(
                'categoryupdated',
                'gradereport_calcsetup',
                ['changed' => $gradeitem->propsset, 'itemname' => $gradeitem->get_name()])
            );
        }

        if (empty($changed)) {
            \core\notification::success(get_string(
                    'categoryupdated',
                    'gradereport_calcsetup',
                    ['changed' => 0, 'itemname' => '']
                )
            );
        }
    }

}
