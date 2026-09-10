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
 * Everything that keeps an untrusted deck harmless.
 *
 * Slides may be written by students, so nothing an author types is trusted, and
 * neither is what Marp makes of it. The rendered HTML is sanitised here before it
 * reaches the page, inline styles included; the CSS never comes from the author's
 * render at all (see render.ts). The Markdown filter in this module is an allowlist
 * of features, not a security boundary: it decides which directives a deck may use,
 * and it happens to throw away author CSS before Marp spends time on it.
 *
 * @module     mod_mudeck/sanitize
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {DOMPurify} from '@mudeck/marp-core';

/**
 * Directives an author may use.
 *
 * Anything else is removed. A leading underscore marks the spot form of a
 * directive (this slide only) and is allowed for the same keys. Colours and
 * backgrounds by directive are CSS in disguise, so they are not here: a slide
 * that wants a look picks a class from its theme, or uses a background image.
 */
const ALLOWEDDIRECTIVES = [
    'theme',
    'paginate',
    'header',
    'footer',
    'class',
    'marp',
];

/** Matches a whole style element, including anything it contains. */
const STYLEBLOCK = /<style\b[\s\S]*?<\/style\s*>/gi;

/** Matches an unterminated style element, so a truncated one cannot leak through. */
const STYLEOPEN = /<style\b[\s\S]*$/i;

/** Matches an HTML comment, which is how Marp carries directives and presenter notes. */
const COMMENT = /<!--([\s\S]*?)-->/g;

/**
 * Is this a directive line the author may keep?
 *
 * @param line one line from a comment or from the front matter
 * @return bool
 */
const isAllowedDirective = (line: string): boolean => {
    const match = line.match(/^\s*(_?)([A-Za-z][\w-]*)\s*:/);
    if (!match) {
        return false;
    }
    return ALLOWEDDIRECTIVES.includes(match[2]);
};

/**
 * Does the line look like a directive at all?
 *
 * Lines that are not directives are presenter notes and stay as they are.
 *
 * @param line
 * @return bool
 */
const looksLikeDirective = (line: string): boolean => /^\s*_?[A-Za-z][\w-]*\s*:/.test(line);

/**
 * Keep only allowed directives in a block of directive lines.
 *
 * @param body the inside of a comment or of the front matter
 * @return the filtered body
 */
const filterDirectiveBlock = (body: string): string => {
    const lines = body.split('\n');
    const directives = lines.filter(looksLikeDirective);
    if (!directives.length) {
        // No directives at all - this is a presenter note, leave it alone.
        return body;
    }

    const kept: string[] = [];
    // A directive can run over several lines: "style: |" carries a block of CSS beneath
    // it. Dropping its first line and leaving the rest behind turns valid front matter
    // into rubbish, and then the whole block is thrown away - theme and all.
    let dropped: number | null = null;
    for (const line of lines) {
        const indent = (line.match(/^[ \t]*/) ?? [''])[0].length;
        if (looksLikeDirective(line) && (dropped === null || indent <= dropped)) {
            dropped = isAllowedDirective(line) ? null : indent;
            if (dropped === null) {
                kept.push(line);
            }
            continue;
        }
        // Indented deeper, or empty: still part of the directive that was dropped.
        if (dropped !== null && (line.trim() === '' || indent > dropped)) {
            continue;
        }
        dropped = null;
        kept.push(line);
    }
    return kept.join('\n');
};

/**
 * Remove the directives an author may not use, and any CSS they wrote.
 *
 * @param markdown raw Markdown as written by the author
 * @return Markdown to hand to Marp
 */
export function filterMarkdown(markdown: string): string {
    let out = markdown.replace(STYLEBLOCK, '').replace(STYLEOPEN, '');

    // Front matter is a directive block too.
    const frontmatter = out.match(/^(\s*)---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/);
    if (frontmatter) {
        const filtered = filterDirectiveBlock(frontmatter[2]);
        out = out.replace(frontmatter[0], `${frontmatter[1]}---\n${filtered}\n---\n`);
    }

    // Comment directives, including the spot forms.
    out = out.replace(COMMENT, (whole, body) => `<!--${filterDirectiveBlock(body)}-->`);

    return out;
}

/**
 * The inline styles Marp is allowed to put on a slide, and what their values may be.
 *
 * Marp draws a background image as a figure with the picture in its style attribute,
 * and the bg keywords for size and blur go with it. Nothing else has any business in
 * a style attribute, and DOMPurify does not look inside one, so this does.
 */
const ALLOWEDSTYLES: Record<string, RegExp> = {
    // A single quoted url. What is inside came through markdown-it, which has already
    // percent-encoded quotes, backslashes and whitespace; the scheme is checked below.
    'background-image': /^url\("[^"'\\()\s]*"\)$/,
    'background-size': /^(?:cover|contain|auto|\d*\.?\d+(?:px|%)?)(?: (?:auto|\d*\.?\d+(?:px|%)?))?$/,
    'filter': /^(?:[a-z-]+\(\d*\.?\d+(?:px|%|deg)?\) ?)+$/,
};

/** Where a background picture may come from: this site, another site, or the data itself. */
const ALLOWEDURL = /^url\("(?:https?:\/\/|data:image\/|[^:]*$)/i;

/** A scratch element whose CSSOM parses the styles, so no regex has to. */
let probe: HTMLElement | null = null;

/**
 * Keep only the declarations in a style attribute that Marp needs for backgrounds.
 *
 * @param value the attribute as Marp wrote it
 * @return the declarations that may stay, possibly none
 */
const cleanStyle = (value: string): string => {
    probe = probe ?? document.createElement('span');
    probe.style.cssText = value;
    const kept: string[] = [];
    for (let i = 0; i < probe.style.length; i++) {
        const name = probe.style.item(i);
        const pattern = ALLOWEDSTYLES[name];
        if (!pattern) {
            continue;
        }
        const declared = probe.style.getPropertyValue(name).trim();
        if (!pattern.test(declared)) {
            continue;
        }
        if (name === 'background-image' && !ALLOWEDURL.test(declared)) {
            continue;
        }
        kept.push(`${name}:${declared}`);
    }
    probe.style.cssText = '';
    return kept.join(';');
};

/*
 * A style attribute keeps only the declarations listed above, or goes.
 */
DOMPurify.addHook('uponSanitizeAttribute', (node: Element, data: {attrName: string; attrValue: string; keepAttr: boolean}) => {
    if (data.attrName !== 'style') {
        return;
    }
    data.attrValue = cleanStyle(data.attrValue);
    if (!data.attrValue) {
        data.keepAttr = false;
    }
});

/*
 * Every link in a slide opens in a tab of its own.
 *
 * A link clicked during a talk must never take the presentation off the screen - and a
 * browser already in full screen opens the new tab full screen too, so a demonstration
 * can be shown and left without the room ever seeing a toolbar. The rel goes with it:
 * a tab opened this way has no business reaching back through window.opener.
 */
DOMPurify.addHook('afterSanitizeAttributes', (node: Element) => {
    if (node.tagName === 'A' && node.hasAttribute('href')) {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
    }
});

/**
 * Clean the HTML Marp produced before it is put into the page.
 *
 * @param html slide markup from marp-core
 * @return sanitised markup
 */
export function sanitizeHtml(html: string): string {
    return DOMPurify.sanitize(html, {
        // Marp emits plain sections with data-marpit-* attributes.
        ALLOW_DATA_ATTR: true,
        // Plain HTML only: with raw HTML off and inline SVG off, Marp has no SVG to emit.
        USE_PROFILES: {html: true},
        FORBID_TAGS: ['style', 'script', 'iframe', 'object', 'embed', 'form', 'base', 'link', 'meta'],
        FORBID_ATTR: ['srcdoc', 'formaction', 'ping'],
    });
}
