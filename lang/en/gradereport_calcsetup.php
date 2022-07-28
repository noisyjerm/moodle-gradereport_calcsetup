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
$string['actions_help'] = 'Actions are performed when the rule is changed. They are used to initially set properties for items. E.g.

Set gradepass to 50 when grademax is 100

Set itemgroup to \'activity\' when itemtype is \'mod\'

The logic is very basic with "is" being the only comparison available. There is minimal validation here so use with caution.';
$string['action']       = 'Set {$a->set} to \'{$a->to}\' when {$a->when} {$a->op} \'{$a->val}\'';
$string['actionall']    = 'Set {$a->set} to \'{$a->to}\'';
$string['allitems']     = 'All items';
$string['calculation']  = 'Calculation';
$string['calculationupdated']  = 'Calculation updated.';
$string['cantupdate']   = 'The property \'{$a}\' cannot be updated.';
$string['categoryupdated']   = '{$a->changed} properties set / changed on item {$a->itemname}.';
$string['columns']      = 'Columns';
$string['columns_help'] = 'Choose the grade item properties that are listed in the table columns.
                           The table rows contain the grade items within the selected category.';
$string['current']      = 'Current';
$string['deleterule']   = 'Delete rule';
$string['deleterulereally']  = 'Are you sure you want to permanently delete this rule?';
$string['editable']     = 'Editable';
$string['editaction']   = 'Edit action';
$string['editcols']     = 'Edit column';
$string['editfield']    = 'Edit field';
$string['editrule']     = 'Edit rule';
$string['equals']       = 'is';
$string['eventgradereportviewed'] = 'Calc setup viewed.';
$string['eventgradeitemupdated']  = 'Grade item updated.';
$string['example1_name'] = 'Achievement';
$string['example1_desc'] = 'All grade items in this category should use the "Default competence scale".
The category aggregation should be set to something other than "Natural" -
only because we can\'t change the scale otherwise.
<br><br>
A simple MIN calculation for all grade items in this category is used.
<br><br>
When applying this rule:
<br>- Assignments are changed to use the "Default competence scale"
<br>- Grade items within this category that are set to "Default competence scale" will have a grade to pass set to 2 (competent).';
$string['example2_name'] = 'Pass or nothing';
$string['example2_desc'] = 'All grade items in this category must be passed.
If any grade item is not passed, a grade of 0 will be given for the category.
If all grade items are passed, the category grade will be the
mean of the grade items scaled to the category maximum grade.
<br><br>
Applying this rule will not change the grade items.';
$string['fields']       = 'Fields';
$string['fields_help']  = 'Choose the grade item properties that are listed for this category.
                           Set whether each property can be edited by the course creator.';
$string['formulaerror'] = "There is an error in the calculation. Check the idnumbers and syntax.";
$string['free']         = "Free form";
$string['greaterorequal']    = 'is greater than or equal to';
$string['greaterthan']       = 'is greater than';
$string['idnumused']         = 'This ID number is already used. Try something else.';
$string['lessorequal']       = 'is less than or equal to';
$string['lessthan']          = 'is less than';
$string['locked']            = 'Locked';
$string['loophelper']        = '<a href="#" class="loophelper">Calculation loop helper</a>';
$string['managerules']       = 'Manage rules';
$string['missingtotal']      = 'Missing total name';
$string['newrule']           = 'New rule';
$string['nochanges']         = 'No rule changes made.';
$string['nodata']            = 'No data';
$string['norule']            = 'No rule applied';
$string['notequals']         = 'is not equal to';
$string['plugindescription'] = '<p>The aim of this tool is to help organisations apply grading methodologies consistently.<br><br>
                                Administrators create calculation \'rules\' which course developers apply to grade categories
                                within their courses. The rules can make changes to grade items when applied, describe a table
                                of grade item properties for convenient editing and provide a template to assist in writing
                                the calculation formulas. <a href=\'{$a}\'>Manage the rules</a>
                                <br><br>
                                <b>Calculation templates</b>
                                <br><br>
                                The calculation template uses Mustache markup. mustache(5) - Logic-less templates.
                                All grade item properties are available as variables, e.g. {{gradepass}}. Not all can be changed though.
                                <br><br>
                                Properties of the category can be accessed using dot syntax e.g {{category.grademax_total}} or wrapping in {{#category}} e.g. <br>
                                e.g. {{#category}}{{grademax_total}}{{/category}} Note: grademax_total is a special pseudo property that sums the max grade of items in the category.
                                <br><br>
                                When grade items have been assigned a \'group\' through the columns definition, these can be looped with the following Mustache markup {{#group-name}}Repeated content in here{{/group-name}}.
                                The last item in the group is tagged with a "last" property. Including an \'inverted section\' we can add a separator between the looped items, e.g. {{^last}},{{/last}}.
                                To loop all grade items in the category, use {{#items}}
                                <br><br>
                                Line breaks and spaces are preserved in the preview but removed from the calculation saved to the grade item. Use line breaks and spaces in your template to improve readability.
                                <br><br>
                                <strong>Example 1</strong>
                                <br>
                                Desired output                AND([[assignment1]] >= 50, [[assignment2]] >= 50, [[assignment3]] >= 50)
                                <br>
                                My template could be    AND({{#group-mod}}[[{{idnumber}}]]>={{gradepass}}{{^last}},{{/last}}{{/group-mod}})
                                <br><br>
                                <strong>Example 2</strong>
                                <br>
                                Desired output                [[assignment1]] * [[assignment2]] * [[assignment3]]
                                <br>
                                My template could be    {{#group-mod}}[[{{idnumber}}]]{{^last}}*{{/last}}{{/group-mod}}
                                <br><br>
                                See https://docs.moodle.org/en/Grade_calculations for info on grade calculations.</p>';
$string['pageheader']        = 'Grade calculation tool';
$string['placeholdercalc']   = 'Moodle calculation formula using mustache template markup. e.g.
=min({{#items}}[[{{idnumber}}]]{{^last}},{{/last}}{{/items}})';
$string['placeholderdescription'] = 'What is the rule for and what actions happen when it is applied?';
$string['placeholderidnum']  = 'uniqueidentifier';
$string['placeholdername']   = 'Give your rule a name';
$string['placeholderjson']   = '[]';
$string['property']          = 'Property';
$string['rule']              = 'Rule';
$string['ruleupdated']       = 'Rule updated';
$string['separator']         = 'Separator';
$string['set']               = 'Set';
$string['standardfields']    = '[{"title":{"identifier":"maxgrade","component":"core_grades"},"property":"grademax"}]';
$string['stringidentifier']  = 'Title or string identifier';
$string['stringcomponent']   = 'Blank or string component';
$string['template']          = 'Calculation template';
$string['template_help']     = 'The calculation template uses Mustache markup. mustache(5) - Logic-less templates.<br><br>
                                All grade item properties are available as variables, e.g. {{gradepass}}
                                Access this category\'s properties with dot syntax {{category.gradepass}}. <br>
                                Access grade items in this category with {{#items}}{{gradepass}}{{/items}}.<br><br>
                                See plugin settings for more info.';
$string['title']             = 'Title';
$string['to']                = 'To';
$string['when']              = 'When';
$string['wrongtype']         = 'The value \'{$a}\' is not of the expected type';

