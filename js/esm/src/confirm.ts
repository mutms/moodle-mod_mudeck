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
 * Confirmation dialogue using Moodle's core/notification, with window.confirm as fallback.
 *
 * @module     mod_mudeck/confirm
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {requireAsync} from '@moodle/lms/core/amd';

type Notification = {
    saveCancelPromise: (title: string, question: string, save: string) => Promise<void>;
};

/**
 * Asks for confirmation before a destructive action.
 *
 * @param title heading of the question
 * @param question what is about to happen
 * @param save label of the button that goes ahead
 * @returns whether it was confirmed
 */
export async function confirmed(title: string, question: string, save: string): Promise<boolean> {
    let notification: Notification;
    try {
        notification = await requireAsync<Notification>('core/notification');
    } catch {
        // eslint-disable-next-line no-alert -- the fallback when core/notification cannot load
        return window.confirm(question);
    }

    try {
        await notification.saveCancelPromise(title, question, save);
        return true;
    } catch {
        // Cancelling rejects the promise.
        return false;
    }
}
