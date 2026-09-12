# Working in mod_mudeck

Instructions for coding agents. Short on purpose: the structure carries most of the
rules, this file carries the ones the structure cannot show.

## What this is

A Moodle activity that renders Marp Markdown slide decks in the browser. Part of the
MuTMS suite. This branch, `MOODLE_503_DEV`, is the experimental line (alpha maturity,
`-dev` release); `MOODLE_503_STABLE` is what non-developers install. Experiments are
welcome here, but every change lands complete: code, tests, strings, docs.

## Layout

| Path                        | Holds                                      | Rule                                                                                                                                                                 |
|-----------------------------|--------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `*.php`, `management/*.php` | one page each                              | one purpose per file; a confirm page is not a list page                                                                                                              |
| `classes/route/api/`        | JSON REST routes                           | one class per resource with real verbs; a separate `<resource>_<action>` class only for a state change that is more than a field update; file name says the resource |
| `classes/local/`            | domain logic                               | pages and routes call these; they do not query the database themselves                                                                                               |
| `js/esm/src/*.ts`           | plain logic                                | no DOM rendering, unit-testable without a browser                                                                                                                    |
| `js/esm/src/*.tsx`          | React components                           | thin; state and rendering only, logic imported from `.ts`                                                                                                            |
| `js/esm/build/`             | compiled output                            | never edit; rebuilt by grunt                                                                                                                                         |
| `js/vendor/src/`            | our esbuild recipe for third-party bundles | GPL headers, ours                                                                                                                                                    |
| `js/vendor/*.js`            | built third-party bundles                  | never edit; listed in `thirdpartylibs.xml`                                                                                                                           |
| `templates/`                | Mustache                                   | `{{{ }}}` only for rendered HTML and for JSON inside `{{#react}}`                                                                                                    |
| `tests/phpunit/route/api/`  | one test file per route class              |                                                                                                                                                                      |
| `tests/behat/`              | one feature                                | every user-facing flow has a scenario                                                                                                                                |

A page is React or plain, never both. htmx is planned for plain pages but not here
yet; it arrives through tool_mulib after it is proven elsewhere. Do not start it here.

## Code rules that are not visible from the structure

- Write pages repeat their `require_capability()` even when `admin_externalpage_setup()`
  already checked it. Privileged pages use `require_login($course, false, $cm)`; only
  pages guests may see use `require_course_login()`.
- Every REST URL is built with `\core\url::routed_path()`, never a hard-coded
  `/api/rest/v2/...`, or it breaks on servers without the rewrite.
- Comments are short and factual: one line inline, one or two sentences in a docblock,
  JSDoc `@param`/`@returns` kept. No history, no alternatives, no em dashes.
- Strings live in `lang/en`, `lang/cs` and `lang/de`; add all three.

## Security boundary

Authors may be students. Nothing they type and nothing Marp makes of it is trusted.
The rendered HTML, inline styles and SVG go through `js/esm/src/sanitize.ts`; the
deck stylesheet never comes from the author's render (`render.ts`). Read the docblock
in `sanitize.ts` before touching either, and extend allowlists with a pattern, never
by dropping a check. Site themes are trusted CSS and need `mod/mudeck:managethemes`,
which no role holds by default.

## Commands

Run from `/srv/projects/moodle53` unless noted. The short commands (`phpunit`,
`phpunit-init`, `phpunit-util`, `behat`, `behat-init`, `mdl-cache-purge`) are helper
scripts from [mpd](https://github.com/mutms/mpd), the maintainer's VM-based development
environment, found in `/opt/mpd/assets/vm/project_types/moodle/bin`. Each is a thin
wrapper: `phpunit` runs `php vendor/bin/phpunit` from the Moodle root, `phpunit-util`
runs `public/admin/tool/phpunit/cli/util.php`, `behat-init` runs
`public/admin/tool/behat/cli/init.php`, and so on. In another environment, run the
underlying Moodle commands instead; the helper name tells you which one.

| Task                   | Command                                   | Note                                                                                                            |
|------------------------|-------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| build ES modules       | `npx grunt esm --root=public/mod/mudeck`  | needs Node 22: `PATH=$HOME/.nvm/versions/node/v22.23.2/bin:$PATH`; Node 24 is refused                           |
| lint ES modules        | `npx eslint public/mod/mudeck/js/esm/src` | must exit 0; CI's grunt step does not lint ESM                                                                  |
| rebuild vendor bundles | `npm run build:vendor` in the plugin dir  | only when a dependency changes                                                                                  |
| PHPUnit                | `phpunit --filter=mod_mudeck`             | after a `version.php` bump run `phpunit-util --upgrade`; if `requires` changed, `phpunit-init` (full reinstall) |
| Behat                  | `behat --tags=@mod_mudeck`                | after a version bump or a generator change run `behat-init` first; it reinstalls when `requires` changed        |
| purge dev site caches  | `mdl-cache-purge`                         | after adding or renaming a route class, or the old path keeps being served                                      |

## Known traps

- A route that "does not exist" is usually the route cache. Purge before debugging.
- Behat cannot select inline SVG elements by tag name; use a custom element, a data
  attribute or visible text instead.
- Anything an agent registers as pending for Behat must be registered synchronously,
  before any `await`, or the step ends before the work begins.
- MathJax and beautiful-mermaid read Node's `process` at load; `js/vendor/src/process-shim.mjs`
  is injected for the browser. Bundles that pass in Node can still fail in Chrome.
- `git diff` on `js/vendor/*.js` is disabled on purpose.

## Do not

- Open or suggest pull requests; the project does not accept them.
- Bump `version.php` for each change; the maintainer bumps once per refactoring round.
- Add a `pull_request` trigger to CI.
