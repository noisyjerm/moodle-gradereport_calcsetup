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

import * as ModalFactory from 'core/modal_factory';
let pageurl = '';
export const init = (url) => {
    pageurl = url;
    document.getElementById('catselector').addEventListener('change', changeCategory);
    document.getElementById('newcalc').addEventListener('click', showFormattedCalc);
};

const changeCategory = (e) => {
    let sel = e.target.selectedOptions;
    let cat = sel[0].value;
    document.location = pageurl + "&catid=" + cat;
};

const showFormattedCalc = (e) => {
    let contents = e.target.innerHTML;
    return ModalFactory.create({
        type: ModalFactory.types.ALERT,
        body: "<pre>" + contents + "</pre>",
        title: "cacl",
        removeOnClose: false,
    }).then(modal => {
        modal.show();
        return modal;
    });
};