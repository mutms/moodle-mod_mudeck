var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Tell the server that the last slide was reached.
 *
 * @module     mod_mudeck/completion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { requireAsync } from "@moodle/lms/core/amd";
async function reportReachedEnd(target) {
  let pending = null;
  try {
    const PendingPromise = await requireAsync("core/pending");
    pending = new PendingPromise("mod_mudeck/reachedend");
  } catch {
    pending = null;
  }
  try {
    await fetch(target.url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sesskey: target.sesskey }),
      keepalive: true
    });
  } catch {
  } finally {
    pending?.resolve();
  }
}
__name(reportReachedEnd, "reportReachedEnd");
export {
  reportReachedEnd
};
//# sourceMappingURL=completion.dev.js.map
