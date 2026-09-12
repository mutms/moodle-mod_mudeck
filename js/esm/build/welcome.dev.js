var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * The first slide on the welcome page, drawn inside the start card.
 *
 * @module     mod_mudeck/welcome
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { renderPart } from "./render";
function Welcome({ markdown, theme, themecss }) {
  const boxref = useRef(null);
  const [slide, setSlide] = useState(null);
  useEffect(() => {
    let cancelled = false;
    (async () => {
      const { html, css } = await renderPart(markdown, theme, themecss ?? {});
      if (cancelled) {
        return;
      }
      const holder = document.createElement("div");
      holder.innerHTML = html;
      const first = holder.querySelector("section");
      setSlide(first ? { html: first.outerHTML, css } : null);
    })();
    return () => {
      cancelled = true;
    };
  }, [markdown, theme, themecss]);
  useEffect(() => {
    const box = boxref.current;
    if (!box) {
      return void 0;
    }
    const fit = /* @__PURE__ */ __name(() => box.style.setProperty("--mudeck-poster-width", String(box.clientWidth)), "fit");
    fit();
    const watcher = new ResizeObserver(fit);
    watcher.observe(box);
    return () => watcher.disconnect();
  }, [slide]);
  return /* @__PURE__ */ jsxDEV("div", { className: "mudeck-poster-box", ref: boxref, "aria-hidden": "true", children: slide && /* @__PURE__ */ jsxDEV(Fragment, { children: [
    /* @__PURE__ */ jsxDEV("style", { children: slide.css }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/welcome.tsx",
      lineNumber: 70,
      columnNumber: 21
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-poster-slide marpit", dangerouslySetInnerHTML: { __html: slide.html } }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/welcome.tsx",
      lineNumber: 71,
      columnNumber: 21
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/welcome.tsx",
    lineNumber: 69,
    columnNumber: 17
  }, this) }, void 0, false, {
    fileName: "public/mod/mudeck/js/esm/src/welcome.tsx",
    lineNumber: 67,
    columnNumber: 9
  }, this);
}
__name(Welcome, "Welcome");
export {
  Welcome as default
};
//# sourceMappingURL=welcome.dev.js.map
