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
 * The languages the code highlighter knows, and nothing else.
 *
 * marp-core resolves "#marp-shiki" to a module holding every grammar Shiki has, which
 * is several megabytes. This stands in for it with the languages a teaching deck is
 * likely to show; a fence in any other language renders as plain code. The shape is
 * what marp-core's Shiki plugin reads: a lazily built highlighter and a lookup from a
 * language name to a loader.
 */

import {createCssVariablesTheme, createHighlighterCoreSync} from 'shiki/core';
import {createJavaScriptRegexEngine} from 'shiki/engine/javascript';

import bash from 'shiki/langs/shellscript.mjs';
import c from 'shiki/langs/c.mjs';
import cpp from 'shiki/langs/cpp.mjs';
import csharp from 'shiki/langs/csharp.mjs';
import css from 'shiki/langs/css.mjs';
import diff from 'shiki/langs/diff.mjs';
import go from 'shiki/langs/go.mjs';
import html from 'shiki/langs/html.mjs';
import java from 'shiki/langs/java.mjs';
import javascript from 'shiki/langs/javascript.mjs';
import json from 'shiki/langs/json.mjs';
import kotlin from 'shiki/langs/kotlin.mjs';
import markdown from 'shiki/langs/markdown.mjs';
import mermaid from 'shiki/langs/mermaid.mjs';
import php from 'shiki/langs/php.mjs';
import python from 'shiki/langs/python.mjs';
import ruby from 'shiki/langs/ruby.mjs';
import rust from 'shiki/langs/rust.mjs';
import sql from 'shiki/langs/sql.mjs';
import swift from 'shiki/langs/swift.mjs';
import typescript from 'shiki/langs/typescript.mjs';
import xml from 'shiki/langs/xml.mjs';
import yaml from 'shiki/langs/yaml.mjs';

const langLoaders = {
    shellscript: () => bash,
    c: () => c,
    cpp: () => cpp,
    csharp: () => csharp,
    css: () => css,
    diff: () => diff,
    go: () => go,
    html: () => html,
    java: () => java,
    javascript: () => javascript,
    json: () => json,
    kotlin: () => kotlin,
    markdown: () => markdown,
    mermaid: () => mermaid,
    php: () => php,
    python: () => python,
    ruby: () => ruby,
    rust: () => rust,
    sql: () => sql,
    swift: () => swift,
    typescript: () => typescript,
    xml: () => xml,
    yaml: () => yaml,
};

let highlighter = null;

/** The real highlighter, with codeToHtml declining instead of throwing for a language it does not have. */
const create = () => {
    const real = createHighlighterCoreSync({
        themes: [createCssVariablesTheme({name: 'marp-shiki', variablePrefix: '--marp-shiki-'})],
        langs: [],
        engine: createJavaScriptRegexEngine({forgiving: true}),
    });
    return {
        getLoadedLanguages: () => real.getLoadedLanguages(),
        loadLanguageSync: (lang) => real.loadLanguageSync(lang),
        codeToHtml: (code, options) => {
            try {
                return real.codeToHtml(code, options);
            } catch {
                // An empty string tells Marp to render the fence uncoloured.
                return '';
            }
        },
    };
};

export const shiki = {
    get highlighter() {
        highlighter = highlighter ?? create();
        return highlighter;
    },
    resolveLang: (lang) => langLoaders[lang],
};
