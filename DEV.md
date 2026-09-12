# Development notes

Working notes for the `MOODLE_503_DEV` branch. **Nothing here is decided for good**:
this is a plan under discussion, kept next to the code so the reasoning is not lost.
It is not linked from the README and it is not documentation of the plugin.

## Why this plugin

mod_mudeck is used as a testbed for Moodle development patterns. It has every shape
of UI Moodle asks for, so an idea can be tried in the place it fits and compared with
the alternatives next door:

- pages where the browser does real work (viewer, editor, notes) - React,
- plain CRUD pages (themes, sessions, confirmations) - the candidates for htmx,
- a mixed page (overview) - the test of the "one owner per page" rule,
- JSON routes for machine calls (completion, device sync),
- every test layer: router harness, PHPUnit, Behat with JS, and a CI matrix.

The DEV branch is alpha maturity and says so in the README, which is the licence to
experiment. The stable branch only ever receives what has proved itself here.

## Routes

The JSON API follows one rule so that a file can be predicted from a URL:

- one class per resource, with real HTTP verbs: `part` (DELETE), `session` (GET, PATCH);
- a separate action class only for a state change that is more than a field update:
  `part_move`, `session_end`;
- the file name says which resource it belongs to: `presentation_reached_end`,
  `part_edit_images`.

Rule of thumb: if the client could say "set these fields to these values", it is a
PATCH on the resource. If the server has to interpret a decision, it is a named action.

## htmx for pages without React

The idea is to replace Moodle's fragment API and inline editing with server-rendered
Bootstrap forms and partials: the browser posts a form, the route renders a template,
htmx swaps the result into part of the page. No AMD module per feature, no `{{#js}}`
block to keep in step with the markup, and Behat sees plain HTML arriving.

### Ground rules

- A page is either htmx or React, never both. The overview stays React and its
  server-rendered fallback rows are not reused by htmx partials.
- htmx pages carry no `{{#js}}` blocks, so a fragment never has to collect queued JS.
- HTML5 validation in the browser (`required`, `maxlength`, `pattern`) does the simple
  checks. The server only has to be safe: `clean_param` every field, and re-render the
  form with a message only for what the browser cannot know, such as a short name that
  is already taken.
- The forms that embed a file manager (part edit, import) stay full pages; the widget
  does not survive being swapped in.

### How the pieces map onto core

- Fragment routes return `\core\router\schema\response\view_response`, the `text/html`
  sibling of `payload_response`. It exists in core, tested, and unused so far.
- They live under `classes/route/controller/`, which puts them in the page route group:
  no CORS, no response-schema validation. `classes/route/api/` stays JSON only.
- htmx itself is vendored like Marp (`js/vendor`, `thirdpartylibs.xml`) and registered in
  the import map by the existing hook in `classes/hook_callbacks.php`, as its own bundle
  so React pages do not download it.
- Core reads the sesskey from the body or the query string, never from a header.
  `\core\router\util::confirm_sesskey()` accepts an explicit value as its second
  argument, so a small plugin wrapper reading a request header is enough; no core patch.
- One shared bootstrap module for every htmx page: the sesskey header, the rule that
  a 422 response is swapped (htmx ignores 4xx by default), and the `core/pending`
  bridge so Behat waits for swaps.

### Response contract for a form

| Outcome        | Status | Body                                    | Header                                     |
|----------------|--------|-----------------------------------------|--------------------------------------------|
| saved          | 200    | the row, or the list                    | `HX-Trigger` if something else must update |
| invalid        | 422    | the form again, message under the field | none                                       |
| leave the page | 200    | empty                                   | `HX-Redirect`                              |

Decisions come from the status and headers, never from the body.

### What has to change first

- Flash messages: eleven `redirect()` calls stash a notice for the next page load, which
  a fragment does not have. One convention is needed: an out-of-band swap into a
  notification region, or `HX-Redirect` where a full page really follows.
- Destructive GET links with the sesskey in the URL (sessions delete, part up/down)
  become `hx-delete` and `hx-post` buttons.
- The confirm interstitials (`theme_delete.php`, `part_delete.php`) become `hx-confirm`
  on the button. The theme one shows how many presentations use the theme, so that
  count is rendered into the attribute with the row.
- No page loads a plain ES module yet; every entry point is reached through a React
  mount. The htmx bootstrap needs a module script in the template.

### When

Not in mudeck first. The htmx layer is built in tool_mulib and proven in a new plugin
with pages that need it from the start (a cohort rules manager: list, preview,
activation). mudeck's sessions and themes pages are converted after that.

### Suggested order, once it is mudeck's turn

1. Sessions: fixes the GET-with-sesskey links, and its rows go stale on their own,
   which makes it the natural first user of polling.
2. Themes: the form example, with the one server-side uniqueness check.
3. Then decide, on evidence, whether the remaining management pages follow.

Both are behind capabilities most users never hold, so a rough first version costs
nothing.

## Marp 5

marp-core 5 is a lightweight core plus plugins. The plugin ships four bundles built by
`js/vendor/src/build.mjs`: the renderer with DOMPurify, and MathJax, Shiki and
Mermaid as one bundle each. `js/esm/src/plugins.ts` reads the Markdown and fetches only
the bundles a deck needs, so a deck of plain text downloads the renderer alone.

- Maths is Marp syntax, `$x$` and `$$ ... $$`, typeset to inline SVG at render time.
  Moodle's own MathJax filter never touched client-rendered slides and is not used.
- Shiki's language table is replaced by a curated list in `js/vendor/src/shiki-langs.mjs`;
  any other language renders as plain code.
- The renderer's SVG and inline styles pass through `sanitize.ts`; see its docblock for
  what is admitted and why. The style hook is the boundary, which is what lets the Marp
  colour directives back in.
- 5.0.2 is the `next` prerelease on npm, pinned in `npm-shrinkwrap.json`.
