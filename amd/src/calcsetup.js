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
 * @file       UI scripts for Grade Calculation Setup Tool.
 * @copyright  Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';
import * as ModalFactory from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as Str from 'core/str';
let pageurl = '';
export const init = (url) => {
    pageurl = url;
    document.getElementById('catselector').addEventListener('change', changeCategory);
    document.getElementById('newcalcview').addEventListener('click', showFormattedCalc);
};

const changeCategory = (e) => {
    let sel = e.target.selectedOptions;
    let cat = sel[0].value;
    document.location = pageurl + "&catid=" + cat;
};

const showFormattedCalc = (e) => {
    let contents = e.target.innerHTML;
    let itemid = e.target.dataset.id;
    let courseid = e.target.dataset.courseid;
    return ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        body: "<textarea class='calc-input' id='modifiedcalc'>" + contents + "</textarea>",
        title: Str.get_string('calculation', 'gradereport_calcsetup'),
        removeOnClose: true
    }).then(function(modal) {
        // Handle save event.
        modal.getRoot().on(ModalEvents.save, function(evt) {
            let textarea = document.getElementById('modifiedcalc');
            evt.isDefaultPrevented = function() { return true; };
            // Validate the formula.
            Ajax.call([{
                methodname: 'gradereport_calcsetup_updatecalc',
                args: {'id': itemid,
                    'courseid': courseid,
                    'formula': textarea.value
                },
                done: function(data) {
                    if (data.valid === true) {
                        // Apply changes and close dialog.
                        document.getElementById('newcalcview').innerHTML = textarea.value;
                        document.getElementById('newcalc').value = textarea.value.replace(/\n/g, '');
                        modal.hide();
                    } else {
                        // Warn of errors and leave dialog open.
                        if (textarea.previousElementSibling !== null) {
                               textarea.previousElementSibling.remove();
                        }
                        Str.get_string('formulaerror', 'gradereport_calcsetup').then(
                            function(s) {
                                let warning = document.createElement("p");
                                let body = textarea.parentNode;
                                warning.setAttribute('class', 'bad-calc');
                                warning.innerHTML = s;
                                body.insertBefore(warning, textarea);
                                return true;
                            }
                        );
                    }
                },
                fail: Notification.exception
            }]);

        });

        modal.getRoot().on(ModalEvents.hidden, function() {
            let textarea = document.getElementById('modifiedcalc');
            textarea.remove();
        });
        modal.show();
        return modal;
    });
};
