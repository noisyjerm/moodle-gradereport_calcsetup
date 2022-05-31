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
 * Applies properties to grade items
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup;

/**
 * Class rule
 * @package gradereport_calcsetup
 */
class rule {
    /** @var false|mixed|\stdClass  */
    private $rule;

    /** @var array */
    private $items;

    /**
     * actions constructor.
     * @param string $rulename
     * @param \gradereport_calcsetup\gradecategory $gradecategory
     */
    public function __construct($rulename, $items, $item) {
        $this->item = $item;
        $this->items = $items;
        $iteminfo = \gradereport_calcsetup\gradecategory::extract_iteminfo($item);

        $this->rule = $this->extract_rule($iteminfo, $rulename);
    }

    /**
     * @return mixed
     */
    public function get_calc() {
        return $this->rule->calc;
    }

    /**
     * @return string
     */
    public function get_idnumber() {
        return $this->rule->idnumber;
    }

    /**
     * @return mixed
     */
    public function get_columns() {
        return $this->rule->cols;
    }

    /**
     * @return array
     */
    public function get_customcolumns() {
        $cols = $this->get_columns();
        $customcols = [];
        foreach ($cols as $col) {
            if ($col->valtype <> VALTYPE_COL) {
                $customcols[] = $col;
            }
        }

        return $customcols;
    }

    /**
     * @return array
     */
    public function get_fields() {
        $cols = $this->get_columns();
        $fields = [];
        foreach ($cols as $field) {
            $fields[] = $field->val;
        }
        return $fields;
    }

    /**
     * @return false|mixed|\stdClass
     */
    public function get_rule() {
        return $this->rule;
    }

    /**
     * @return string
     */
    public function get_name() {
        return $this->rule->name;
    }

    /**
     * @return string
     */
    public function get_description() {
        return $this->rule->desc;
    }

    /**
     * @return array
     */
    public function get_displayoptions() {
        $fields = json_decode($this->rule->fields);
        return empty($fields) ? [] : $fields;
    }

    /**
     * Save the rule to the category and apply any actions.
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     */
    public function apply() {
        global $DB;
        // Todo. Return false if no change.
        // Save the rulename to the cateory.
        $iteminfo = \gradereport_calcsetup\gradecategory::insert_iteminfo($this->item, 'rule', $this->get_idnumber());
        $this->item->iteminfo = $iteminfo;
        $DB->set_field('grade_items', 'iteminfo', $iteminfo, ['id' => $this->item->id]);

        // Perform the actions.
        $actions = [];
        if (isset($this->rule->actions)) {
            $actions = $this->rule->actions;
        }

        foreach ($actions as $action) {
            // Todo. This should exist but put in error checking.
            $set = $action->set;

            $filtereditems = $this->filter_items($this->items, $action->cond);
            // Todo: See what grade functions exist to make this more robust.
            foreach ($filtereditems as $item) {
                if (!isset($item->$set) || $set === 'itemgroup') {
                    $item->$set = $action->val;
                    $iteminfo = \gradereport_calcsetup\gradecategory::insert_iteminfo($item, $set, $action->val);
                    $item->iteminfo = $iteminfo;
                    $item->update();
                } else {
                    $item->$set = $action->val;
                    $item->update();
                }
            }
        }

        return true;
    }

    /**
     * @param $iteminfo
     * @param string $rule
     * @return \stdClass
     * @throws \dml_exception
     */
    private function extract_rule($iteminfo, $rule = '') {
        global $DB;

        if ($rule === '') {
            // Get the rulename from the category item info.
            if (isset($iteminfo->rule)) {
                $rule = $iteminfo->rule;
            }
        }

        // Get the rule data.
        if ($rule !== '') {
            $rule = $DB->get_record('gradereport_calcsetup_rules', ['idnumber' => $rule]);
        }

        if (is_object($rule )) {
            $rule->actions = json_decode($rule->actions);
            $rule->cols = json_decode($rule->cols);
        } else {
            $rule = new \stdClass();
            $rule->idnumber = '';
            $rule->name = '';
            $rule->desc = get_string('norule', 'gradereport_calcsetup');
            $rule->cols = [];
            $rule->fields = get_string('standardfields', 'gradereport_calcsetup');
            $rule->calc = '';
        }

        return $rule;
    }

    /**
     * @param array $items
     * @param array|string $cond
     * @return array
     */
    protected function filter_items($items, $cond) {
        if ($cond === 'all') {
            return $items;
        }

        $filtereditems = [];
        $prop = $cond[0];
        foreach ($items as $item) {
            if ($item->$prop === $cond[1]) {
                $filtereditems[] = $item;
            }
        }
        return $filtereditems;
    }
}
