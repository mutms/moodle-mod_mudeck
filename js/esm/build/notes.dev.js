var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * Speaker notes of the presentation running on another device.
 *
 * Reads the position the showing device reports, and shows the notes of that slide under
 * it, with the slides on either side small, so the speaker can see where they are and
 * what is coming.
 *
 * @module     mod_mudeck/notes
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { filterSlides } from "./filters";
import { renderParts } from "./render";
const POLLEVERY = 1e3;
const MAINSHARE = 0.62;
const SIDESHARE = 0.5;
const LEASTSIDE = 120;
const EDGE = 24;
const GAP = 16;
const SHAPE = 1280 / 720;
function clock(seconds) {
  const minutes = Math.floor(seconds / 60);
  return `${minutes}:${String(Math.floor(seconds % 60)).padStart(2, "0")}`;
}
__name(clock, "clock");
function state(gone, following, running, hasnotes) {
  if (gone) {
    return "gone";
  }
  if (!following) {
    return "waiting";
  }
  if (!running) {
    return "lost";
  }
  return hasnotes ? "notes" : "nonotes";
}
__name(state, "state");
function Said({ state: said, notes, labels, exiturl }) {
  if (said === "notes") {
    return /* @__PURE__ */ jsxDEV("pre", { className: "mudeck-notes-note", children: notes }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 130,
      columnNumber: 16
    }, this);
  }
  if (said === "nonotes") {
    return /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: labels.nonotes }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 133,
      columnNumber: 16
    }, this);
  }
  if (said === "waiting") {
    return /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: labels.waiting }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 136,
      columnNumber: 16
    }, this);
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "alert alert-warning", role: "status", children: [
    /* @__PURE__ */ jsxDEV("p", { children: said === "gone" ? labels.gone : labels.stale }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 142,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("a", { href: exiturl, className: "btn btn-sm btn-secondary", children: labels.reconnect }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 143,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
    lineNumber: 141,
    columnNumber: 9
  }, this);
}
__name(Said, "Said");
function Notes({ parts, themecss, pollurl, exiturl, labels }) {
  const stageref = useRef(null);
  const slideref = useRef(null);
  const beforeref = useRef(null);
  const afterref = useRef(null);
  const [deck, setDeck] = useState({
    slides: [],
    notes: [],
    css: "",
    origins: []
  });
  const [position, setPosition] = useState(null);
  const [gone, setGone] = useState(false);
  const [began, setBegan] = useState(null);
  const [, tick] = useState(0);
  useEffect(() => {
    const { html, css, notes: notes2, origins } = renderParts(parts ?? [], themecss ?? {});
    const holder = document.createElement("div");
    holder.innerHTML = html;
    setDeck({
      slides: Array.from(holder.querySelectorAll("section")).map((one) => one.outerHTML),
      notes: notes2,
      css,
      origins
    });
  }, [parts, themecss]);
  useEffect(() => {
    let stopped = false;
    const poll = /* @__PURE__ */ __name(async () => {
      try {
        const response = await fetch(pollurl, { headers: { Accept: "application/json" } });
        if (!response.ok) {
          if (!stopped) {
            setGone(true);
          }
          return;
        }
        const reported = await response.json();
        if (!stopped && reported?.ended) {
          setGone(true);
          return;
        }
        if (!stopped && typeof reported?.slide === "number") {
          setGone(false);
          if (typeof reported.elapsed === "number") {
            setBegan(Date.now() - reported.elapsed * 1e3);
          }
          setPosition({
            partid: Number(reported.partid) || 0,
            parthash: String(reported.parthash ?? ""),
            slide: reported.slide
          });
        }
      } catch {
      }
    }, "poll");
    poll();
    const timer = window.setInterval(poll, POLLEVERY);
    return () => {
      stopped = true;
      window.clearInterval(timer);
    };
  }, [pollurl]);
  useEffect(() => {
    const timer = window.setInterval(() => tick((was) => was + 1), POLLEVERY);
    return () => window.clearInterval(timer);
  }, []);
  const index = position ? deck.origins.findIndex(
    (origin) => origin.partid === position.partid && origin.offset === position.slide
  ) : -1;
  const stale = !!position && index >= 0 && !!position.parthash && deck.origins[index].parthash !== position.parthash;
  const running = index >= 0 && !stale && !gone;
  const slide = index + 1;
  const notes = running ? deck.notes[index] : "";
  const shown = running ? deck.slides[slide - 1] : "";
  const said = state(gone, !!position, running, !!notes);
  const sofar = began === null ? null : Math.max(0, Math.floor((Date.now() - began) / 1e3));
  const before = running ? deck.slides[slide - 2] ?? "" : "";
  const after = running ? deck.slides[slide] ?? "" : "";
  useEffect(() => {
    const stage = stageref.current;
    if (!stage) {
      return void 0;
    }
    const fit = /* @__PURE__ */ __name(() => {
      const box = stage.getBoundingClientRect();
      const room = Math.max(0, box.height - EDGE);
      const main = Math.max(0, Math.min(box.width * MAINSHARE, room * SHAPE));
      const spare = (box.width - main - GAP * 2) / 2;
      const side = spare < LEASTSIDE ? 0 : Math.min(spare, main * SIDESHARE);
      const sizes = [
        [slideref, main],
        [beforeref, side],
        [afterref, side]
      ];
      sizes.forEach(([ref, width]) => {
        const node = ref.current;
        if (!node) {
          return;
        }
        const holder = node.parentElement;
        if (holder) {
          holder.style.display = width ? "" : "none";
        }
        node.style.setProperty("--mudeck-width", `${width}px`);
        node.style.setProperty("--mudeck-scale", String(width / 1280));
      });
    }, "fit");
    fit();
    const watcher = new ResizeObserver(fit);
    watcher.observe(stage);
    return () => watcher.disconnect();
  }, [deck, slide]);
  useEffect(() => {
    if (slideref.current && running) {
      filterSlides(slideref.current);
    }
  }, [slide, running]);
  return /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes", children: [
    /* @__PURE__ */ jsxDEV("style", { children: deck.css }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 308,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes-body", children: [
      /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes-stage", ref: stageref, children: [
        /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes-neighbour", children: /* @__PURE__ */ jsxDEV(
          "div",
          {
            ref: beforeref,
            className: "mudeck-notes-preview marpit",
            "aria-hidden": "true",
            dangerouslySetInnerHTML: { __html: before }
          },
          void 0,
          false,
          {
            fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
            lineNumber: 314,
            columnNumber: 25
          },
          this
        ) }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 313,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes-slide", children: [
          /* @__PURE__ */ jsxDEV("h3", { className: "mudeck-notes-heading visually-hidden", children: labels.current }, void 0, false, {
            fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
            lineNumber: 323,
            columnNumber: 25
          }, this),
          /* @__PURE__ */ jsxDEV(
            "div",
            {
              ref: slideref,
              className: "mudeck-notes-preview marpit",
              "aria-hidden": "true",
              dangerouslySetInnerHTML: { __html: shown }
            },
            void 0,
            false,
            {
              fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
              lineNumber: 325,
              columnNumber: 25
            },
            this
          )
        ] }, void 0, true, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 322,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes-neighbour", children: /* @__PURE__ */ jsxDEV(
          "div",
          {
            ref: afterref,
            className: "mudeck-notes-preview marpit",
            "aria-hidden": "true",
            dangerouslySetInnerHTML: { __html: after }
          },
          void 0,
          false,
          {
            fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
            lineNumber: 334,
            columnNumber: 25
          },
          this
        ) }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 333,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
        lineNumber: 311,
        columnNumber: 17
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "mudeck-notes-text", children: [
        /* @__PURE__ */ jsxDEV("h2", { className: "mudeck-notes-heading", children: labels.notes }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 344,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV(Said, { state: said, notes, labels, exiturl }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 345,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
        lineNumber: 343,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 310,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-chrome mudeck-notes-chrome", "data-region": "mudeck-notes-chrome", children: [
      sofar !== null && /* @__PURE__ */ jsxDEV("span", { className: "mudeck-notes-clock", title: labels.elapsed, "data-region": "mudeck-notes-clock", children: [
        /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: [
          labels.elapsed,
          ": "
        ] }, void 0, true, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 352,
          columnNumber: 25
        }, this),
        clock(sofar)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
        lineNumber: 351,
        columnNumber: 21
      }, this),
      /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          className: "btn btn-secondary mudeck-control",
          onClick: () => {
            if (document.fullscreenElement) {
              document.exitFullscreen();
            } else {
              document.documentElement.requestFullscreen();
            }
          },
          children: [
            /* @__PURE__ */ jsxDEV("i", { className: "fa fa-expand", "aria-hidden": "true" }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
              lineNumber: 367,
              columnNumber: 21
            }, this),
            /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: labels.fullscreen }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
              lineNumber: 368,
              columnNumber: 21
            }, this)
          ]
        },
        void 0,
        true,
        {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 356,
          columnNumber: 17
        },
        this
      ),
      /* @__PURE__ */ jsxDEV("a", { href: exiturl, className: "btn btn-secondary mudeck-control", children: [
        /* @__PURE__ */ jsxDEV("i", { className: "fa fa-times", "aria-hidden": "true" }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 372,
          columnNumber: 21
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: labels.exit }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
          lineNumber: 373,
          columnNumber: 21
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
        lineNumber: 371,
        columnNumber: 17
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
      lineNumber: 349,
      columnNumber: 13
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/notes.tsx",
    lineNumber: 307,
    columnNumber: 9
  }, this);
}
__name(Notes, "Notes");
export {
  Notes as default
};
//# sourceMappingURL=notes.dev.js.map
