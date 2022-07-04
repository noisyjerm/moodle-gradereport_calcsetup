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
 * Form for creating and changing calculation setup rules
 *
 * @package    gradereport_calcsetup
 * @copyright  2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_calcsetup\output;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');


/**
 * Form for adding and updating calculation rules
 */
class editrule_form extends \moodleform {

    /** @var string  */
    private $pluginname = 'gradereport_calcsetup';

    /** @var array  */
    private $rules = [];

    /**
     * Describes the form elements
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        global $DB;

        $ruleid = $this->_customdata['ruleid'];
        $mform = $this->_form;

        $this->rules = $DB->get_records('gradereport_calcsetup_rules');
        $rule = $ruleid !== 0 ? $this->rules[$ruleid] : null;

        $mform->addElement('hidden', 'id', !empty($rule) ? $rule->id : 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_RAW);
        $mform->setDefault('name',
            !empty($rule) ? $rule->name : get_string('placeholdername', $this->pluginname)
        );

        // ID number.
        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_ALPHANUMEXT);
        $mform->setDefault('idnumber',
            !empty($rule) ? $rule->idnumber : get_string('placeholderidnum', $this->pluginname)
        );
        if (!$ruleid) {
            $mform->addRule('idnumber', get_string('required'), 'required');
            $mform->registerRule('checkidnumber', 'callback', 'checkifidused', $this);
            $mform->addRule('idnumber', get_string('idnumused', $this->pluginname), 'checkidnumber', true);
        }
        $mform->disabledIf('idnumber', 'id', 'neq', 0);

        // Description.
        $mform->addElement('textarea', 'descr', get_string('description'));
        $mform->setType('descr', PARAM_RAW);
        $mform->setDefault('descr',
            !empty($rule) ? $rule->descr : get_string('placeholderdescription', $this->pluginname)
        );

        // Visible.
        $mform->addElement('checkbox', 'visible', get_string('visible'));
        $mform->setType('visible', PARAM_ALPHANUMEXT);
        $mform->setDefault('visible',
            !empty($rule) ? $rule->visible : 1
        );

        // Calculation template.
        $mform->addElement('textarea', 'calc', get_string('calculation', $this->pluginname));
        $mform->setType('calc', PARAM_RAW);
        $mform->setDefault('calc',
            !empty($rule) ? $rule->calc : get_string('placeholdercalc', $this->pluginname)
        );
        $mform->addHelpButton('calc', 'template', $this->pluginname);
        $mform->addElement('static', 'calchelper', '', get_string('loophelper', $this->pluginname));

        // Actions.
        $mform->addElement('hidden', 'actions', get_string('actions', $this->pluginname));
        $mform->setType('actions', PARAM_RAW);
        $mform->setDefault('actions',
            !empty($rule) ? $rule->actions : get_string('placeholderjson', $this->pluginname)
        );

        $actions = isset($rule->actions) ? $rule->actions : '';
        $mform->addElement('static', 'actioncontrol', get_string('actions', $this->pluginname),
            $this->displayactions($actions, 'actions'));
        $mform->addHelpButton('actioncontrol', 'actions', $this->pluginname);

        // Fields.
        $mform->addElement('hidden', 'fields', get_string('fields', $this->pluginname));
        $mform->setType('fields', PARAM_RAW);
        $mform->setDefault('fields',
            !empty($rule) ? $rule->fields : get_string('placeholderjson', $this->pluginname)
        );

        $fields = isset($rule->fields) ? $rule->fields : '';
        $mform->addElement('static', 'fieldcontrol', get_string('fields', $this->pluginname),
                           $this->displayfields($fields, 'fields'));
        $mform->addHelpButton('fieldcontrol', 'fields', $this->pluginname);

        // Columns.
        $mform->addElement('hidden', 'cols', get_string('columns', $this->pluginname));
        $mform->setType('cols', PARAM_RAW);
        $mform->setDefault('cols',
            !empty($rule) ? $rule->cols : get_string('placeholderjson', $this->pluginname)
        );

        $cols = isset($rule->cols) ? $rule->cols : '';
        $mform->addElement('static', 'colcontrol', get_string('columns', $this->pluginname),
                           $this->displayfields($cols, 'cols'));
        $mform->addHelpButton('colcontrol', 'columns', $this->pluginname);

        $this->add_action_buttons();
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Todo: validate idnumber format.
        // Todo: something better with the JSON fields.
        return $errors;
    }

    /**
     * Prepares HTML 'table' for static form 'field'
     * @param $data
     * @param $elename
     * @return string
     * @throws \coding_exception
     */
    private function displayfields($data, $elename) {
        $cols = '';
        $i = 0;
        $stradd = get_string('add');
        if (!empty($data)) {
            $columns = json_decode($data);
            $stredit = get_string('edit');
            $strup = get_string('moveup');
            $strdn = get_string('movedown');
            $strdel = get_string('delete');

            foreach ($columns as $col) {
                $cols .= "<div class='row rule-$elename' data-index='$i' data-targetele='$elename'><span class='col'>";
                $title = $col->title;
                if (is_object($title)) {
                    $cols .= get_string($title->identifier, isset($title->component) ? $title->component : 'core');
                } else {
                    $cols .= $title;
                }
                $cols .= "</span><span class='col'>$col->property</span>";
                $edit = !empty($col->editable)
                    ? get_string('editable', 'gradereport_calcsetup')
                    : get_string('locked', 'gradereport_calcsetup');
                $cols .= " <span class='col'>$edit</span>";
                $cols .= " <span class='col-md-3 text-right'>" .
                    "<a class='up' href='#'>
                              <i class='icon fa fa-arrow-up fa-fw ' title='$strup' aria-label='$strup' data-action='up'></i>
                          </a>" .
                    "<a class='down' href='#'>
                              <i class='icon fa fa-arrow-down fa-fw ' title='$strdn' aria-label='$strdn' data-action='down'></i>
                          </a>" .
                    "<a class='delete' href='#'>
                              <i class='icon fa fa-trash fa-fw ' title='$strdel' aria-label='$strdel' data-action='delete'></i>
                          </a>" .
                    "<a class='edit' href='#'>
                              <i class='icon fa fa-cog fa-fw ' title='$stredit' aria-label='$stredit' data-action='edit'></i>
                          </a>" .
                    "</span></div>";
                $i ++;
            }
        }

        $cols .= "<div class='row rule-$elename' data-index='$i' data-targetele='$elename'>
                         <span class='col'></span><span class='col'></span class='col'><span class='col'></span>
                         <span class='col-md-3 text-right'><a class='add' href='#'>
                         <i data-action='edit'>$stradd</i>
                         <i class='icon fa fa-plus fa-fw' title='$stradd' aria-label='$stradd' data-action='edit'></i>
                      </a></span></div>";

        return $cols;
    }

    /**
     * Prepares HTML 'table' for static form 'field'
     * @param $data
     * @param $elename
     * @return string
     * @throws \coding_exception
     */
    private function displayactions($data, $elename) {
        $stradd  = get_string('add');
        $stredit = get_string('edit');
        $strdel  = get_string('delete');
        $html = '';
        $i = 0;
        if (!empty($data)) {
            $actions = json_decode($data);
            foreach ($actions as $action) {
                if (empty($action->op)) {
                    $action->op = 'equals';
                }
                $action->op = get_string($action->op, 'gradereport_calcsetup');
                $html .= \html_writer::start_div('row rule-actions', ['data-index' => $i]);
                $html .= \html_writer::span(
                    get_string('action', 'gradereport_calcsetup', $action),
                   'col-md-9'
                );
                $html .= \html_writer::start_span('col-md-3 text-right');
                $html .= \html_writer::link('#',
                    \html_writer::tag('i', '', [
                        'class' => 'icon fa fa-trash fa-fw',
                        'title' => $strdel,
                        'aria-label' => $strdel,
                        'data-action' => 'delete'
                    ])
                );
                $html .= \html_writer::link('#',
                    \html_writer::tag('i', '', [
                        'class' => 'icon fa fa-cog fa-fw',
                        'title' => $stredit,
                        'aria-label' => $stredit,
                        'data-action' => 'edit'
                    ])
                );
                $html .= \html_writer::end_span();
                $i++;
                $html .= \html_writer::end_div();
            }
        }

        $html .= \html_writer::start_div('row rule-actions', ['data-index' => $i]);
        $html .= \html_writer::span('', 'col-md-9');
        $html .= \html_writer::start_span('col-md-3 text-right');
        $html .= \html_writer::link('#',
            \html_writer::tag('i', $stradd, ['data-action' => 'edit'])
            . \html_writer::tag('i', '', [
                'class' => 'icon fa fa-plus fa-fw',
                'title' => $stradd,
                'aria-label' => $stradd,
                'data-action' => 'edit'
            ]), ['title' => $stradd]);
        $html .= \html_writer::end_tag('a');
        $html .= \html_writer::end_span();
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Validation rule for idnumber field.
     * @param $val
     * @return bool
     */
    public function checkifidused($val) {
        foreach ($this->rules as $rule) {
            if ($val === $rule->idnumber) {
                return false;
            }
        }

        return true;
    }

}
