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

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        global $DB;

        $ruleid = $this->_customdata['ruleid'];
        $mform = $this->_form;

        $rule = $DB->get_record('gradereport_calcsetup_rules', ['id' => $ruleid]);

        $mform->addElement('hidden', 'id', !empty($rule) ? $rule->id : 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_RAW);
        $mform->setDefault('name',
            !empty($rule) ? $rule->name : get_string('placeholdername', $this->pluginname)
        );

        $mform->addElement('text', 'idnumber', get_string('idnumber'));
        $mform->setType('idnumber', PARAM_ALPHANUMEXT);
        $mform->setDefault('idnumber',
            !empty($rule) ? $rule->idnumber : get_string('placeholderidnum', $this->pluginname)
        );
        $mform->addRule('idnumber', get_string('required'), 'required');

        $mform->addElement('textarea', 'descr', get_string('description'));
        $mform->setType('descr', PARAM_RAW);
        $mform->setDefault('descr',
            !empty($rule) ? $rule->descr : get_string('placeholderdescription', $this->pluginname)
        );

        $mform->addElement('checkbox', 'visible', get_string('visible'));
        $mform->setType('visible', PARAM_ALPHANUMEXT);
        $mform->setDefault('visible',
            !empty($rule) ? $rule->visible : 1
        );

        $mform->addElement('textarea', 'calc', get_string('calculation', $this->pluginname));
        $mform->setType('calc', PARAM_RAW);
        $mform->setDefault('calc',
            !empty($rule) ? $rule->calc : get_string('placeholdercalc', $this->pluginname)
        );

        $mform->addElement('textarea', 'actions', get_string('actions', $this->pluginname));
        $mform->setType('actions', PARAM_RAW);
        $mform->setDefault('actions',
            !empty($rule) ? $rule->actions : get_string('placeholderjson', $this->pluginname)
        );

        $mform->addElement('textarea', 'fields', get_string('fields', $this->pluginname));
        $mform->setType('fields', PARAM_RAW);
        $mform->setDefault('fields',
            !empty($rule) ? $rule->fields : get_string('placeholderjson', $this->pluginname)
        );

        $mform->addElement('hidden', 'cols', get_string('columns', $this->pluginname));
        $mform->setType('cols', PARAM_RAW);
        $mform->setDefault('cols',
            !empty($rule) ? $rule->cols : get_string('placeholderjson', $this->pluginname)
        );

        $cols = '';
        $i = 0;
        if (!empty($rule->cols)) {
            $columns = json_decode($rule->cols);
            $stredit = get_string('edit');
            $strup = get_string('moveup');
            $strdn = get_string('movedown');
            $strdel = get_string('delete');
            foreach ($columns as $col) {
                $cols .= "<div class='row rule-field' data-index='$i'><span class='col'>";
                $title = $col->title;
                if (is_object($title)) {
                    $cols .= get_string($title->identifier, $title->component);
                } else {
                    $cols .= $title;
                }
                $cols .= "</span><span class='col'>$col->property</span>";
                $edit = $col->editable
                      ? get_string('editable', 'gradereport_calcsetup')
                      : get_string('locked', 'gradereport_calcsetup');
                $cols .= " <span class='col'>$edit</span>";
                $cols .= " <span class='col-md-2'>" .
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
        $mform->addElement('static', 'description', get_string('columns', $this->pluginname),
            $cols);

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

}
