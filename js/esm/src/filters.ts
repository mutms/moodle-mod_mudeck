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
 * Hand freshly rendered slides to Moodle's filters.
 *
 * Slides are built in the browser, so nothing on the server ever sees them and the site's
 * filters never run. Telling the filter subsystem about the new nodes is what makes maths
 * work: the deck carries the TeX the teacher typed, and the site's own MathJax typesets it
 * with the site's own settings. No second maths engine is shipped with this plugin.
 *
 * @module     mod_mudeck/filters
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {requireAsync} from '@moodle/lms/core/amd';

type FilterEvents = {
    notifyFilterContentUpdated: (nodes: Node[]) => void;
};

/**
 * Ask the site's filters to process the slides that were just put on the page.
 *
 * Filters are a site setting, so this is best effort by design: a site with MathJax off
 * shows the TeX as written, which is the same thing that happens anywhere else in Moodle.
 *
 * @param node element holding the rendered slides
 */
export async function filterSlides(node: HTMLElement): Promise<void> {
    try {
        const events = await requireAsync<FilterEvents>('core_filters/events');
        events.notifyFilterContentUpdated([node]);
    } catch (e) {
        window.console.error('[mudeck] could not notify the filters', e);
    }
}
