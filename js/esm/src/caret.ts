// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Locates the caret of a text area by laying its text out again in a measurable mirror element.
 *
 * @module     mod_mudeck/caret
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Styles that affect where a character is laid out. */
const COPIED = [
    'font-family',
    'font-size',
    'font-weight',
    'font-style',
    'font-variant',
    'letter-spacing',
    'line-height',
    'text-indent',
    'text-transform',
    'word-spacing',
    'tab-size',
    'padding-top',
    'padding-right',
    'padding-bottom',
    'padding-left',
];

type Point = {
    left: number;
    top: number;
    height: number;
};

/**
 * Window coordinates of the character at an offset in a text area.
 *
 * @param textarea the field being written in
 * @param index how far into its text to look
 * @returns window coordinates of that spot, and how tall a line is there
 */
export function caretPoint(textarea: HTMLTextAreaElement, index: number): Point {
    const style = window.getComputedStyle(textarea);
    const mirror = document.createElement('div');

    COPIED.forEach((name) => mirror.style.setProperty(name, style.getPropertyValue(name)));
    // The width must match the text area's content width so lines wrap identically.
    mirror.style.position = 'absolute';
    mirror.style.top = '0';
    mirror.style.left = '-9999px';
    mirror.style.visibility = 'hidden';
    mirror.style.whiteSpace = 'pre-wrap';
    mirror.style.overflowWrap = 'break-word';
    mirror.style.boxSizing = 'content-box';
    const inside = textarea.clientWidth
        - parseFloat(style.paddingLeft || '0')
        - parseFloat(style.paddingRight || '0');
    mirror.style.width = `${Math.max(0, inside)}px`;

    mirror.textContent = textarea.value.slice(0, index);
    const spot = document.createElement('span');
    // The remaining text keeps the wrapping identical; an empty span at the end has no position.
    spot.textContent = textarea.value.slice(index) || '.';
    mirror.appendChild(spot);
    document.body.appendChild(mirror);

    const box = textarea.getBoundingClientRect();
    // Offsets are measured from inside the border, which the mirror does not have.
    const point = {
        left: box.left + parseFloat(style.borderLeftWidth || '0') + spot.offsetLeft - textarea.scrollLeft,
        top: box.top + parseFloat(style.borderTopWidth || '0') + spot.offsetTop - textarea.scrollTop,
        height: parseFloat(style.lineHeight || '0') || parseFloat(style.fontSize || '16') * 1.2,
    };

    mirror.remove();
    return point;
}
