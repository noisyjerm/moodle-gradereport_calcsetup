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
    document.querySelector(".rule-actions").parentElement.addEventListener("click", editaction);
    document.querySelector('.loophelper').addEventListener('click', loopHelper);
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
        for (let i = index; i < container.children.length; i++) {
            container.children[i].dataset.index = container.children[i].dataset.index - 1;
        }
        row.remove();
        field.value = JSON.stringify(columns);
        return;
    }

    if (action === "edit") {
        let title = row.dataset.targetele === 'fields'
                   ? Str.get_string("editfield", "gradereport_calcsetup")
                   : Str.get_string("editcols", "gradereport_calcsetup");

        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            body: "",
            title: title,
            removeOnClose: true
        }).then(function(modal) {
            modal.getRoot().on('change', swapSelect);
            modal.getRoot().on(ModalEvents.save, {columns: columns, row: row, index: index, field: field}, saveFields);
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
            Ajax.call([{
                methodname: "gradereport_calcsetup_get_corefields",
                args: {
                    'editableonly': false
                },
                done: function(data) {
                    data.col = columns[index];
                    let stringsPromise = Str.get_string("free", "gradereport_calcsetup");
                    stringsPromise.done(function(string) {
                        data.exists = index < columns.length;
                        let prop = data.exists ? data.col.property : null;
                        data.fields = getCorefields(data.fields, prop, string);
                        if (typeof data.col !== 'undefined') {
                            data.title = typeof columns[index].title === "object"
                                ? JSON.stringify(columns[index].title)
                                : columns[index].title;
                        }
                        Templates.renderForPromise("gradereport_calcsetup/editfield", data)
                            .then(({html, js}) => {
                                Templates.appendNodeContents(".modal-body", html, js);
                                return true;
                            })
                            .catch(Notification.exception);
                    });
                    modal.show();
                }
            }]);

            return true;
        });
    }
};

const editaction = (evt) => {
    let el = evt.target;

    if (el.nodeName !== "I") {
        return;
    }
    evt.preventDefault();

    let row = el.closest("div");
    let container = row.parentNode;
    let index = parseInt(row.dataset.index);
    var field = document.querySelector("input[name=actions]");
    let actions = JSON.parse(field.value);
    let action = el.dataset.action;

    if (action === 'edit') {
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            body: "",
            title: Str.get_string("editaction", "gradereport_calcsetup"),
            removeOnClose: true
        }).then(function(modal) {
            modal.getRoot().on('change', swapSelect);
            modal.getRoot().on(ModalEvents.save, function() {
                if (index === actions.length) {
                    actions.push({'op': 'equals'});
                    let addrow = row.parentElement.appendChild(row.cloneNode(true));
                    addrow.dataset.index = index + 1;
                    row.querySelector('span:last-of-type').innerHTML = '<a href="#">' +
                        '<i class="icon fa fa-trash fa-fw" title="Delete" aria-label="Delete" data-action="delete"></i></a>' +
                        '<a href="#" ><i class="icon fa fa-cog fa-fw" title="Edit" aria-label="Edit" data-action="edit"></i></a>';
                }

                actions[index].set = document.getElementById("actionset").value;
                actions[index].to = document.getElementById("actionto").value;
                actions[index].when = document.getElementById("actionwhen").value;
                actions[index].val = document.getElementById("actionval").value;
                field.value = JSON.stringify(actions);

                // First get operator string
                let operator = Str.get_string(actions[index].op, 'gradereport_calcsetup', actions[index]);
                operator.done(function(string) {
                    actions[index].op = string;
                    let description = Str.get_string('action', 'gradereport_calcsetup', actions[index]);
                    description.done(function(string) {
                        row.querySelector('span:first-of-type').innerHTML = string;
                    });
                });

            });
            Ajax.call([{
                methodname: "gradereport_calcsetup_get_corefields",
                args: {
                    'editableonly': true
                },
                done: function(data) {
                    let stringsPromise = Str.get_string("free", "gradereport_calcsetup");
                    stringsPromise.done(function(string) {
                        let actionrow = {};
                        actionrow.action = index < actions.length ? actions[index] : [];
                        actionrow.exists = index < actions.length;
                        actionrow.fields = getCorefields(data.fields, actionrow.exists ? actions[index].set : null, string);
                        actionrow.fieldswhen = getCorefields(data.fields, actionrow.exists ? actions[index].when : null, string);
                        Templates.renderForPromise("gradereport_calcsetup/editaction", actionrow)
                            .then(({html, js}) => {
                                Templates.appendNodeContents(".modal-body", html, js);
                                return true;
                            })
                            .catch(Notification.exception);
                    });

                    modal.show();
                }
            }]);
            return true;
        });

    }

    if (action === 'delete') {
        actions.splice(index, 1);
        for (var i = index; i < container.children.length; i++) {
            container.children[i].dataset.index = container.children[i].dataset.index - 1;
        }
        row.remove();
        field.value = JSON.stringify(actions);
    }
};

const swapSelect = (e) => {
    if (e.target.value === "free") {
        let input = document.createElement("input");
        input.setAttribute("id", e.target.getAttribute("id"));
        e.target.parentElement.insertBefore(input, e.target);
        e.target.remove();
    }
};

const saveFields = (e) => {
    let columns = e.data.columns;
    let row = e.data.row;
    let index = e.data.index;
    let field = e.data.field;

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
    let reqStrings = [
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

    let stringsPromise = Str.get_strings(reqStrings);
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
};

const tryParseJSONObject = (jsonString) => {
    try {
        var o = JSON.parse(jsonString);
        if (o && typeof o === "object") {
            return o;
        }
    } catch (e) {
        // Do nothing.
    }

    return jsonString;
};

const getCorefields = (data, property, string) => {
    // We need a deep clone.
    let dataString = JSON.stringify(data);
    let giProperties = JSON.parse(dataString);
    let l = giProperties.push({"property": "free"});
    if (typeof property == 'string') {
        var i;
        for (i = 0; i < l; i++) {
            if (giProperties[i].property === property) {
                giProperties[i].selected = true;
                break;
            }
        }
        if (i === l) {
            giProperties.push({"property": property, "selected": true});
        }
    }

    // Replace the last item "free" with a readable name.
    giProperties[l - 1].property = string;
    giProperties[l - 1].value = "free";
    return giProperties;
};

const loopHelper = (evt) => {
    evt.preventDefault();
    return ModalFactory.create({
        type: ModalFactory.types.DEFAULT,
        body: "",
        title: Str.get_string('loophelper', 'gradereport_calcsetup'),
        removeOnClose: false
    }).then(function(modal) {
        modal.getRoot().on('change', updateCalc);
        Ajax.call([{
            methodname: "gradereport_calcsetup_get_corefields",
            args: {
                'editableonly': false
            },
            done: function(data) {
                Templates.renderForPromise("gradereport_calcsetup/looper", data)
                    .then(({html, js}) => {
                        Templates.appendNodeContents(".modal-body", html, js);
                        let copyBtn = modal.getRoot()[0].querySelector('.copyme');
                        if (navigator.clipboard) {
                            copyBtn.addEventListener('click', function (e) {
                                let copyText = e.target.previousElementSibling;
                                copyText.select();
                                copyText.setSelectionRange(0, 99999);
                                navigator.clipboard.writeText(copyText.value);
                            });
                        } else {
                            copyBtn.setAttribute("style", "display:none");
                        }
                        return true;
                    })
                    .catch(Notification.exception);
            }}]);
        modal.show();
        return true;
    });
};

const updateCalc = (evt) => {
    let container = evt.target.closest('.modal-body');
    let group = container.querySelector('#loopgroup').value;
    let contentInput = container.querySelector('#loopcontent');
    let separator = container.querySelector('#loopseparator').value;

    if (evt.target.getAttribute('id') === 'loopproperty') {
        contentInput.value = contentInput.value + "{{" + evt.target.value + "}}";
    }

    if (evt.target.getAttribute('id') === '') {
        return true;
    } else {
        container.querySelector('.output').value =
            "{{#" + group + "}}" + contentInput.value + "{{^last}}" + separator + "{{/last}}{{/" + group + "}}";
    }

    return true;
};