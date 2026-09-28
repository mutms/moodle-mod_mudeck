var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Browser side of the part editor layout: tabs of the right pane and the slide preview.
 *
 * @module     mod_mudeck/muform/element/partcontainer
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Element from "@moodle/lms/tool_mulib/muform/element";
import { mountReactApp } from "@moodle/lms/core/mount";
import Editor from "../../editor";
class partcontainer_default extends Element {
  static {
    __name(this, "default");
  }
  /**
   * Wire the tabs and mount the preview.
   *
   * @param wrapper outer element with data-muform-* attributes
   * @param form the form API
   */
  constructor(wrapper, form) {
    super(wrapper, form);
    const tabs = Array.from(wrapper.querySelectorAll("[data-mudeck-tab]"));
    const panes = Array.from(wrapper.querySelectorAll("[data-mudeck-pane]"));
    tabs.forEach((tab) => tab.addEventListener("click", () => {
      tabs.forEach((other) => {
        const active = other === tab;
        other.classList.toggle("active", active);
        other.setAttribute("aria-selected", String(active));
      });
      panes.forEach((pane) => {
        pane.hidden = pane.dataset.mudeckPane !== tab.dataset.mudeckTab;
      });
    }));
    const textarea = wrapper.querySelector('[data-muform-name="content"] textarea');
    const mount = wrapper.querySelector('[data-region="mudeck-editor-slides"]');
    if (!textarea || !mount) {
      return;
    }
    const config = JSON.parse(wrapper.dataset.mudeckEditor ?? "{}");
    const props = {
      ...config,
      textarea,
      caretstart: wrapper.querySelector('input[name="caretstart"]'),
      caretend: wrapper.querySelector('input[name="caretend"]'),
      mediapane: wrapper.querySelector('[data-mudeck-pane="media"]')
    };
    mountReactApp(mount, Editor, props);
  }
}
export {
  partcontainer_default as default
};
//# sourceMappingURL=partcontainer.dev.js.map
