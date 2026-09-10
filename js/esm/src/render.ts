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
 * Nothing Marp produces from an author's text is trusted: the HTML is sanitised and the
 * CSS is never taken from the author's render, see sanitize.ts.
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Marp} from '@mudeck/marp-core';
import {filterMarkdown, sanitizeHtml} from './sanitize';

export type RenderedPart = {
    html: string;
    css: string;
    /** Speaker notes of each slide - Marp hands us the comments it did not consume. */
    notes: string[];
};

/**
 * Which part a slide came from, and what that part held at the time.
 *
 * Device sync travels on this rather than on a slide number: an edit while the show is
 * running renumbers the deck, and the hash is how the other device notices.
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

/** Front matter at the very top of a deck, which is the only place Marp reads it. */
const FRONTMATTER = /^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/;

/** A theme the author chose for themselves, and what they called it. */
const OWNTHEME = /^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;

/**
 * Which theme the deck ends up with.
 *
 * The author's own when it names one this page can render, otherwise the presentation's.
 * A deck restored from another site, or one whose theme was deleted, names a theme that
 * is not here, and then the setting is what is left.
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
 * Give the deck its theme, without taking the author's front matter away.
 *
 * The theme has to travel as a directive, and a directive block only counts when
 * it is the first thing in the document - so ours goes *into* the author's front
 * matter when there is one. Putting a second block in front of theirs would turn
 * their "---" into a slide break: an empty first slide, and every directive they
 * wrote quietly ignored.
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
 * Render one part.
 *
 * @param markdown raw Marp Markdown
 * @param theme Marp theme name
 * @param themecss CSS of the themes shipped with the plugin, keyed by name
 * @returns slide HTML and the theme CSS
 */
export function renderPart(markdown: string, theme: string, themecss: Record<string, string> = {}): RenderedPart {
    const marp = new Marp({
        // No raw HTML from the author, ever.
        html: false,
        // The bundle ships without a maths engine, so a dollar sign in a slide is a dollar
        // sign - with maths on, marp reaches for the engine that is not there and throws.
        // Formulas are typeset by the site's own filter instead, see filters.ts.
        math: false,
        // Plain HTML sections rather than inline SVG, so screen readers get a sane reading order.
        inlineSVG: false,
        script: false,
        // Shortcodes become the character itself; nothing is fetched from an emoji CDN.
        emoji: {shortcode: true, unicode: false},
    });

    // Themes shipped with the plugin have to be registered before they can be named.
    Object.values(themecss).forEach((css) => {
        try {
            marp.themeSet.add(css);
        } catch (e) {
            window.console.error('[mudeck] could not add theme', e);
        }
    });

    // What Marp will answer to: the three it is born with, plus everything registered
    // above. A deck asking for anything else is asking for something that is not here.
    const known = ['default', 'gaia', 'uncover', ...Object.keys(themecss)];
    const safe = filterMarkdown(markdown);
    const chosen = chooseTheme(safe, theme, known);
    const {html, comments} = marp.render(withTheme(safe, chosen));

    // The stylesheet comes from a deck that holds nothing of the author's: the theme
    // alone. Whatever CSS Marp may have collected from the Markdown - style elements,
    // style directives, scoped styles - is never asked for.
    const {css} = marp.render(chosen ? `---\ntheme: ${chosen}\n---\n` : '');

    return {
        html: sanitizeHtml(html),
        css,
        notes: comments.map((slide) => slide.join('\n\n')),
    };
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
 * Each part is rendered on its own so that one part's directives cannot leak into
 * another, and only the slide markup is joined - never the Markdown.
 *
 * @param parts the parts in the order they are shown
 * @param themecss CSS of the themes shipped with the plugin, keyed by name
 * @returns the slides of the whole presentation and the CSS to go with them
 */
export function renderParts(parts: PartSource[], themecss: Record<string, string> = {}): RenderedDeck {
    const rendered = parts.map((part) => renderPart(part.markdown, part.theme, themecss));
    return {
        html: rendered.map((one) => one.html).join('\n'),
        // Every part uses the same theme set, so the first stylesheet covers them all.
        css: rendered.length ? rendered[0].css : '',
        notes: rendered.flatMap((one) => one.notes),
        origins: rendered.flatMap((one, index) => one.notes.map((_note, slide) => ({
            partid: parts[index].id,
            parthash: parts[index].hash,
            offset: slide + 1,
        }))),
    };
}
