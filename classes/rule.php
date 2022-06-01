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

    /** @var \grade_item */
    private $item;

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
            if (!in_array($col->property, $this->item->required_fields) ) {
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
            $fields[] = $field->property;
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
    public function apply($rule) {
        global $DB;
        $updated = false;
        // Save the rulename to the cateory.
        if ($this->get_idnumber() != $rule) {
            $iteminfo = \gradereport_calcsetup\gradecategory::insert_iteminfo($this->item, 'rule', $rule);
            $this->rule = $this->extract_rule($iteminfo, $rule);
            $this->item->iteminfo = $iteminfo;
            $this->item->update();
            $updated = true;
        }

        // Perform the actions.
        $actions = [];
        if (isset($this->rule->actions)) {
            $actions = $this->rule->actions;
        }

        foreach ($actions as $action) {
            // This should exist.
            if (!isset($action->set)) {
                break;
            }
            $set = $action->set;
            $custom = !in_array($set, $this->item->required_fields);

            $filtereditems = $this->filter_items($this->items, $action->cond);
            // Todo: See what grade functions exist to make this more robust.
            foreach ($filtereditems as $item) {

                $update = !isset($item->$set) || $item->$set != $action->val;

                if (in_array($set, LOCKEDFIELDS)) {
                    \core\notification::warning(get_string('cantupdate', 'gradereport_calcsetup', $set));
                    break;
                }

                if (in_array($set, NUMERIC) && !is_numeric($action->val)) {
                    \core\notification::warning(get_string('wrongtype', 'gradereport_calcsetup', $action->val));
                    break;
                }

                if ($update && $custom) {
                    $item->$set = $action->val;
                    $iteminfo = \gradereport_calcsetup\gradecategory::insert_iteminfo($item, $set, $action->val);
                    $item->iteminfo = $iteminfo;
                    $item->update();
                } else if ($update) {
                    $item->$set = $action->val;
                    $item->update();
                }
            }
        }

        if (!$updated || $update) {
            \core\notification::warning(get_string('nochanges', 'gradereport_calcsetup'));
        }

        return  $updated || $update;
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
