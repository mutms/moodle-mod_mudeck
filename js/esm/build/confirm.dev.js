var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Asking before something is destroyed.
 *
 * Moodle already has the dialogue for this, so the plugin borrows it rather than growing
 * a modal of its own - and falls back to the browser's own question if it cannot be had.
 *
 * @module     mod_mudeck/confirm
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { requireAsync } from "@moodle/lms/core/amd";
async function confirmed(title, question, save) {
  let notification;
  try {
    notification = await requireAsync("core/notification");
  } catch {
    return window.confirm(question);
  }
  try {
    await notification.saveCancelPromise(title, question, save);
    return true;
  } catch {
    return false;
  }
}
__name(confirmed, "confirmed");
export {
  confirmed
};
//# sourceMappingURL=confirm.dev.js.map
