var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Optional Marp plugins (maths, code colouring, diagrams), loaded on demand and cached once per page.
 *
 * @module     mod_mudeck/plugins
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const FENCEWITHLANG = /^[ \t]{0,3}(?:\x60{3,}|~{3,})[ \t]*[A-Za-z]/m;
const MERMAIDFENCE = /^[ \t]{0,3}(?:\x60{3,}|~{3,})[ \t]*mermaid\b/m;
function detectNeeds(markdown) {
  const mermaid = MERMAIDFENCE.test(markdown);
  return {
    math: markdown.includes("$"),
    code: FENCEWITHLANG.test(markdown),
    mermaid
  };
}
__name(detectNeeds, "detectNeeds");
const loaders = {
  math: /* @__PURE__ */ __name(() => import("@mudeck/marp-mathjax"), "math"),
  code: /* @__PURE__ */ __name(() => import("@mudeck/marp-shiki"), "code"),
  mermaid: /* @__PURE__ */ __name(() => import("@mudeck/marp-mermaid"), "mermaid")
};
const loaded = {};
async function loadPlugins(needs) {
  const wanted = Object.keys(loaders).filter((name) => needs[name]);
  const results = await Promise.all(wanted.map((name) => {
    loaded[name] = loaded[name] ?? loaders[name]().then((module) => module.default);
    return loaded[name].catch((e) => {
      window.console.error(`[mudeck] could not load the ${name} plugin`, e);
      delete loaded[name];
      return null;
    });
  }));
  return results.filter((plugin) => plugin !== null);
}
__name(loadPlugins, "loadPlugins");
export {
  detectNeeds,
  loadPlugins
};
//# sourceMappingURL=plugins.dev.js.map
