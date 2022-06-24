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
 * Strings for Grade Calculation Setup Grade Report
 *
 * @package   gradereport_calcsetup
 * @copyright 2022 Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// General Strings.

$string['pluginname']   = 'Grade calculation tool';
$string['about']        = 'About';
$string['actions']      = 'Actions';
$string['action']       = 'Set {$a->set} to \'{$a->to}\' when {$a->when} {$a->op} {$a->val}';
$string['calculation']  = 'Calculation';
$string['calculationupdated']  = 'Calculation updated.';
$string['cantupdate']   = 'The property \'{$a}\' cannot be updated.';
$string['categoryupdated']   = '{$a->changed} properties set / changed on item {$a->itemname}.';
$string['columns']      = 'Columns';
$string['current']      = 'Current';
$string['deleterule']   = 'Delete rule';
$string['deleterulereally']  = 'Are you sure you want to permanently delete this rule?';
$string['editable']     = 'Editable';
$string['editaction']   = 'Edit action';
$string['editfield']    = 'Edit field';
$string['editrule']     = 'Edit rule';
$string['equals']       = 'is';
$string['eventgradereportviewed'] = 'Calc setup viewed.';
$string['eventgradeitemupdated']  = 'Grade item updated.';
$string['fields']       = 'Fields';
$string['formulaerror'] = "There is an error in the calculation. Check the idnumbers and syntax.";
$string['free']         = "Free form";
$string['greaterorequal']    = 'is greater than or equal to';
$string['greaterthan']       = 'is greater than';
$string['lessorequal']       = 'is less than or equal to';
$string['lessthan']          = 'is less than';
$string['locked']            = 'Locked';
$string['managerules']       = 'Manage rules';
$string['missingtotal']      = 'Missing total name';
$string['newrule']           = 'New rule';
$string['nochanges']         = 'No rule changes made.';
$string['nodata']            = 'No data';
$string['norule']            = 'No rule applied';
$string['notequals']         = 'is not equal to';
$string['plugindescription'] = 'The aim of this tool is to help organisations apply grading methodologies consistently.<br><br>
                                Administrators create calculation \'rules\' which course developers apply to grade categories
                                within their courses. The rules can make changes to grade items when applied, describe a table
                                of grade item properties for convenient editing and provide a template to assist in writing
                                the calculation formulas. <a href=\'{$a}\'>Manage the rules</a>';
$string['pageheader']        = 'Grade calculation tool';
$string['placeholdercalc']   = 'Moodle calculation formula using mustache template markup. e.g. <br>
                                =min({{#items}}[[{{idnumber}}]]{{^last}},{{/last}}{{/items}})';
$string['placeholderdescription'] = 'What is the rule for and what actions happen when it is applied?';
$string['placeholderidnum']  = 'uniqueidentifier';
$string['placeholdername']   = 'Give your rule a name';
$string['placeholderjson']   = '[]';
$string['property']          = 'Property';
$string['rule']              = 'Rule';
$string['ruleupdated']       = 'Rule updated';
$string['set']               = 'Set';
$string['standardfields']    = '[{"title":{"identifier":"maxgrade","component":"core_grades"},"property":"grademax"}]';
$string['title']             = 'Title';
$string['to']                = 'To';
$string['when']              = 'When';
$string['wrongtype']         = 'The value \'{$a}\' is not of the expected type';

