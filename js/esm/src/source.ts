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
 * Find where each slide sits in the Markdown. The rules match
 * `mod_mudeck\local\part::count_slides()` on the server; keep the two in step.
 *
 * @module     mod_mudeck/source
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Front matter, which is not part of the first slide. */
const FRONTMATTER = /^\s*---\r?\n[\s\S]*?\r?\n---[ \t]*(\r?\n|$)/;

/** A fence line, opening or closing a code block. */
const FENCE = /^[ \t]{0,3}(\x60{3,}|~{3,})/;

/** A line of nothing but dashes, underscores or stars. */
const RULE = /^[ \t]{0,3}(-{3,}|_{3,}|\*{3,})[ \t]*$/;

/** A line that dashes below it cannot turn into a setext heading. */
const UNDERLINABLE = /^[ \t]{0,3}([#>|]|[-*+][ \t]|\d+[.)][ \t])/;

/**
 * Is this line a slide break, given what came before it?
 *
 * @param line the line as written
 * @param previousblank the line above was blank, or something dashes cannot underline
 * @param fence the code fence currently open, or null
 * @returns true when Marp starts a new slide here
 */
export function isBreak(line: string, previousblank: boolean, fence: string | null): boolean {
    return fence === null && previousblank && RULE.test(line);
}

/**
 * Does a line leave the next one free to be a slide break?
 *
 * @param line the line as written
 * @returns true when it is blank, or a marker that dashes below it cannot underline
 */
export function opensBreak(line: string): boolean {
    return line.trim() === '' || UNDERLINABLE.test(line);
}

/**
 * The fence a line opens or closes, given the one already open.
 *
 * @param line the line as written
 * @param fence the fence currently open, or null
 * @returns the fence open after this line
 */
function fenceAfter(line: string, fence: string | null): string | null {
    const match = line.match(FENCE);
    if (!match) {
        return fence;
    }
    const marker = match[1].slice(0, 3);
    if (fence === null) {
        return marker;
    }
    return fence === marker ? null : fence;
}

/**
 * Where every slide break line starts.
 *
 * @param text the Markdown as written
 * @returns offsets into the text, in order, one per break
 */
export function slideBreaks(text: string): number[] {
    const front = text.match(FRONTMATTER);
    // Skipped rather than removed, so offsets point into the author's own text.
    let at = front ? front[0].length : 0;

    const breaks: number[] = [];
    let fence: string | null = null;
    let previousblank = true;

    while (at <= text.length) {
        const newline = text.indexOf('\n', at);
        const end = newline === -1 ? text.length : newline;
        const line = text.slice(at, end).replace(/\r$/, '');

        const opened = fenceAfter(line, fence);
        if (opened !== fence) {
            fence = opened;
            previousblank = false;
        } else if (isBreak(line, previousblank, fence)) {
            breaks.push(at);
            previousblank = false;
        } else {
            previousblank = opensBreak(line);
        }

        if (newline === -1) {
            break;
        }
        at = newline + 1;
    }

    return breaks;
}

/**
 * The cursor offset for a slide: its end, on the blank line before the break.
 *
 * @param text the Markdown as written
 * @param slide 1-based slide number, as the preview strip numbers them
 * @returns an offset into the text
 */
export function slideEnd(text: string, slide: number): number {
    const breaks = slideBreaks(text);
    // Clamped rather than refused, in case this disagrees with Marp about a deck.
    const wanted = Math.min(Math.max(slide, 1), breaks.length + 1);
    if (wanted > breaks.length) {
        return text.length;
    }

    const at = breaks[wanted - 1];
    // Step back onto the blank line above the break, or the end of the last content line.
    return at > 0 && text.charAt(at - 1) === '\n' ? at - 1 : at;
}
