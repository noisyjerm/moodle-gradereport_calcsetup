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
     * rule constructor.
     * @param string $rulename
     * @param array $items list of {@see \grade_item} objects
     * @param \grade_item $item
     * @throws \dml_exception
     */
    public function __construct($rulename, $items, $item) {
        $this->item = $item;
        $this->items = $items;
        $iteminfo = \gradereport_calcsetup\gradecategory::extract_iteminfo($item);

        $this->rule = $this->extract_rule($iteminfo, $rulename);
    }

    /**
     * Getter for calc property of rule object.
     * @return mixed
     */
    public function get_calc() {
        return $this->rule->calc;
    }

    /**
     * Getter for idnumber property of rule object.
     * @return string
     */
    public function get_idnumber() {
        return $this->rule->idnumber;
    }

    /**
     * Getter for cols property of rule object.
     * @return mixed
     */
    public function get_columns() {
        return $this->rule->cols;
    }

    /**
     * Returns simple array of column property names.
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
     * Getter for rule object.
     * @return false|mixed|\stdClass
     */
    public function get_rule() {
        return $this->rule;
    }

    /**
     * Getter for name of rule.
     * @return string
     */
    public function get_name() {
        return $this->rule->name;
    }

    /**
     * Getter for description of rule.
     * @return string
     */
    public function get_description() {
        return $this->rule->descr;
    }

    /**
     * Getter for fields property of rule object:
     * array of the fields of the grade item to display.
     * @return array
     */
    public function get_displayoptions() {
        $fields = json_decode($this->rule->fields);
        return empty($fields) ? [] : $fields;
    }

    /**
     * Save the rule to the category and apply any actions.
     * @param string $rule
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function apply($rule) {
        $updated = false;
        $propsset = 0;
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

        $corefields = $this->get_core_fields();

        foreach ($actions as $action) {
            // This should exist.
            if (!isset($action->set)) {
                break;
            }
            $set = $action->set;
            $custom = !in_array($set, array_keys($corefields));

            if (!empty($corefields[$set]->locked)) {
                \core\notification::warning(get_string('cantupdate', 'gradereport_calcsetup', $set));
                break;
            }

            // Todo: improve the validation.
            // E.g. check aggregation before changing gradetype and check gradetype = 2 before setting scale.
            if (!empty($corefields[$set]->validation) && $corefields[$set]->validation === 'number') {
                if (!is_numeric($action->to)) {
                    \core\notification::warning(get_string('wrongtype', 'gradereport_calcsetup', $action->to));
                    break;
                }
            }

            $filtereditems = $this->filter_items($this->items, $action);
            // Todo: See what grade functions exist to make this more robust.
            foreach ($filtereditems as $item) {
                $update = !isset($item->$set) || $item->$set != $action->to;
                $updated = $update ? $update : $updated;
                $propsset += intval($update);

                // Todo: can 'to' be an expression?
                // E.g. set gradepass to grademax / 2.
                if ($update && $custom) {
                    $item->$set = $action->to;
                    $iteminfo = \gradereport_calcsetup\gradecategory::insert_iteminfo($item, $set, $action->to);
                    $item->iteminfo = $iteminfo;
                    $item->update();
                } else if ($update) {
                    $item->$set = $action->to;
                    $success = $item->update();
                }
            }
        }

        if (!$updated) {
            \core\notification::warning(get_string('nochanges', 'gradereport_calcsetup'));
        } else {
            \core\notification::success(get_string('ruleupdated', 'gradereport_calcsetup', $propsset));
        }

        return  $updated;
    }

    /**
     * Gets the rule as an object from the JSON string.
     * @param \stdClass $iteminfo
     * @param string $rule
     * @return false|mixed|\stdClass|string
     * @throws \coding_exception
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
            $rule->descr = get_string('norule', 'gradereport_calcsetup');
            $rule->cols = [];
            $rule->fields = get_string('standardfields', 'gradereport_calcsetup');
            $rule->calc = '';
        }

        return $rule;
    }

    /**
     * Reduce the child grade items according to conditions.
     * @param array $items
     * @param \stdClass $action
     * @return array
     */
    protected function filter_items($items, $action) {
        if ($action->when === 'all') {
            return $items;
        }

        $filtereditems = [];
        $prop = $action->when;
        foreach ($items as $item) {
            // Todo. Let user specify comparison maybe.
            if ($item->$prop === $action->val) {
                $filtereditems[] = $item;
            }
        }
        return $filtereditems;
    }

    /**
     * Descibe how the core fields can be used.
     * @return array
     * @throws \coding_exception
     */
    public function get_core_fields() {
        // Todo: somehow refactor this and get_coreitemfields into one.
        global $CFG;
        $defaultgradedisplaytype = grade_get_setting($this->item->courseid, 'displaytype', $CFG->grade_displaytype);
        $defaultdisplay = $this->get_displaytypename($defaultgradedisplaytype);
        return [
            'id'               => (object) ['locked' => true],
            'courseid'         => (object) ['locked' => true],
            'categoryid'       => (object) ['locked' => true],
            'itemname'         => null,
            'itemtype'         => (object) ['locked' => true],
            'itemmodule'       => (object) ['locked' => true],
            'iteminstance'     => (object) ['locked' => true],
            'itemnumber'       => (object) ['locked' => true],
            'iteminfo'         => null,
            'idnumber'         => null,
            'calculation'      => null,
            'gradetype'        => null,
            'grademax'         => (object) ['validation' => 'number'],
            'grademin'         => (object) ['validation' => 'number'],
            'scaleid'          => null,
            'outcomeid'        => (object) ['validation' => 'number'],
            'gradepass'        => (object) ['validation' => 'number'],
            'multfactor'       => (object) ['validation' => 'number'],
            'plusfactor'       => (object) ['validation' => 'number'],
            'aggregationcoef'  => (object) ['validation' => 'number'],
            'aggregationcoef2' => (object) ['validation' => 'number'],
            'sortorder'        => (object) ['locked' => true],
            'display'          => (object) [
                'options'          => [
                    (object) ['val' => GRADE_DISPLAY_TYPE_DEFAULT, 'name' => get_string('defaultprev', 'grades', $defaultdisplay)],
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
            'needsupdate'      => null,
            'weightoverride'   => (object) ['validation' => 'number'],
            'timecreated'      => (object) ['locked' => true],
            'timemodified'     => (object) ['locked' => true]
        ];
    }

    /**
     * Get a readable name for the grade display type.
     * @param integer $display
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
