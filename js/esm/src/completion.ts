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

/** The part of M.util that tracks pending work for tests. */
type PendingRegistry = {
    js_pending?: (key: string) => void;
    js_complete?: (key: string) => void;
};

const registry = (): PendingRegistry | undefined =>
    (window as unknown as {M?: {util?: PendingRegistry}}).M?.util;

/**
 * Report reaching the end.
 *
 * Registered as pending synchronously so tests wait for it; sent with keepalive so leaving the page does not lose it.
 *
 * @param target where to post, and the session key
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
        // Ignored; completion is checked again next time.
    } finally {
        registry()?.js_complete?.(key);
    }
}
