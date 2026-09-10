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

type Target = {
    url: string;
    sesskey: string;
};

/** The bit of core's global that tracks work a test has to wait for. */
type PendingRegistry = {
    js_pending?: (key: string) => void;
    js_complete?: (key: string) => void;
};

const registry = (): PendingRegistry | undefined =>
    (window as unknown as {M?: {util?: PendingRegistry}}).M?.util;

/**
 * Report reaching the end, once.
 *
 * Registered as pending with core synchronously, before anything is awaited, so that a
 * test waiting for the page to settle waits for this too - loading the pending module
 * first would leave a gap in which nothing looks pending and the page can be left.
 * Sent with keepalive so that leaving the page the moment the last slide shows does not
 * lose it. Failures are ignored: completion is checked again next time the user gets there.
 *
 * @param target where to post, and the session key that lets us
 */
export async function reportReachedEnd(target: Target): Promise<void> {
    const key = 'mod_mudeck/reachedend';
    registry()?.js_pending?.(key);
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
        registry()?.js_complete?.(key);
    }
}
