var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Tell the server that the last slide was reached.
 *
 * @module     mod_mudeck/completion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const registry = /* @__PURE__ */ __name(() => window.M?.util, "registry");
async function reportReachedEnd(target) {
  const key = "mod_mudeck/reachedend";
  registry()?.js_pending?.(key);
  try {
    await fetch(target.url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sesskey: target.sesskey }),
      keepalive: true
    });
  } catch {
  } finally {
    registry()?.js_complete?.(key);
  }
}
__name(reportReachedEnd, "reportReachedEnd");
export {
  reportReachedEnd
};
//# sourceMappingURL=completion.dev.js.map
