var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * A preview beside the slides being written.
 *
 * The Moodle form is left exactly as the server rendered it and stays the only copy of
 * the text: this draws the slides next to it and follows the writing cursor. If it never
 * mounts, the page is still the plain form it always was.
 *
 * @module     mod_mudeck/editor
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useCallback, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { renderPart } from "./render";
import { caretPoint } from "./caret";
import { isBreak, opensBreak, slideEnd } from "./source";
const SPLITKEY = "mudeck-editor-split";
const LEASTHEIGHT = 200;
const NOTICEFOR = 5e3;
const REDRAWAFTER = 250;
const MARKER = "\u2060mudeckcaret\u2060";
const ABSOLUTE = /^([a-z][a-z0-9+.-]*:|\/\/|\/|#)/i;
const REFERENCE = /(!?\[[^\]]*\]\(\s*)([^)\s]+)/g;
function withMedia(markdown, base) {
  if (!base) {
    return markdown;
  }
  const home = base.replace(/\/+$/, "");
  return markdown.split("@@PLUGINFILE@@").join(home).replace(REFERENCE, (whole, lead, target) => ABSOLUTE.test(target) ? whole : `${lead}${home}/${target.replace(/^\/+/, "")}`);
}
__name(withMedia, "withMedia");
const OPENALT = /!\[([^\]\n]*)$/;
const OPENIMAGE = /!\[([^\]\n]*)\]\($/;
function offering(textarea) {
  const caret = textarea.selectionStart ?? 0;
  if ((textarea.selectionEnd ?? 0) !== caret) {
    return null;
  }
  const before = textarea.value.slice(0, caret);
  const parens = before.match(OPENIMAGE);
  if (parens && textarea.value.charAt(caret) === ")") {
    return { alt: parens[1], mode: "parens" };
  }
  const alt = before.match(OPENALT);
  if (!alt) {
    return null;
  }
  return /^[^\]\n]*\]\(/.test(textarea.value.slice(caret)) ? null : { alt: alt[1], mode: "alt" };
}
__name(offering, "offering");
const HEADING = /^[ \t]{0,3}#{1,6}[ \t]/;
function withMarker(text, caret) {
  const lines = text.split("\n");
  let start = 0;
  let index = 0;
  for (; index < lines.length; index++) {
    const end = start + lines[index].length;
    if (caret <= end) {
      break;
    }
    start = end + 1;
  }
  const mark = /* @__PURE__ */ __name((at) => {
    const marked = [...lines];
    marked[at] = `${marked[at]}${MARKER}`;
    return marked.join("\n");
  }, "mark");
  const breaks = lines.map((line, at) => isBreak(line, at === 0 || opensBreak(lines[at - 1]), null));
  const from = Math.min(index, lines.length - 1);
  for (let at = from; at >= 0; at--) {
    if (breaks[at]) {
      break;
    }
    if (HEADING.test(lines[at])) {
      return mark(at);
    }
  }
  for (let at = from; at < lines.length; at++) {
    if (breaks[at]) {
      break;
    }
    if (HEADING.test(lines[at])) {
      return mark(at);
    }
  }
  return null;
}
__name(withMarker, "withMarker");
function showCaret(textarea, from, to) {
  textarea.focus();
  textarea.setSelectionRange(from, to);
  const point = caretPoint(textarea, to);
  const box = textarea.getBoundingClientRect();
  textarea.scrollTop += point.top - box.top - textarea.clientHeight / 2;
}
__name(showCaret, "showCaret");
function write(textarea, from, to, text, caret) {
  textarea.focus();
  textarea.setSelectionRange(from, to);
  if (!document.execCommand("insertText", false, text)) {
    textarea.setRangeText(text, from, to, "end");
    textarea.dispatchEvent(new Event("input", { bubbles: true }));
  }
  textarea.setSelectionRange(caret, caret);
}
__name(write, "write");
function Editor({ themecss, theme, labels, help, imagesurl, mediabase }) {
  const stripref = useRef(null);
  const mediaref = useRef(null);
  const menuref = useRef(null);
  const [split, setSplit] = useState(() => {
    try {
      return Number(window.sessionStorage.getItem(SPLITKEY)) || 40;
    } catch {
      return 40;
    }
  });
  const [tab, setTab] = useState("preview");
  const [slides, setSlides] = useState([]);
  const [css, setCss] = useState("");
  const [current, setCurrent] = useState(0);
  const [full, setFull] = useState(false);
  const [menu, setMenu] = useState(null);
  const [pick, setPick] = useState(-1);
  const dismissed = useRef(false);
  const files = useRef([]);
  const load = useCallback(async () => {
    try {
      const answer = await fetch(imagesurl, { headers: { Accept: "application/json" } });
      files.current = (await answer.json())?.files ?? [];
    } catch {
      files.current = [];
    }
  }, [imagesurl]);
  const offer = useCallback((textarea, open) => {
    const spot = offering(textarea);
    if (!spot) {
      dismissed.current = false;
      setMenu(null);
      return;
    }
    if (dismissed.current && !open || !files.current.length) {
      return;
    }
    dismissed.current = false;
    const point = caretPoint(textarea, textarea.selectionStart ?? 0);
    const items = files.current;
    setMenu((was) => {
      if (!was) {
        setPick(-1);
      }
      return { items, left: point.left, top: point.top + point.height };
    });
  }, []);
  const choose = useCallback((name) => {
    const textarea = document.querySelector("#id_content");
    const spot = textarea ? offering(textarea) : null;
    if (!textarea || !spot) {
      return;
    }
    const at = textarea.selectionStart ?? 0;
    if (spot.mode === "parens") {
      const start = at - `![${spot.alt}](`.length;
      write(textarea, at, at, name, spot.alt === "" ? start + 2 : at + name.length + 1);
      setMenu(null);
      return;
    }
    const text = `](${name})`;
    write(textarea, at, at, text, spot.alt === "" ? at : at + text.length);
    setMenu(null);
  }, []);
  const redraw = useCallback((textarea) => {
    const text = textarea.value;
    const marked = withMarker(text, textarea.selectionStart ?? 0);
    const { html, css: slidecss, notes } = renderPart(withMedia(marked ?? text, mediabase), theme, themecss ?? {});
    const holder = document.createElement("div");
    holder.innerHTML = html;
    const sections = Array.from(holder.querySelectorAll("section"));
    let found = -1;
    sections.forEach((section, index) => {
      if (section.innerHTML.includes(MARKER)) {
        found = index;
        section.innerHTML = section.innerHTML.split(MARKER).join("");
      }
    });
    if (found < 0) {
      found = notes.findIndex((note) => note.includes(MARKER));
    }
    setSlides(sections.map((section) => section.outerHTML));
    setCss(slidecss);
    if (found >= 0) {
      setCurrent(found);
    }
  }, [theme, themecss, mediabase]);
  useEffect(() => {
    const textarea = document.querySelector("#id_content");
    const form = textarea?.closest("form");
    if (!textarea || !form) {
      return void 0;
    }
    const start = form.elements.namedItem("caretstart");
    const end = form.elements.namedItem("caretend");
    const remember = /* @__PURE__ */ __name(() => {
      if (start && end) {
        start.value = String(textarea.selectionStart ?? 0);
        end.value = String(textarea.selectionEnd ?? 0);
      }
    }, "remember");
    form.addEventListener("submit", remember);
    return () => form.removeEventListener("submit", remember);
  }, []);
  useEffect(() => {
    const textarea = document.querySelector("#id_content");
    const form = textarea?.closest("form");
    if (!textarea || !form) {
      return void 0;
    }
    const from = Number(form.elements.namedItem("caretstart")?.value ?? 0);
    const to = Number(form.elements.namedItem("caretend")?.value ?? 0);
    if (!to) {
      return void 0;
    }
    showCaret(textarea, from, to);
    return void 0;
  }, []);
  useEffect(() => {
    const textarea = document.querySelector("#id_content");
    if (!textarea) {
      return void 0;
    }
    const editor = textarea.closest(".mudeck-editor");
    const fit = /* @__PURE__ */ __name(() => {
      const bottom = (editor ?? textarea).getBoundingClientRect().bottom + window.scrollY;
      const spare = window.innerHeight - bottom;
      const height = textarea.getBoundingClientRect().height + spare;
      textarea.style.height = `${Math.max(LEASTHEIGHT, height)}px`;
    }, "fit");
    fit();
    const settled = window.requestAnimationFrame(fit);
    window.addEventListener("resize", fit);
    return () => {
      window.cancelAnimationFrame(settled);
      window.removeEventListener("resize", fit);
    };
  }, []);
  useEffect(() => {
    const textarea = document.querySelector("#id_content");
    if (!textarea) {
      return void 0;
    }
    let timer;
    const later = /* @__PURE__ */ __name(() => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => redraw(textarea), REDRAWAFTER);
    }, "later");
    redraw(textarea);
    const events = ["input", "keyup", "click", "focus"];
    events.forEach((name) => textarea.addEventListener(name, later));
    return () => {
      events.forEach((name) => textarea.removeEventListener(name, later));
      window.clearTimeout(timer);
    };
  }, [redraw]);
  useEffect(() => {
    const textarea = document.querySelector("#id_content");
    if (!textarea) {
      return void 0;
    }
    const onkey = /* @__PURE__ */ __name((event) => {
      const last = menu ? menu.items.length - 1 : -1;
      if (event.key === "ArrowDown") {
        if (!menu) {
          if (files.current.length && offering(textarea)) {
            event.preventDefault();
            offer(textarea, true);
          }
          return;
        }
        event.preventDefault();
        setPick((was) => was >= last ? 0 : was + 1);
      } else if (menu && event.key === "ArrowUp") {
        event.preventDefault();
        setPick((was) => was <= 0 ? last : was - 1);
      } else if (menu && pick >= 0 && (event.key === "Enter" || event.key === "Tab")) {
        event.preventDefault();
        choose(menu.items[pick]);
      } else if (menu && event.key === "Escape") {
        event.preventDefault();
        dismissed.current = true;
        setMenu(null);
      }
    }, "onkey");
    const follow = /* @__PURE__ */ __name(() => offer(textarea, false), "follow");
    const away = /* @__PURE__ */ __name((event) => {
      if (!menuref.current?.contains(event.target)) {
        setMenu(null);
      }
    }, "away");
    const close = /* @__PURE__ */ __name(() => setMenu(null), "close");
    const watched = ["input", "keyup", "click"];
    textarea.addEventListener("keydown", onkey);
    watched.forEach((name) => textarea.addEventListener(name, follow));
    textarea.addEventListener("scroll", close);
    window.addEventListener("resize", close);
    document.addEventListener("pointerdown", away);
    return () => {
      textarea.removeEventListener("keydown", onkey);
      watched.forEach((name) => textarea.removeEventListener(name, follow));
      textarea.removeEventListener("scroll", close);
      window.removeEventListener("resize", close);
      document.removeEventListener("pointerdown", away);
    };
  }, [menu, pick, offer, choose]);
  useEffect(() => {
    menuref.current?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: "nearest" });
  }, [pick, menu]);
  useEffect(() => {
    document.documentElement.style.setProperty("--mudeck-split", `${split}%`);
    try {
      window.sessionStorage.setItem(SPLITKEY, String(split));
    } catch {
    }
  }, [split]);
  useEffect(() => {
    const strip = stripref.current;
    if (!strip) {
      return void 0;
    }
    const fit = /* @__PURE__ */ __name(() => {
      const box = strip.querySelector(".mudeck-editor-slide-box");
      if (box && box.clientWidth > 0) {
        strip.style.setProperty("--mudeck-strip-width", String(box.clientWidth));
      }
    }, "fit");
    fit();
    const watcher = new ResizeObserver(fit);
    watcher.observe(strip);
    return () => watcher.disconnect();
  }, [slides, tab]);
  useEffect(() => {
    let fade;
    let clear;
    let showing = "";
    const expire = /* @__PURE__ */ __name(() => {
      const notices = document.querySelector("#user-notifications");
      const text = notices?.textContent?.trim() ?? "";
      if (!text) {
        showing = "";
        return;
      }
      if (text === showing) {
        return;
      }
      showing = text;
      window.clearTimeout(fade);
      window.clearTimeout(clear);
      fade = window.setTimeout(() => notices?.classList.add("mudeck-notification-going"), NOTICEFOR);
      clear = window.setTimeout(() => {
        if (notices) {
          notices.innerHTML = "";
          notices.classList.remove("mudeck-notification-going");
        }
        showing = "";
      }, NOTICEFOR + 500);
    }, "expire");
    expire();
    const watcher = new MutationObserver(expire);
    watcher.observe(document.body, { childList: true, subtree: true });
    return () => {
      watcher.disconnect();
      window.clearTimeout(fade);
      window.clearTimeout(clear);
    };
  }, []);
  useEffect(() => {
    const holder = mediaref.current;
    const field = document.querySelector("#fitem_id_attachments");
    if (!holder || !field) {
      return void 0;
    }
    const home = field.parentElement;
    const next = field.nextElementSibling;
    const form = field.closest("form");
    holder.appendChild(field);
    const formid = form?.getAttribute("id");
    if (formid) {
      field.querySelectorAll("input, select, textarea").forEach((input) => {
        input.setAttribute("form", formid);
      });
    }
    return () => {
      if (next) {
        home?.insertBefore(field, next);
      } else {
        home?.appendChild(field);
      }
    };
  }, []);
  useEffect(() => {
    const watch = /* @__PURE__ */ __name(() => setFull(document.fullscreenElement !== null), "watch");
    watch();
    document.addEventListener("fullscreenchange", watch);
    return () => document.removeEventListener("fullscreenchange", watch);
  }, []);
  const [tools, setTools] = useState(null);
  useEffect(() => {
    setTools(document.querySelector("[data-region=mudeck-editor-tools]"));
  }, []);
  const toggleFull = /* @__PURE__ */ __name(() => {
    if (document.fullscreenElement) {
      void document.exitFullscreen();
    } else {
      void document.documentElement.requestFullscreen();
    }
  }, "toggleFull");
  useEffect(() => {
    void load();
    const holder = mediaref.current;
    if (!holder) {
      return void 0;
    }
    let timer;
    const later = /* @__PURE__ */ __name(() => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => void load(), REDRAWAFTER);
    }, "later");
    const watcher = new MutationObserver(later);
    watcher.observe(holder, { childList: true, subtree: true });
    return () => {
      watcher.disconnect();
      window.clearTimeout(timer);
    };
  }, [load]);
  useEffect(() => {
    stripref.current?.querySelector(`[data-slide="${current}"]`)?.scrollIntoView({ block: "center", behavior: "smooth" });
  }, [current, slides]);
  const goToSlide = /* @__PURE__ */ __name((index) => {
    const textarea = document.querySelector("#id_content");
    if (!textarea) {
      return;
    }
    const at = slideEnd(textarea.value, index + 1);
    showCaret(textarea, at, at);
    redraw(textarea);
  }, "goToSlide");
  const drag = /* @__PURE__ */ __name((event) => {
    event.currentTarget.setPointerCapture(event.pointerId);
    const move = /* @__PURE__ */ __name((moving) => {
      const share = (window.innerWidth - moving.clientX) / window.innerWidth * 100;
      setSplit(Math.min(70, Math.max(20, share)));
    }, "move");
    const stop = /* @__PURE__ */ __name(() => {
      window.removeEventListener("pointermove", move);
      window.removeEventListener("pointerup", stop);
    }, "stop");
    window.addEventListener("pointermove", move);
    window.addEventListener("pointerup", stop);
  }, "drag");
  const fullbutton = /* @__PURE__ */ jsxDEV(
    "button",
    {
      type: "button",
      className: "btn btn-sm btn-secondary mudeck-editor-full",
      title: labels.fullscreen,
      "aria-pressed": full,
      onClick: toggleFull,
      children: [
        /* @__PURE__ */ jsxDEV("i", { className: `fa fa-${full ? "compress" : "expand"}`, "aria-hidden": "true" }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 736,
          columnNumber: 13
        }, this),
        /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.fullscreen }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 737,
          columnNumber: 13
        }, this)
      ]
    },
    void 0,
    true,
    {
      fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
      lineNumber: 729,
      columnNumber: 9
    },
    this
  );
  return /* @__PURE__ */ jsxDEV(Fragment, { children: [
    tools && createPortal(fullbutton, tools),
    /* @__PURE__ */ jsxDEV(
      "div",
      {
        className: "mudeck-editor-divider",
        role: "separator",
        "aria-orientation": "vertical",
        "aria-label": labels.preview,
        "aria-valuenow": Math.round(split),
        tabIndex: 0,
        onPointerDown: drag,
        onKeyDown: (event) => {
          if (event.key === "ArrowLeft") {
            setSplit((was) => Math.min(70, was + 2));
          } else if (event.key === "ArrowRight") {
            setSplit((was) => Math.max(20, was - 2));
          }
        }
      },
      void 0,
      false,
      {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 745,
        columnNumber: 13
      },
      this
    ),
    menu && /* @__PURE__ */ jsxDEV(
      "ul",
      {
        className: "mudeck-editor-menu",
        ref: menuref,
        role: "listbox",
        "aria-label": labels.media,
        "data-region": "mudeck-editor-menu",
        style: { left: `${menu.left}px`, top: `${menu.top}px` },
        children: menu.items.map((name, index) => /* @__PURE__ */ jsxDEV(
          "li",
          {
            role: "option",
            "aria-selected": index === pick,
            className: `mudeck-editor-menu-item${index === pick ? " mudeck-editor-menu-current" : ""}`,
            onMouseDown: (event) => event.preventDefault(),
            onClick: () => choose(name),
            children: name
          },
          name,
          false,
          {
            fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
            lineNumber: 772,
            columnNumber: 25
          },
          this
        ))
      },
      void 0,
      false,
      {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 763,
        columnNumber: 17
      },
      this
    ),
    /* @__PURE__ */ jsxDEV("div", { className: "mudeck-editor-preview", "data-region": "mudeck-editor-preview", children: [
      /* @__PURE__ */ jsxDEV("style", { children: css }, void 0, false, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 788,
        columnNumber: 13
      }, this),
      /* @__PURE__ */ jsxDEV("ul", { className: "nav nav-underline mudeck-editor-tabs", role: "tablist", children: [
        ["preview", labels.preview],
        ["media", labels.media],
        ["help", labels.help]
      ].map(([name, label]) => /* @__PURE__ */ jsxDEV("li", { className: "nav-item", role: "presentation", children: /* @__PURE__ */ jsxDEV(
        "button",
        {
          type: "button",
          role: "tab",
          "aria-selected": tab === name,
          className: `nav-link${tab === name ? " active" : ""}`,
          onClick: () => setTab(name),
          children: label
        },
        void 0,
        false,
        {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 797,
          columnNumber: 25
        },
        this
      ) }, name, false, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 796,
        columnNumber: 21
      }, this)) }, void 0, false, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 790,
        columnNumber: 13
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "mudeck-editor-pane", hidden: tab !== "preview", children: /* @__PURE__ */ jsxDEV("ol", { className: "mudeck-editor-strip", ref: stripref, children: slides.map((slide, index) => /* @__PURE__ */ jsxDEV(
        "li",
        {
          "data-slide": index,
          className: `mudeck-editor-slide${index === current ? " mudeck-editor-slide-current" : ""}`,
          "aria-current": index === current,
          children: [
            /* @__PURE__ */ jsxDEV("div", { className: "mudeck-editor-slide-box marpit", dangerouslySetInnerHTML: { __html: slide } }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
              lineNumber: 822,
              columnNumber: 29
            }, this),
            /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "mudeck-editor-slide-jump",
                title: labels.slide.replace("{$a}", String(index + 1)),
                onClick: () => goToSlide(index),
                children: /* @__PURE__ */ jsxDEV("span", { className: "sr-only visually-hidden", children: labels.slide.replace("{$a}", String(index + 1)) }, void 0, false, {
                  fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
                  lineNumber: 829,
                  columnNumber: 33
                }, this)
              },
              void 0,
              false,
              {
                fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
                lineNumber: 823,
                columnNumber: 29
              },
              this
            ),
            /* @__PURE__ */ jsxDEV("span", { className: "mudeck-editor-number", children: labels.slide.replace("{$a}", String(index + 1)) }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
              lineNumber: 833,
              columnNumber: 29
            }, this)
          ]
        },
        index,
        true,
        {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 813,
          columnNumber: 25
        },
        this
      )) }, void 0, false, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 811,
        columnNumber: 17
      }, this) }, void 0, false, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 810,
        columnNumber: 13
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "mudeck-editor-pane", hidden: tab !== "media", children: [
        /* @__PURE__ */ jsxDEV("div", { ref: mediaref }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 843,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("p", { className: "text-muted mudeck-editor-intro", children: labels.mediaintro }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 844,
          columnNumber: 17
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 841,
        columnNumber: 13
      }, this),
      /* @__PURE__ */ jsxDEV("div", { className: "mudeck-editor-pane", hidden: tab !== "help", children: [
        /* @__PURE__ */ jsxDEV("h2", { className: "mudeck-editor-heading", children: labels.markdownhelp }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 848,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("div", { dangerouslySetInnerHTML: { __html: help?.markdown ?? "" } }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 849,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("h2", { className: "mudeck-editor-heading", children: labels.mediahelp }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 850,
          columnNumber: 17
        }, this),
        /* @__PURE__ */ jsxDEV("div", { dangerouslySetInnerHTML: { __html: help?.media ?? "" } }, void 0, false, {
          fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
          lineNumber: 851,
          columnNumber: 17
        }, this)
      ] }, void 0, true, {
        fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
        lineNumber: 847,
        columnNumber: 13
      }, this)
    ] }, void 0, true, {
      fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
      lineNumber: 787,
      columnNumber: 9
    }, this)
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/editor.tsx",
    lineNumber: 742,
    columnNumber: 9
  }, this);
}
__name(Editor, "Editor");
export {
  Editor as default
};
//# sourceMappingURL=editor.dev.js.map
