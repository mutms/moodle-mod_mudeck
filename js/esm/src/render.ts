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
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * The HTML is sanitised and the CSS never comes from the author's render (sanitize.ts);
 * optional plugins are loaded on demand (plugins.ts).
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Marp, type MarkdownIt, type MarpPlugin} from '@mudeck/marp-core';
import {detectNeeds, loadPlugins, type Needs} from './plugins';
import {filterMarkdown, sanitizeHtml} from './sanitize';

export type RenderedPart = {
    html: string;
    css: string;
    /** Speaker notes of each slide, the comments Marp did not consume. */
    notes: string[];
};

/**
 * Which part a slide came from, and the hash of that part at the time.
 *
 * Device sync uses this rather than a slide number, because an edit during a show renumbers the deck.
 */
export type SlideOrigin = {
    partid: number;
    parthash: string;
    /** 1-based position of the slide within its own part. */
    offset: number;
};

export type RenderedDeck = RenderedPart & {
    origins: SlideOrigin[];
};

/** Front matter at the top of a deck, the only place Marp reads it. */
const FRONTMATTER = /^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/;

/** A theme directive written by the author. */
const OWNTHEME = /^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;

/**
 * Choose the theme: the author's own when this page can render it, otherwise the presentation's.
 *
 * @param markdown the author's Markdown, already filtered
 * @param theme the theme named by the presentation, may be empty
 * @param known every theme name this page can actually render
 * @returns the theme name to render with, empty for Marp's own default
 */
function chooseTheme(markdown: string, theme: string, known: string[]): string {
    const front = markdown.match(FRONTMATTER);
    const own = front?.[1].match(OWNTHEME)?.[1].trim();
    if (own && known.includes(own)) {
        return own;
    }
    return theme;
}

/**
 * Put the theme directive into the author's front matter.
 *
 * A second front matter block would turn the author's "---" into a slide break and lose their directives.
 *
 * @param markdown the author's Markdown, already filtered
 * @param theme the theme chosen for the deck, may be empty
 * @returns Markdown Marp will read the way the author meant it
 */
function withTheme(markdown: string, theme: string): string {
    if (!theme) {
        return markdown;
    }
    const front = markdown.match(FRONTMATTER);
    if (!front) {
        return `---\ntheme: ${theme}\n---\n\n${markdown}`;
    }
    if (OWNTHEME.test(front[1])) {
        return markdown.replace(front[0], `---\n${front[1].replace(OWNTHEME, `theme: ${theme}`)}\n---\n`);
    }
    return markdown.replace(front[0], `---\ntheme: ${theme}\n${front[1]}\n---\n`);
}

/**
 * Build the deck stylesheet from a stub document, so no author CSS is included.
 *
 * Plugins only emit CSS when used, so the stub holds one formula, fenced block and diagram as needed.
 *
 * @param marp renderer with the deck's theme and plugins already registered
 * @param theme the chosen theme name, empty for Marp's own default
 * @param needs which plugins are in play
 * @returns the CSS for the whole deck
 */
function stylesheet(marp: Marp, theme: string, needs: Needs): string {
    let stub = theme ? `---\ntheme: ${theme}\n---\n` : '';
    if (needs.math) {
        stub += '\n$x$\n';
    }
    if (needs.code) {
        stub += '\n```js\n1\n```\n';
    }
    if (needs.mermaid) {
        stub += '\n```mermaid\ngraph TD\n  A --> B\n```\n';
    }
    const {html, css} = marp.render(stub);
    return needs.mermaid ? css + '\n' + mermaidCss(html) : css;
}

/**
 * Extract the Mermaid style element, which is stripped from slides, and scope it to diagrams.
 *
 * @param html rendered stub holding one diagram
 * @returns CSS, possibly empty
 */
function mermaidCss(html: string): string {
    const found = html.match(/<svg data-marp-mermaid[^>]*>\s*<style>([\s\S]*?)<\/style>/);
    if (!found) {
        return '';
    }
    const rules = found[1]
        // No third-party web font import.
        .replace(/@import[^;]*;/g, '')
        // Root svg rules become rules for the scope.
        .replace(/(^|\n)[ \t]*svg[ \t]*\{/g, '$1& {');
    return `svg[data-marp-mermaid] {\n${rules}\n}`;
}

/** Transition kinds the presenter knows; anything else is dropped. */
const TRANSITIONS = ['fade', 'slide', 'none'];

/**
 * The transition directive, as Marp CLI spells it: a slide's data-transition attribute.
 *
 * Marpit reads custom directives at parse time but lists the ones it applies at start,
 * so the attribute is set by a rule of our own after Marpit's own apply step.
 *
 * @param md markdown-it instance with marpit attached
 */
const transitionDirective = (md: MarkdownIt): void => {
    md.marpit.customDirectives.local.transition = (value: unknown) => ({
        transition: TRANSITIONS.includes(String(value)) ? String(value) : undefined,
    });
    md.core.ruler.after('marpit_directives_apply', 'mudeck_transition', (state) => {
        for (const token of state.tokens) {
            const transition = token.meta?.marpitDirectives?.transition;
            if (token.type === 'marpit_slide_open' && typeof transition === 'string') {
                token.attrSet('data-transition', transition);
            }
        }
    });
};

/**
 * Create a renderer with the deck's options, plugins and site themes registered.
 *
 * @param plugins the plugin factories the deck needs
 * @param themecss CSS of the themes shipped with the plugin, keyed by name
 * @returns a Marp instance, and the theme names it will answer to
 */
function createMarp(plugins: MarpPlugin[], themecss: Record<string, string>): {marp: Marp; known: string[]} {
    const marp = new Marp({
        // Never raw HTML from the author.
        html: false,
        // Typeset only when the MathJax plugin is loaded.
        math: true,
        // Plain sections rather than inline SVG, for screen reader reading order.
        inlineSVG: false,
        script: false,
        // Heading ids from different parts would collide on one page.
        slug: false,
        // No emoji images from a CDN.
        emoji: {shortcode: true, unicode: false},
    });
    plugins.forEach((plugin) => marp.use(plugin()));
    marp.use(transitionDirective);

    // Site themes must be registered before they can be named.
    Object.values(themecss).forEach((css) => {
        try {
            marp.themeSet.add(css);
        } catch (e) {
            window.console.error('[mudeck] could not add theme', e);
        }
    });

    // Marp's three built-in themes plus the registered ones.
    return {marp, known: ['default', 'gaia', 'uncover', ...Object.keys(themecss)]};
}

type RenderedWithNeeds = RenderedPart & {needs: Needs; theme: string};

/**
 * Render one part, remembering what it needed.
 *
 * @param markdown raw Marp Markdown
 * @param theme Marp theme name
 * @param themecss CSS of the themes shipped with the plugin, keyed by name
 */
async function renderOne(markdown: string, theme: string, themecss: Record<string, string>): Promise<RenderedWithNeeds> {
    const safe = filterMarkdown(markdown);
    let needs = detectNeeds(safe);
    let {marp, known} = createMarp(await loadPlugins(needs), themecss);
    const chosen = chooseTheme(safe, theme, known);
    let rendered: {html: string; comments: string[][]};
    try {
        rendered = marp.render(withTheme(safe, chosen));
    } catch (e) {
        // A plugin failed on this deck: show the slides plain rather than nothing.
        window.console.error('[mudeck] plugin rendering failed, showing plain slides', e);
        needs = {math: false, code: false, mermaid: false};
        ({marp, known} = createMarp([], themecss));
        rendered = marp.render(withTheme(safe, chosen));
    }
    const {html, comments} = rendered;

    return {
        html: sanitizeHtml(html),
        // Never the CSS of the author's render.
        css: stylesheet(marp, chosen, needs),
        notes: comments.map((slide) => slide.join('\n\n')),
        needs,
        theme: chosen,
    };
}

/**
 * Render one part.
 *
 * @param markdown raw Marp Markdown
 * @param theme Marp theme name
 * @param themecss CSS of the themes shipped with the plugin, keyed by name
 * @returns slide HTML and the theme CSS
 */
export async function renderPart(markdown: string, theme: string, themecss: Record<string, string> = {}): Promise<RenderedPart> {
    const {html, css, notes} = await renderOne(markdown, theme, themecss);
    return {html, css, notes};
}

export type PartSource = {
    id: number;
    hash: string;
    markdown: string;
    theme: string;
};

/**
 * Render every part of a presentation into one deck.
 *
 * Parts are rendered separately so directives cannot leak between them; only the slide markup is joined.
 *
 * @param parts the parts in the order they are shown
 * @param themecss CSS of the themes shipped with the plugin, keyed by name
 * @returns the slides of the whole presentation and the CSS to go with them
 */
export async function renderParts(parts: PartSource[], themecss: Record<string, string> = {}): Promise<RenderedDeck> {
    const rendered = await Promise.all(parts.map((part) => renderOne(part.markdown, part.theme, themecss)));
    const needs: Needs = {
        math: rendered.some((one) => one.needs.math),
        code: rendered.some((one) => one.needs.code),
        mermaid: rendered.some((one) => one.needs.mermaid),
    };
    // The first part's theme stands for the deck.
    const {marp} = createMarp(await loadPlugins(needs), themecss);
    return {
        html: rendered.map((one) => one.html).join('\n'),
        css: rendered.length ? stylesheet(marp, rendered[0].theme, needs) : '',
        notes: rendered.flatMap((one) => one.notes),
        origins: rendered.flatMap((one, index) => one.notes.map((_note, slide) => ({
            partid: parts[index].id,
            parthash: parts[index].hash,
            offset: slide + 1,
        }))),
    };
}
