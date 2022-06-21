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

import * as ModalFactory from "core/modal_factory";
import * as ModalEvents from "core/modal_events";
import * as Str from "core/str";
import * as Templates from "core/templates";
import Notification from "core/notification";
import * as Ajax from "core/ajax";

export const init = () => {
    document.querySelector(".rule-fields").parentElement.addEventListener("click", editrule);
    document.querySelector(".rule-cols").parentElement.addEventListener("click", editrule);
};

/**
 * Decide which edit button was pressed
 * @param {Event} evt
 */
const editrule = (evt) => {
    let el = evt.target;

    if (el.nodeName !== "I") {
        return;
    }
    evt.preventDefault();

    let row = el.closest("div");
    let container = row.parentNode;
    let index = parseInt(row.dataset.index);
    var field = document.querySelector("input[name=" + row.dataset.targetele + "]");
    let columns = JSON.parse(field.value);
    let action = el.dataset.action;

    if (action === "up") {
        let oldfield = columns[index - 1];
        columns[index - 1] = columns[index];
        columns[index] = oldfield;

        row.dataset.index = index - 1;
        row.previousSibling.dataset.index = index;
        container.insertBefore(row, row.previousSibling);

        field.value = JSON.stringify(columns);
        return;
    }

    if (action === "down") {
        let oldfield = columns[index + 1];
        columns[index + 1] = columns[index];
        columns[index] = oldfield;

        row.dataset.index = index + 1;
        row.nextSibling.dataset.index = index;
        container.insertBefore(row.nextSibling, row);

        field.value = JSON.stringify(columns);
        return;
    }

    if (action === "delete") {
        columns.splice(index, 1);
        for (var i = index; i < container.children.length; i++) {
            container.children[i].dataset.index = container.children[i].dataset.index - 1;
        }
        row.remove();
        field.value = JSON.stringify(columns);
        return;
    }

    if (action === "edit") {
        return ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            body: "",
            title: Str.get_string("editfield", "gradereport_calcsetup"),
            removeOnClose: true
        }).then(function(modal) {
            modal.getRoot().on(ModalEvents.save, function() {
                if (typeof columns[index] === 'undefined') {
                    columns[index] = {};
                    let addrow = row.parentElement.appendChild(row.cloneNode(true));
                    addrow.dataset.index = index + 1;
                    row.querySelector("span:last-of-type").innerHTML =
                        "<a class='up' href='#'>" +
                        "<i class='icon fa fa-arrow-up fa-fw ' title='$strup' aria-label='$strup' data-action='up'></i>" +
                        "</a><a class='down' href='#'>" +
                        "<i class='icon fa fa-arrow-down fa-fw ' title='$strdn' aria-label='$strdn' data-action='down'></i>" +
                        "</a><a class='delete' href='#'>" +
                        "<i class='icon fa fa-trash fa-fw ' title='$strdel' aria-label='$strdel' data-action='delete'></i>" +
                        "</a><a class='edit' href='#'>" +
                        "<i class='icon fa fa-cog fa-fw ' title='$stredit' aria-label='$stredit' data-action='edit'></i></a>";
                }
                var reqStrings = [
                    {"key": "editable", "component": "gradereport_calcsetup"},
                    {"key": "locked", "component": "gradereport_calcsetup"}
                ];

                // Set the data.
                columns[index].title = tryParseJSONObject(document.getElementById("coltitle").value);
                columns[index].property = document.getElementById("colproperty").value;
                columns[index].editable = document.getElementById("coleditable").checked;
                // Set the dom.
                if (typeof columns[index].title === "object") {
                    reqStrings.push({
                        "key": columns[index].title.identifier,
                        "component": columns[index].title.component
                    });
                }

                var stringsPromise = Str.get_strings(reqStrings);
                stringsPromise.done(function(strings) {
                    row.querySelector("span:first-of-type").innerHTML = typeof columns[index].title === "object"
                        ? strings[2]
                        : columns[index].title;
                    row.querySelector("span:nth-of-type(2)").innerHTML = columns[index].property;
                    row.querySelector("span:nth-of-type(3)").innerHTML = columns[index].editable
                        ? strings[0]
                        : strings[1];
                });
                field.value = JSON.stringify(columns);
            });
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
            Ajax.call([{
                methodname: "gradereport_calcsetup_get_corefields",
                args: {},
                done: function(data) {
                    data.col = columns[index];
                    let stringsPromise = Str.get_string("free", "gradereport_calcsetup");
                    stringsPromise.done(function(string) {
                        let l = data.fields.push({"property": "free"});
                        if (typeof data.col !== 'undefined') {
                            data.exists = true;
                            for (var i = 0; i < l; i++) {
                                if (data.fields[i].property == data.col.property) {
                                    data.fields[i].selected = true;
                                    break;
                                }
                            }
                            if (i === l) {
                                data.fields.push({"property": data.col.property, "selected": true});
                            }

                            data.title = typeof columns[index].title === "object"
                                ? JSON.stringify(columns[index].title)
                                : columns[index].title;
                        }
                        // Replace the last item "free" with a readable name.
                        data.fields[l-1].property = string;
                        data.fields[l-1].value = "free";
                        Templates.renderForPromise("gradereport_calcsetup/editfield", data)
                            .then(({html, js}) => {
                                Templates.appendNodeContents(".modal-body", html, js);
                                document.getElementById("colproperty").addEventListener("change", function (e) {
                                    if (e.target.value === "free") {
                                        let input = document.createElement("input");
                                        input.setAttribute("id", e.target.getAttribute("id"));
                                        e.target.parentElement.insertBefore(input, e.target);
                                        e.target.remove();
                                    }
                                });
                                return true;
                            })
                            .catch(Notification.exception);
                    });
                    modal.show();
                }
            }]);

            return true;
        });
        evt.preventDefault();
    }
};

const tryParseJSONObject = (jsonString) => {
    try {
        var o = JSON.parse(jsonString);
        if (o && typeof o === "object") {
            return o;
        }
    }
    catch (e) { }

    return jsonString;
};