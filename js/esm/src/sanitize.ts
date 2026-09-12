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
 * Sanitise untrusted decks: the rendered HTML is cleaned here, and the CSS never comes
 * from the author's render (see render.ts). The Markdown filter is a feature allowlist,
 * not a security boundary.
 *
 * What the renderer emits, and how each piece is handled:
 *
 *  - HTML from Markdown: DOMPurify's HTML profile; raw HTML is off in Marp.
 *  - Inline styles: parsed by the browser's CSS engine, only listed properties with matching values are kept.
 *  - SVG from MathJax and Mermaid: DOMPurify's SVG profile, foreignObject and style elements forbidden.
 *  - MathJax's container: a custom element admitted by name with three attributes.
 *
 * @module     mod_mudeck/sanitize
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {DOMPurify} from '@mudeck/marp-core';

/**
 * Directives an author may use, in plain and spot (underscore) form.
 *
 * The style directive stays out because it carries CSS rules rather than values the style hook can check.
 */
const ALLOWEDDIRECTIVES = [
    'theme',
    'paginate',
    'header',
    'footer',
    'class',
    'backgroundColor',
    'backgroundImage',
    'backgroundPosition',
    'backgroundRepeat',
    'backgroundSize',
    'color',
    'transition',
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
 * Does the line look like a directive? Lines that do not are presenter notes.
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
        // A presenter note.
        return body;
    }

    const kept: string[] = [];
    // A dropped directive takes its indented continuation lines ("style: |") with it.
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
        // Still part of the dropped directive.
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

/** A theme variable the renderer's plugins colour by, with an optional fallback variable. */
const VAR = 'var\\(--marp-[a-z-]+(?:, var\\(--marp-[a-z-]+\\))?\\)';

/** A colour as the CSS engine writes it back: a keyword, a hex value, a function, or a theme variable. */
const COLOUR = `(?:[a-z]+|#[0-9a-f]{3,8}|(?:rgb|rgba|hsl|hsla)\\([0-9., %/]+\\)|${VAR})`;

/** A length, as a bare zero or a number with a unit. */
const LENGTH = '(?:0|-?\\d*\\.?\\d+(?:px|em|ex|rem|%|vw|vh))';

/** Inline style properties Marp or its plugins write, and the values each may take. */
const ALLOWEDSTYLES: Record<string, RegExp> = {
    // The url was percent-encoded by markdown-it; the scheme is checked separately below.
    'background-image': /^(?:none|url\("[^"'\\()\s]*"\))$/,
    'background-size': new RegExp(`^(?:cover|contain|auto|${LENGTH})(?: (?:auto|${LENGTH}))?$`),
    'background-position': new RegExp(
        `^(?:left|right|top|bottom|center|${LENGTH})(?: (?:left|right|top|bottom|center|${LENGTH}))?$`,
    ),
    'background-repeat': /^(?:repeat|no-repeat|repeat-x|repeat-y|space|round)(?: (?:repeat|no-repeat|space|round))?$/,
    'background-color': new RegExp(`^${COLOUR}$`),
    'color': new RegExp(`^${COLOUR}$`),
    'filter': /^(?:[a-z-]+\(\d*\.?\d+(?:px|%|deg)?\) ?)+$/,
    'font-style': /^(?:normal|italic)$/,
    'font-weight': /^(?:normal|bold|[1-9]00)$/,
    'text-decoration': new RegExp(
        `^(?:none|underline|line-through|overline)(?: (?:solid|double|dotted|dashed|wavy))?(?: ${COLOUR})?$`,
    ),
    'vertical-align': new RegExp(`^${LENGTH}$`),
    'display': /^(?:block|inline|inline-block)$/,
    'width': new RegExp(`^(?:auto|${LENGTH})$`),
    'min-width': new RegExp(`^(?:auto|${LENGTH})$`),
    'max-width': new RegExp(`^(?:none|${LENGTH})$`),
    'height': new RegExp(`^(?:auto|${LENGTH})$`),
    'max-height': new RegExp(`^(?:none|${LENGTH})$`),
    'margin-top': new RegExp(`^(?:auto|${LENGTH})$`),
    'margin-right': new RegExp(`^(?:auto|${LENGTH})$`),
    'margin-bottom': new RegExp(`^(?:auto|${LENGTH})$`),
    'margin-left': new RegExp(`^(?:auto|${LENGTH})$`),
};

/** A custom property is kept only when it hands on a theme variable, as Mermaid's palette does. */
const CUSTOMPROPERTY = /^--[a-z][a-z0-9_-]*$/;
const CUSTOMVALUE = new RegExp(`^${VAR}$`);

/** Where a background picture may come from: this site, another site, or the data itself. */
const ALLOWEDURL = /^url\("(?:https?:\/\/|data:image\/|[^:]*$)/i;

/** A scratch element whose CSSOM parses the styles. */
let probe: HTMLElement | null = null;

/**
 * Keep only the declarations in a style attribute that the renderer is allowed to make.
 *
 * @param value the attribute as the renderer wrote it
 * @return the declarations that may stay, possibly none
 */
const cleanStyle = (value: string): string => {
    probe = probe ?? document.createElement('span');
    probe.style.cssText = value;
    const kept: string[] = [];
    for (let i = 0; i < probe.style.length; i++) {
        const name = probe.style.item(i);
        const declared = probe.style.getPropertyValue(name).trim();
        if (CUSTOMPROPERTY.test(name)) {
            if (CUSTOMVALUE.test(declared)) {
                kept.push(`${name}:${declared}`);
            }
            continue;
        }
        const pattern = ALLOWEDSTYLES[name];
        if (!pattern || !pattern.test(declared)) {
            continue;
        }
        if (name === 'background-image' && declared !== 'none' && !ALLOWEDURL.test(declared)) {
            continue;
        }
        kept.push(`${name}:${declared}`);
    }
    probe.style.cssText = '';
    return kept.join(';');
};

/* A style attribute keeps only the listed declarations, or is removed. */
DOMPurify.addHook('uponSanitizeAttribute', (node: Element, data: {attrName: string; attrValue: string; keepAttr: boolean}) => {
    if (data.attrName !== 'style') {
        return;
    }
    data.attrValue = cleanStyle(data.attrValue);
    if (!data.attrValue) {
        data.keepAttr = false;
    }
});

/** Diagram text is 13px; this brings it close to slide text. */
const DIAGRAMSCALE = 2.2;

/** The room a diagram may take on a 1280x720 slide, leaving the theme's padding and a heading. */
const DIAGRAMMAXWIDTH = 1100;
const DIAGRAMMAXHEIGHT = 540;

/*
 * A diagram is drawn at its own pixel size, which is small on a slide. Its viewBox keeps
 * the proportions, so scaling is a matter of the width and height attributes.
 */
DOMPurify.addHook('afterSanitizeAttributes', (node: Element) => {
    if (node.tagName.toLowerCase() !== 'svg' || !node.hasAttribute('data-marp-mermaid')) {
        return;
    }
    const width = parseFloat(node.getAttribute('width') ?? '');
    const height = parseFloat(node.getAttribute('height') ?? '');
    if (!(width > 0) || !(height > 0)) {
        return;
    }
    const factor = Math.min(DIAGRAMSCALE, DIAGRAMMAXWIDTH / width, DIAGRAMMAXHEIGHT / height);
    node.setAttribute('width', (width * factor).toFixed(2));
    node.setAttribute('height', (height * factor).toFixed(2));
});

/*
 * Every link opens in a new tab, so a click during a talk never leaves the presentation.
 * The rel stops the new tab reaching back through window.opener.
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
        // HTML from Markdown, SVG from MathJax and Mermaid.
        USE_PROFILES: {html: true, svg: true},
        // MathJax's container element and its three attributes.
        CUSTOM_ELEMENT_HANDLING: {
            tagNameCheck: /^mjx-[a-z-]+$/,
            attributeNameCheck: /^(?:jax|display|overflow)$/,
            allowCustomizedBuiltInElements: false,
        },
        ADD_ATTR: ['focusable'],
        FORBID_TAGS: ['style', 'script', 'iframe', 'object', 'embed', 'form', 'base', 'link', 'meta', 'foreignobject'],
        FORBID_ATTR: ['srcdoc', 'formaction', 'ping'],
    });
}
