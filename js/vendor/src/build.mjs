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
 * Bundle Marp and its plugins into js/vendor/*.js.
 *
 * Core builds plugin ES modules without bundling, so a third party library has to
 * arrive pre-bundled and registered in the import map (see classes/hook_callbacks.php).
 *
 * Four bundles: the renderer with DOMPurify, and one per optional marp-core plugin -
 * MathJax, Shiki and Mermaid - so a deck only downloads what it uses. Shiki's own
 * language table is replaced by shiki-langs.mjs, a curated list, see there.
 *
 * Run with: npm run build:vendor - esbuild is taken from Moodle's own node_modules,
 * the plugin sits inside the tree so nothing else has to be installed for it.
 */

import esbuild from 'esbuild';
import path from 'path';
import {fileURLToPath} from 'url';

const here = path.dirname(fileURLToPath(import.meta.url));

/** Point marp-core's language table at ours. */
const curatedLanguages = {
    name: 'curated-languages',
    setup(build) {
        build.onResolve({filter: /^#marp-shiki$/}, () => ({path: path.join(here, 'shiki-langs.mjs')}));
    },
};

const bundles = {
    'marp-core': 'entry-core.mjs',
    'marp-mathjax': 'entry-mathjax.mjs',
    'marp-shiki': 'entry-shiki.mjs',
    'marp-mermaid': 'entry-mermaid.mjs',
};

for (const [name, entry] of Object.entries(bundles)) {
    await esbuild.build({
        entryPoints: [path.join(here, entry)],
        bundle: true,
        format: 'esm',
        minify: true,
        target: 'es2020',
        define: {'process.env.NODE_ENV': '"production"'},
        // Node-only globals the libraries read at load time, see process-shim.mjs.
        inject: [path.join(here, 'process-shim.mjs')],
        plugins: [curatedLanguages],
        outfile: path.join(here, '..', `${name}.js`),
        logLevel: 'info',
    });
}
