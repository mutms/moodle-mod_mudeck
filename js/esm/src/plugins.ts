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
 * Optional Marp plugins (maths, code colouring, diagrams), loaded on demand and cached once per page.
 *
 * @module     mod_mudeck/plugins
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {MarpPlugin} from '@mudeck/marp-core';

/** What a deck asks for. */
export type Needs = {
    math: boolean;
    code: boolean;
    mermaid: boolean;
};

/** A fenced block with a language, which needs colouring. */
const FENCEWITHLANG = /^[ \t]{0,3}(?:\x60{3,}|~{3,})[ \t]*[A-Za-z]/m;

/** A mermaid fence. */
const MERMAIDFENCE = /^[ \t]{0,3}(?:\x60{3,}|~{3,})[ \t]*mermaid\b/m;

/**
 * Detect which plugins the Markdown needs. A false positive costs only a download.
 *
 * @param markdown the deck, already filtered
 * @returns which plugins to load
 */
export function detectNeeds(markdown: string): Needs {
    const mermaid = MERMAIDFENCE.test(markdown);
    return {
        math: markdown.includes('$'),
        code: FENCEWITHLANG.test(markdown),
        mermaid,
    };
}

const loaders: Record<keyof Needs, () => Promise<{default: MarpPlugin}>> = {
    math: () => import('@mudeck/marp-mathjax'),
    code: () => import('@mudeck/marp-shiki'),
    mermaid: () => import('@mudeck/marp-mermaid'),
};

const loaded: Partial<Record<keyof Needs, Promise<MarpPlugin>>> = {};

/**
 * Load the plugin factories a deck needs, in parallel and cached. A bundle that fails to load is reported and left out.
 *
 * @param needs what the deck asked for
 * @returns the factories to register with marp.use()
 */
export async function loadPlugins(needs: Needs): Promise<MarpPlugin[]> {
    const wanted = (Object.keys(loaders) as (keyof Needs)[]).filter((name) => needs[name]);
    const results = await Promise.all(wanted.map((name) => {
        loaded[name] = loaded[name] ?? loaders[name]().then((module) => module.default);
        return loaded[name].catch((e: unknown) => {
            window.console.error(`[mudeck] could not load the ${name} plugin`, e);
            delete loaded[name];
            return null;
        });
    }));
    return results.filter((plugin): plugin is MarpPlugin => plugin !== null);
}
