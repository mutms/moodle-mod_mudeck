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
 * Bundle marp-core into js/vendor/marp-core.js.
 *
 * Core builds plugin ES modules without bundling, so a third party library has to
 * arrive pre-bundled and registered in the import map (see classes/hook_callbacks.php).
 *
 * Math is deliberately stubbed out: it is out of scope for now, and mathjax-full
 * grabs the global MathJax object, which Moodle already defines for its own filter -
 * loading both breaks the module at import time.
 *
 * Run with: npm run build:vendor - esbuild is taken from Moodle's own node_modules,
 * the plugin sits inside the tree so nothing else has to be installed for it.
 */

import esbuild from 'esbuild';
import path from 'path';
import {fileURLToPath} from 'url';

const here = path.dirname(fileURLToPath(import.meta.url));
const stub = path.join(here, 'math-stub.mjs');

/** Replace every math engine import with an inert stub. */
const stubMath = {
    name: 'stub-math',
    setup(build) {
        build.onResolve({filter: /^(mathjax-full|katex)(\/|$)/}, () => ({path: stub}));
    },
};

await esbuild.build({
    entryPoints: [path.join(here, 'entry.mjs')],
    bundle: true,
    format: 'esm',
    minify: true,
    target: 'es2020',
    define: {'process.env.NODE_ENV': '"production"'},
    plugins: [stubMath],
    outfile: path.join(here, '..', 'marp-core.js'),
    logLevel: 'info',
});
