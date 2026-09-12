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
 * Types for the pre-bundled Marp renderer and plugins shipped in js/vendor.
 *
 * @module     mod_mudeck/marp-core
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare module '@mudeck/marp-core' {
    /** What a plugin factory returns and marp.use() takes. */
    export type MarpPlugin = () => unknown;

    /** A markdown-it token as far as a plugin of our own reads it. */
    export type Token = {
        type: string;
        meta?: {marpitDirectives?: Record<string, unknown>};
        attrSet: (name: string, value: string) => void;
    };

    /** The slice of markdown-it a plugin of our own touches. */
    export type MarkdownIt = {
        marpit: {customDirectives: {local: Record<string, (value: unknown) => Record<string, unknown>>}};
        core: {ruler: {after: (after: string, name: string, rule: (state: {tokens: Token[]}) => void) => void}};
    };

    export class Marp {
        constructor(options?: Record<string, unknown>);
        use(plugin: unknown): this;
        customDirectives: {local: Record<string, unknown>};
        render(markdown: string): {html: string; css: string; comments: string[][]};
        themeSet: {add(css: string): unknown};
    }

    export const DOMPurify: {
        sanitize(dirty: string, config?: Record<string, unknown>): string;
        addHook(name: string, hook: (node: Element, data: {attrName: string; attrValue: string; keepAttr: boolean}) => void): void;
    };
}

declare module '@mudeck/marp-mathjax' {
    import type {MarpPlugin} from '@mudeck/marp-core';
    const plugin: MarpPlugin;
    export default plugin;
}

declare module '@mudeck/marp-shiki' {
    import type {MarpPlugin} from '@mudeck/marp-core';
    const plugin: MarpPlugin;
    export default plugin;
}

declare module '@mudeck/marp-mermaid' {
    import type {MarpPlugin} from '@mudeck/marp-core';
    const plugin: MarpPlugin;
    export default plugin;
}
