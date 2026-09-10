var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Slide deck viewer.
 *
 * React is the mount point and owns the chrome around the deck. marp-core does the
 * rendering (render.ts) and the deck mechanics are plain DOM (presenter.ts).
 *
 * Mounted by core/react_autoinit via data-react-component="@moodle/lms/mod_mudeck/viewer".
 *
 * @module     mod_mudeck/viewer
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useRef, useState } from "react";
import { filterSlides } from "./filters";
import { reportReachedEnd } from "./completion";
import { renderParts } from "./render";
import { mountPresenter } from "./presenter";
const INTROFOR = 3e3;
const LEAVEAFTER = 1e3;
const TOUCHFOR = 2500;
const ZONE = 0.15;
function Viewer({ parts, themecss, exiturl, labels, sync, reachedend, notes, startslide }) {
  const slidesref = useRef(null);
  const presenter = useRef(null);
  const origins = useRef([]);
  const slidenotes = useRef([]);
  const hidetimer = useRef(void 0);
  const reported = useRef(false);
  const [state, setState] = useState({ current: 0, total: 0 });
  const [chromevisible, setChromevisible] = useState(true);
  const [overviewopen, setOverviewopen] = useState(false);
  const [notesopen, setNotesopen] = useState(false);
  useEffect(() => {
    const node = slidesref.current;
    if (!node) {
      return void 0;
    }
    const { html, css, origins: slideorigins, notes: decknotes } = renderParts(parts ?? [], themecss ?? {});
    origins.current = slideorigins;
    slidenotes.current = decknotes;
    node.innerHTML = `<style>${css}</style>${html}`;
    presenter.current = mountPresenter(node, setState);
    if (startslide && startslide > 1) {
      presenter.current.goto(startslide);
    }
    filterSlides(node);
    return () => {
      presenter.current?.destroy();
      presenter.current = null;
    };
  }, [parts, themecss, startslide]);
  useEffect(() => {
    const origin = origins.current[state.current - 1];
    if (!sync || !state.total || !origin) {
      return;
    }
    fetch(sync.url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        sesskey: sync.sesskey,
        partid: origin.partid,
        parthash: origin.parthash,
        slide: origin.offset,
        slidetitle: presenter.current?.titles()[state.current - 1] ?? ""
      })
    }).catch(() => void 0);
  }, [sync, state]);
  useEffect(() => {
    if (!reachedend || reported.current || !state.total || state.current !== state.total) {
      return;
    }
    reported.current = true;
    reportReachedEnd(reachedend);
  }, [reachedend, state]);
  const finish = useCallback(() => {
    if (!sync) {
      return;
    }
    const body = JSON.stringify({ sesskey: sync.sesskey });
    const url = `${sync.url}/end`;
    if (navigator.sendBeacon) {
      navigator.sendBeacon(url, new Blob([body], { type: "application/json" }));
      return;
    }
    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body,
      keepalive: true
    }).catch(() => void 0);
  }, [sync]);
  useEffect(() => {
    window.addEventListener("pagehide", finish);
    return () => window.removeEventListener("pagehide", finish);
  }, [finish]);
  const show = useCallback((forHowLong) => {
    window.clearTimeout(hidetimer.current);
    setChromevisible(true);
    if (forHowLong !== null) {
      hidetimer.current = window.setTimeout(() => setChromevisible(false), forHowLong);
    }
  }, []);
  const hide = useCallback((after) => {
    window.clearTimeout(hidetimer.current);
    hidetimer.current = window.setTimeout(() => setChromevisible(false), after);
  }, []);
  const panelopen = overviewopen || notesopen;
  useEffect(() => {
    if (panelopen) {
      window.clearTimeout(hidetimer.current);
      setChromevisible(true);
      return void 0;
    }
    show(INTROFOR);
    return () => window.clearTimeout(hidetimer.current);
  }, [show, panelopen]);
  useEffect(() => {
    if (panelopen) {
      return void 0;
    }
    const moved = /* @__PURE__ */ __name((event) => {
      if (event.clientY >= window.innerHeight * (1 - ZONE)) {
        show(null);
      } else {
        hide(LEAVEAFTER);
      }
    }, "moved");
    const left = /* @__PURE__ */ __name(() => hide(LEAVEAFTER), "left");
    const tapped = /* @__PURE__ */ __name((event) => {
      const touch = event.touches[0];
      if (touch && touch.clientY >= window.innerHeight * (1 - ZONE)) {
        show(TOUCHFOR);
      }
    }, "tapped");
    document.addEventListener("pointermove", moved);
    document.addEventListener("touchstart", tapped);
    window.addEventListener("pointerleave", left);
    return () => {
      document.removeEventListener("pointermove", moved);
      document.removeEventListener("touchstart", tapped);
      window.removeEventListener("pointerleave", left);
    };
  }, [show, hide, panelopen]);
  const counter = state.total ? (labels.slideof || "{$a->current} / {$a->total}").replace("{$a->current}", String(state.current)).replace("{$a->total}", String(state.total)) : "";
  return /* @__PURE__ */ jsxDEV("div", { className: `mudeck-deck${chromevisible ? "" : " mudeck-chrome-hidden"}`, children: [
    /* @__PURE__ */ jsxDEV("div", { ref: slidesref, className: "mudeck-slides", tabIndex: -1 }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 246,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-chrome", "data-region": "mudeck-chrome", children: [
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-secondary mudeck-control",
          onClick: () => {
            presenter.current?.previous();
            presenter.current?.focus();
          },
          disabled: state.current <= 1,
          children: [
            /* @__PURE__ */ jsxDEV("i", { className: "fa fa-chevron-left", "aria-hidden": "true" }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 258,
              columnNumber: 21
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.previous }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 259,
              columnNumber: 21
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 249,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-secondary mudeck-counter",
          onClick: () => setOverviewopen((open) => !open),
          "aria-expanded": overviewopen,
          "aria-controls": "mudeck-overview",
          title: labels.overview,
          children: /* @__PURE__ */ jsxDEV("span", { "aria-live": "polite", children: counter }, void 0, false, {
            fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
            lineNumber: 270,
            columnNumber: 21
          }, this)
        },
        void 0,
        false,
        {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 262,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-secondary mudeck-control",
          onClick: () => {
            presenter.current?.next();
            presenter.current?.focus();
          },
          disabled: state.total > 0 && state.current >= state.total,
          children: [
            /* @__PURE__ */ jsxDEV("i", { className: "fa fa-chevron-right", "aria-hidden": "true" }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 282,
              columnNumber: 21
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.next }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 283,
              columnNumber: 21
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 273,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-secondary mudeck-control",
          onClick: () => {
            presenter.current?.toggleFullscreen();
            presenter.current?.focus();
          },
          children: [
            /* @__PURE__ */ jsxDEV("i", { className: "fa fa-expand", "aria-hidden": "true" }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 294,
              columnNumber: 21
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.fullscreen }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 295,
              columnNumber: 21
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 286,
          columnNumber: 17
        },
        this
      ),
      notes && /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-secondary mudeck-control",
          onClick: () => setNotesopen((open) => !open),
          "aria-expanded": notesopen,
          "aria-controls": "mudeck-slide-notes",
          title: labels.notes,
          children: [
            /* @__PURE__ */ jsxDEV("i", { className: "fa fa-sticky-note", "aria-hidden": "true" }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 307,
              columnNumber: 25
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.notes }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
              lineNumber: 308,
              columnNumber: 25
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 299,
          columnNumber: 21
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("a", { href: exiturl, className: "btn btn-secondary mudeck-control", onClick: finish, children: [
        /* @__PURE__ */ jsxDEV("i", { className: "fa fa-times", "aria-hidden": "true" }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 313,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.exit }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
          lineNumber: 314,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
        lineNumber: 312,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 248,
      columnNumber: 13
    }, this),
    notes && notesopen && /* @__PURE__ */ jsxDEV("div", { className: "mudeck-slide-notes", id: "mudeck-slide-notes", "data-region": "mudeck-slide-notes", children: /* @__PURE__ */ jsxDEV("pre", { className: "mudeck-slide-notes-text", children: slidenotes.current[state.current - 1] || labels.nonotes }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 320,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 319,
      columnNumber: 17
    }, this),
    overviewopen && /* @__PURE__ */ jsxDEV("div", { className: "mudeck-overview", id: "mudeck-overview", "data-region": "mudeck-overview", children: /* @__PURE__ */ jsxDEV("ul", { className: "mudeck-overview-list", children: (presenter.current?.titles() ?? []).map((title, i) => /* @__PURE__ */ jsxDEV("li", { children: /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        className: `btn btn-link mudeck-overview-item${state.current === i + 1 ? " active" : ""}`,
        onClick: () => {
          presenter.current?.goto(i + 1);
          setOverviewopen(false);
          presenter.current?.focus();
        },
        "aria-current": state.current === i + 1,
        children: [
          /* @__PURE__ */ jsxDEV("span", { className: "mudeck-overview-number", children: i + 1 }, void 0, false, {
            fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
            lineNumber: 341,
            columnNumber: 37
          }, this),
          /* @__PURE__ */ jsxDEV("span", { className: "mudeck-overview-title", children: title }, void 0, false, {
            fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
            lineNumber: 342,
            columnNumber: 37
          }, this)
        ]
      },
      void 0,
      true,
      {
        fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
        lineNumber: 331,
        columnNumber: 33
      },
      this
    ) }, i, false, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 330,
      columnNumber: 29
    }, this)) }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 328,
      columnNumber: 21
    }, this) }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
      lineNumber: 327,
      columnNumber: 17
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/viewer.tsx",
    lineNumber: 245,
    columnNumber: 9
  }, this);
}
__name(Viewer, "Viewer");
export {
  Viewer as default
};
//# sourceMappingURL=viewer.dev.js.map
