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
     * @deprecated
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

    public function get_core_fields() {
        global $CFG;
        $defaultgradedisplaytype = grade_get_setting($this->item->courseid, 'displaytype', $CFG->grade_displaytype);
        $defaultgradedisplay = $this->get_displaytypename($defaultgradedisplaytype);
        return [
            'id'               => (object) ['locked' => true],
            'courseid'         => (object) ['locked' => true],
            'categoryid'       => (object) ['locked' => true],
            'itemname',
            'itemtype'         => (object) ['locked' => true],
            'itemmodule'       => (object) ['locked' => true],
            'iteminstance'     => (object) ['locked' => true],
            'itemnumber'       => (object) ['locked' => true],
            'iteminfo',
            'idnumber',
            'calculation',
            'gradetype'        => (object) ['locked' => true],
            'grademax'         => (object) ['validation' => 'number'],
            'grademin'         => (object) ['validation' => 'number'],
            'scaleid'          => (object) ['validation' => 'number'],
            'outcomeid'        => (object) ['validation' => 'number'],
            'gradepass'        => (object) ['validation' => 'number'],
            'multfactor'       => (object) ['validation' => 'number'],
            'plusfactor'       => (object) ['validation' => 'number'],
            'aggregationcoef'  => (object) ['validation' => 'number'],
            'aggregationcoef2' => (object) ['validation' => 'number'],
            'sortorder'        => (object) ['locked' => true],
            'display'          => (object) [
                'options'          => [
                    (object) ['val' => GRADE_DISPLAY_TYPE_DEFAULT, 'name' => get_string('defaultprev', 'grades', $defaultgradedisplay)],
                    (object) ['val' => GRADE_DISPLAY_TYPE_REAL, 'name' => get_string('real', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_REAL_PERCENTAGE, 'name' => get_string('realpercentage', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_REAL_LETTER, 'name' => get_string('realletter', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_PERCENTAGE, 'name' => get_string('percentage', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_PERCENTAGE_REAL, 'name' => get_string('percentagereal', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER, 'name' => get_string('percentageletter', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_LETTER, 'name' => get_string('letter', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_LETTER_REAL, 'name' => get_string('letterreal', 'grades')],
                    (object) ['val' => GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE, 'name' => get_string('letterpercentage', 'grades')],
                ],
            ],
            'decimals'         => (object) ['validation' => 'number'],
            'hidden'           => (object) ['validation' => 'number'],
            'locked'           => (object) ['locked' => true],
            'locktime'         => (object) ['locked' => true],
            'needsupdate',
            'weightoverride'   => (object) ['validation' => 'number'],
            'timecreated'      => (object) ['locked' => true],
            'timemodified'     => (object) ['locked' => true]
        ];
    }

    /**
     * @param $display
     * @return \lang_string|string
     * @throws \coding_exception
     */
    private function get_displaytypename($display) {

        switch($display) {
            case GRADE_DISPLAY_TYPE_REAL:
                return get_string('real', 'grades');
            case GRADE_DISPLAY_TYPE_REAL_PERCENTAGE:
                return get_string('realpercentage', 'grades');
            case GRADE_DISPLAY_TYPE_REAL_LETTER:
                return get_string('realletter', 'grades');
            case GRADE_DISPLAY_TYPE_PERCENTAGE:
                return get_string('percentage', 'grades');
            case GRADE_DISPLAY_TYPE_PERCENTAGE_REAL:
                return get_string('percentagereal', 'grades');
            case GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER:
                return get_string('percentageletter', 'grades');
            case GRADE_DISPLAY_TYPE_LETTER    :
                return get_string('letter', 'grades');
            case GRADE_DISPLAY_TYPE_LETTER_REAL:
                return get_string('letterreal', 'grades');
            case GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE:
                return get_string('letterpercentage', 'grades');
            default:
                return get_string('default', 'grades');
        }

    }
}
