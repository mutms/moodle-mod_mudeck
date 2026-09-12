var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Printable copy of the presentation: one slide per page, with the speaker notes underneath.
 *
 * @module     mod_mudeck/print
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { renderParts } from "./render";
function Print({ parts, themecss, exiturl, labels, notes }) {
  const pagesref = useRef(null);
  const [deck, setDeck] = useState({
    slides: [],
    notes: [],
    css: ""
  });
  useEffect(() => {
    let cancelled = false;
    (async () => {
      const { html, css, notes: notes2 } = await renderParts(parts ?? [], themecss ?? {});
      if (cancelled) {
        return;
      }
      const holder = document.createElement("div");
      holder.innerHTML = html;
      setDeck({
        slides: Array.from(holder.querySelectorAll("section")).map((one) => one.outerHTML),
        notes: notes2,
        css
      });
    })();
    return () => {
      cancelled = true;
    };
  }, [parts, themecss]);
  useEffect(() => {
    const node = pagesref.current;
    if (!node) {
      return void 0;
    }
    const fit = /* @__PURE__ */ __name(() => {
      const slide = node.querySelector(".mudeck-print-slide");
      if (slide) {
        node.style.setProperty("--mudeck-print-width", String(slide.clientWidth));
      }
    }, "fit");
    fit();
    window.addEventListener("resize", fit);
    return () => window.removeEventListener("resize", fit);
  }, [deck]);
  return /* @__PURE__ */ jsxDEV("div", { className: notes ? "mudeck-print" : "mudeck-print mudeck-print-handout", children: [
    /* @__PURE__ */ jsxDEV("style", { children: deck.css }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/print.tsx",
      lineNumber: 89,
      columnNumber: 13
    }, this),
    !notes && /* @__PURE__ */ jsxDEV("style", { children: "@page { size: 1280px 720px; margin: 0; }" }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/print.tsx",
      lineNumber: 91,
      columnNumber: 24
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-print-actions", children: [
      /* @__PURE__ */ jsxDEV("button", { type: "button", className: "btn btn-primary", onClick: () => window.print(), children: labels.print }, void 0, false, {
        fileName: "public/mod/mudeck/js/esm/src/print.tsx",
        lineNumber: 94,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("a", { href: exiturl, className: "btn btn-secondary", children: labels.exit }, void 0, false, {
        fileName: "public/mod/mudeck/js/esm/src/print.tsx",
        lineNumber: 97,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mudeck/js/esm/src/print.tsx",
      lineNumber: 93,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-print-pages", ref: pagesref, children: deck.slides.map((slide, index) => /* @__PURE__ */ jsxDEV("div", { className: "mudeck-print-page", children: [
      /* @__PURE__ */ jsxDEV(
        "div",
        {
          className: "mudeck-print-slide marpit",
          dangerouslySetInnerHTML: { __html: slide }
        },
        void 0,
        false,
        {
          fileName: "public/mod/mudeck/js/esm/src/print.tsx",
          lineNumber: 104,
          columnNumber: 25
        },
        this
      ),
      notes && /* @__PURE__ */ jsxDEV("div", { className: "mudeck-print-notes", children: [
        /* @__PURE__ */ jsxDEV("h2", { className: "mudeck-print-notes-heading", children: [
          labels.notes,
          " ",
          /* @__PURE__ */ jsxDEV("span", { className: "mudeck-print-number", children: index + 1 }, void 0, false, {
            fileName: "public/mod/mudeck/js/esm/src/print.tsx",
            lineNumber: 111,
            columnNumber: 52
          }, this)
        ] }, void 0, true, {
          fileName: "public/mod/mudeck/js/esm/src/print.tsx",
          lineNumber: 110,
          columnNumber: 33
        }, this),
        deck.notes[index] ? /* @__PURE__ */ jsxDEV("pre", { className: "mudeck-print-note", children: deck.notes[index] }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/print.tsx",
          lineNumber: 114,
          columnNumber: 39
        }, this) : /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: labels.nonotes }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/print.tsx",
          lineNumber: 115,
          columnNumber: 39
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/print.tsx",
        lineNumber: 109,
        columnNumber: 29
      }, this)
    ] }, index, true, {
      fileName: "public/mod/mudeck/js/esm/src/print.tsx",
      lineNumber: 102,
      columnNumber: 21
    }, this)) }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/print.tsx",
      lineNumber: 100,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/print.tsx",
    lineNumber: 88,
    columnNumber: 9
  }, this);
}
__name(Print, "Print");
export {
  Print as default
};
//# sourceMappingURL=print.dev.js.map
