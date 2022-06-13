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
$string['calculation']  = 'Calculation';
$string['calculationupdated']  = 'Calculation updated.';
$string['cantupdate']   = 'The property \'{$a}\' cannot be updated.';
$string['categoryupdated']   = '{$a->changed} properties set / changed on item {$a->itemname}.';
$string['current']      = 'Current';
$string['eventgradereportviewed'] = 'Calc setup viewed.';
$string['eventgradeitemupdated']  = 'Grade item updated.';
$string['formulaerror'] = "There is an error in the calculation. Check the idnumbers and syntax.";
$string['missingtotal'] = 'Missing total name';
$string['nochanges']    = 'No rule changes made.';
$string['nodata']       = 'No data';
$string['norule']       = 'No rule applied';
$string['plugindescription']  = 'The aim of this tool is to help organisations apply grading methodologies consistently.<br><br>
                           Administrators create calculation \'rules\' which course developers apply to grade categories
                           within their courses. The rules can make changes to grade items when applied, describe a table
                           of grade item properties for convenient editing and provide a template to assist in writing
                           the calculation formulas. <a href=\'{$a}\'>Manage the rules</a>';
$string['standardfields'] = '[{"title":{"identifier":"maxgrade","component":"core_grades"},"property":"grademax"}]';
$string['pageheader']   = 'Grade calculation setup tool';
$string['rule']         = 'Rule';
$string['ruleupdated']  = 'Rule updated';
$string['wrongtype']    = 'The value \'{$a}\' is not of the expected type';

