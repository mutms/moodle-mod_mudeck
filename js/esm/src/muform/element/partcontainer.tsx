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
 * Browser side of the part editor layout: tabs of the right pane and the slide preview.
 *
 * @module     mod_mudeck/muform/element/partcontainer
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Element from '@moodle/lms/tool_mulib/muform/element';
import type {FormApi} from '@moodle/lms/tool_mulib/muform/types';
import {mountReactApp} from '@moodle/lms/core/mount';
import Editor, {type EditorProps} from '../../editor';

export default class extends Element {
    /**
     * Wire the tabs and mount the preview.
     *
     * @param wrapper outer element with data-muform-* attributes
     * @param form the form API
     */
    constructor(wrapper: HTMLElement, form: FormApi) {
        super(wrapper, form);

        const tabs = Array.from(wrapper.querySelectorAll<HTMLButtonElement>('[data-mudeck-tab]'));
        const panes = Array.from(wrapper.querySelectorAll<HTMLElement>('[data-mudeck-pane]'));
        tabs.forEach((tab) => tab.addEventListener('click', () => {
            tabs.forEach((other) => {
                const active = other === tab;
                other.classList.toggle('active', active);
                other.setAttribute('aria-selected', String(active));
            });
            panes.forEach((pane) => {
                pane.hidden = pane.dataset.mudeckPane !== tab.dataset.mudeckTab;
            });
        }));

        // Children are found inside this element, html ids carry the form suffix.
        const textarea = wrapper.querySelector<HTMLTextAreaElement>('[data-muform-name="content"] textarea');
        const mount = wrapper.querySelector<HTMLElement>('[data-region="mudeck-editor-slides"]');
        if (!textarea || !mount) {
            return;
        }
        const config = JSON.parse(wrapper.dataset.mudeckEditor ?? '{}');
        const props: EditorProps = {
            ...config,
            textarea,
            caretstart: wrapper.querySelector<HTMLInputElement>('input[name="caretstart"]'),
            caretend: wrapper.querySelector<HTMLInputElement>('input[name="caretend"]'),
            mediapane: wrapper.querySelector<HTMLElement>('[data-mudeck-pane="media"]'),
        };
        mountReactApp(mount, Editor, props);
    }
}
