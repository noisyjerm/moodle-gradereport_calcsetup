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

export const init = () => {
    document.getElementById('rulestable').addEventListener('click', editrule);
};

/**
 * Decide which edit button was pressed
 * @param {Event} evt
 */
const editrule = (evt) => {
    let el = evt.target;
    if (el.nodeName === "I") {
        el = el.parentElement;
    } else if (el.nodeName !== "A") {
        return;
    }
    let cls = el.getAttribute('class');
    if (cls.includes('showhide')) {
        // Call showhide.
        hiderule(el);
    } else if (cls.includes('delete')) {
        // Show confirm.
        deleterule(el);
    } else {
        return;
    }
    evt.preventDefault();
};

/**
 * Handle UI request to hide a rule
 * @param {HTMLElement} el
 */
const hiderule = (el) => {
    let ruleid = el.dataset.ruleid || 0;
    let action = el.dataset.action;

    var reqStrings = [
        {'key': 'hide'},
        {'key': 'show'},
    ];
    var stringsPromise = Str.get_strings(reqStrings);
    stringsPromise.done(function(strings) {

        Ajax.call([{
            methodname: 'gradereport_calcsetup_hiderule',
            args: {
                'id': ruleid,
                'action': action
            },
            done: function(data) {
                if (data.success === false) {
                    return;
                }
                if (action == 1) {
                    let hide = strings[0];
                    el.innerHTML = "<i class='icon fa fa-eye fa-fw' title='" + hide + "' aria-label='" + hide + "'></i>";
                    el.dataset.action = 0;
                } else {
                    let show = strings[1];
                    el.innerHTML = "<i class='icon fa fa-eye-slash fa-fw' title='" + show + "' aria-label='" + show + "'></i>";
                    el.dataset.action = 1;
                }
            }
        }]);
    });
};

/**
 * Handle UI request to delete a rule
 * @param {HTMLElement} el
 * @return {*}
 */
const deleterule = (el) => {
    let ruleid = el.dataset.ruleid || 0;

    return ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        body: Str.get_string('deleterulereally', 'gradereport_calcsetup'),
        title: Str.get_string('deleterule', 'gradereport_calcsetup'),
        removeOnClose: true,
        buttons: {
            save: Str.get_string('yes'),
            cancel: Str.get_string('no'),
        }
    }).then(function(modal) {
        modal.getRoot().on(ModalEvents.save, function() {
            Ajax.call([{
                methodname: 'gradereport_calcsetup_deleterule',
                args: {
                    'id': ruleid
                },
                done: function(data) {
                    if (data.success === false) {
                        return false;
                    }
                    let row = el.parentNode.parentNode;
                    row.remove();
                    return true;
                }
            }]);
        });

        modal.show();
        return true;
    });
};
