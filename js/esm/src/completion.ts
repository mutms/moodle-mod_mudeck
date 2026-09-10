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
 * Tell the server that the last slide was reached.
 *
 * @module     mod_mudeck/completion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {requireAsync} from '@moodle/lms/core/amd';

type Target = {
    url: string;
    sesskey: string;
};

type Pending = {
    resolve: () => void;
};

/**
 * Report reaching the end, once.
 *
 * Registered as pending with core so that a test, or anything else that waits for the
 * page to settle, waits for this too. Sent with keepalive so that leaving the page the
 * moment the last slide shows does not lose it. Failures are ignored: completion is
 * checked again the next time the user gets there.
 *
 * @param target where to post, and the session key that lets us
 */
export async function reportReachedEnd(target: Target): Promise<void> {
    let pending: Pending | null = null;
    try {
        const PendingPromise = await requireAsync<new (name: string) => Pending>('core/pending');
        pending = new PendingPromise('mod_mudeck/reachedend');
    } catch {
        pending = null;
    }
    try {
        await fetch(target.url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({sesskey: target.sesskey}),
            keepalive: true,
        });
    } catch {
        // Nothing to do; see above.
    } finally {
        pending?.resolve();
    }
}
