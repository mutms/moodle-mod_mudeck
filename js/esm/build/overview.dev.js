var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { Fragment, jsxDEV } from "react/jsx-dev-runtime";
/**
 * The parts of a presentation, shown as slide thumbnails.
 *
 * Every action is an ordinary link, so the server-rendered list works until this mounts.
 *
 * @module     mod_mudeck/overview
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { useEffect, useRef, useState } from "react";
import { confirmed } from "./confirm";
import { renderPart } from "./render";
const OPENKEY = "mudeck-overview-open";
function PositionChooser({ id, label, confirm, count, current, onPick, onCancel }) {
  const ref = useRef(null);
  useEffect(() => {
    ref.current?.focus();
  }, []);
  return /* @__PURE__ */ jsxDEV(Fragment, { children: [
    /* @__PURE__ */ jsxDEV("label", { className: "visually-hidden", htmlFor: id, children: label }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
      lineNumber: 91,
      columnNumber: 13
    }, this),
    /* @__PURE__ */ jsxDEV(
      "select",
      {
        ref,
        id,
        className: "form-select form-select-sm mudeck-part-position",
        defaultValue: current,
        onKeyDown: (event) => {
          if (event.key === "Escape") {
            onCancel();
          }
        },
        children: Array.from({ length: count }, (_value, place) => /* @__PURE__ */ jsxDEV("option", { value: place + 1, children: place + 1 }, place, false, {
          fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
          lineNumber: 104,
          columnNumber: 21
        }, this))
      },
      void 0,
      false,
      {
        fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
        lineNumber: 92,
        columnNumber: 13
      },
      this
    ),
    /* @__PURE__ */ jsxDEV(
      "button",
      {
        type: "button",
        className: "btn btn-sm btn-primary mudeck-part-position-go",
        onClick: () => onPick(Number(ref.current?.value ?? current)),
        children: confirm
      },
      void 0,
      false,
      {
        fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
        lineNumber: 107,
        columnNumber: 13
      },
      this
    )
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
    lineNumber: 90,
    columnNumber: 9
  }, this);
}
__name(PositionChooser, "PositionChooser");
function Overview({ parts, themecss, labels, parturl, sesskey }) {
  const thumbsref = useRef(null);
  const [open, setOpen] = useState(null);
  const [rendered, setRendered] = useState({});
  const [order, setOrder] = useState(parts);
  const [dragging, setDragging] = useState(null);
  const [choosing, setChoosing] = useState(null);
  const [announcement, setAnnouncement] = useState("");
  useEffect(() => setOrder(parts), [parts]);
  useEffect(() => {
    let remembered = null;
    try {
      remembered = window.sessionStorage.getItem(OPENKEY);
    } catch {
    }
    const id = remembered ? Number(remembered) : null;
    if (id && parts.some((part) => part.id === id)) {
      setOpen(id);
    } else if (parts.length === 1) {
      setOpen(parts[0].id);
    }
  }, [parts]);
  useEffect(() => {
    if (open === null || rendered[open]) {
      return void 0;
    }
    const part = parts.find((one) => one.id === open);
    if (!part) {
      return void 0;
    }
    let cancelled = false;
    (async () => {
      const { html, css } = await renderPart(part.markdown, part.theme, themecss ?? {});
      if (cancelled) {
        return;
      }
      const holder = document.createElement("div");
      holder.innerHTML = html;
      setRendered((all) => ({
        ...all,
        [open]: {
          slides: Array.from(holder.querySelectorAll("section")).map((one) => one.outerHTML),
          css
        }
      }));
    })();
    return () => {
      cancelled = true;
    };
  }, [open, parts, themecss, rendered]);
  useEffect(() => {
    const list = thumbsref.current;
    if (!list) {
      return void 0;
    }
    const fit = /* @__PURE__ */ __name(() => {
      const first = list.querySelector(".mudeck-thumb-link");
      if (first) {
        list.style.setProperty("--mudeck-thumb-width", String(first.clientWidth));
      }
    }, "fit");
    fit();
    window.addEventListener("resize", fit);
    return () => window.removeEventListener("resize", fit);
  }, [open, rendered]);
  const remove = /* @__PURE__ */ __name(async (part) => {
    const question = labels.deleteconfirm.replace("{$a}", part.name);
    if (!await confirmed(labels.deletetitle, question, labels.delete)) {
      return;
    }
    const previous = order;
    setOrder(order.filter((one) => one.id !== part.id));
    try {
      const response = await fetch(`${parturl}${part.id}`, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ sesskey })
      });
      if (!response.ok) {
        setOrder(previous);
      }
    } catch {
      setOrder(previous);
    }
  }, "remove");
  const moveTo = /* @__PURE__ */ __name((id, position) => {
    const from = order.findIndex((part) => part.id === id);
    const to = position - 1;
    if (from < 0 || to < 0 || to >= order.length || to === from) {
      return;
    }
    const previous = order;
    const moved = [...order];
    moved.splice(to, 0, ...moved.splice(from, 1));
    setOrder(moved);
    setAnnouncement(labels.moved.replace("{$a}", String(position)));
    (async () => {
      try {
        const response = await fetch(`${parturl}${id}/move`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ sesskey, position })
        });
        if (!response.ok) {
          setOrder(previous);
        }
      } catch {
        setOrder(previous);
      }
    })();
  }, "moveTo");
  const toggle = /* @__PURE__ */ __name((id) => {
    const next = open === id ? null : id;
    setOpen(next);
    try {
      if (next === null) {
        window.sessionStorage.removeItem(OPENKEY);
      } else {
        window.sessionStorage.setItem(OPENKEY, String(next));
      }
    } catch {
    }
  }, "toggle");
  if (!order.length) {
    return /* @__PURE__ */ jsxDEV("div", { className: "alert alert-info", role: "status", children: labels.noparts }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
      lineNumber: 271,
      columnNumber: 13
    }, this);
  }
  return /* @__PURE__ */ jsxDEV("div", { className: "mudeck-parts", children: [
    /* @__PURE__ */ jsxDEV("div", { className: "visually-hidden", "aria-live": "polite", children: announcement }, void 0, false, {
      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
      lineNumber: 277,
      columnNumber: 13
    }, this),
    order.map((part, index) => /* @__PURE__ */ jsxDEV(
      "section",
      {
        className: `mudeck-part${dragging === part.id ? " mudeck-part-dragging" : ""}`,
        draggable: true,
        onDragStart: () => setDragging(part.id),
        onDragEnd: () => setDragging(null),
        onDragOver: (event) => event.preventDefault(),
        onDrop: (event) => {
          event.preventDefault();
          if (dragging && dragging !== part.id) {
            moveTo(dragging, index + 1);
          }
          setDragging(null);
        },
        children: [
          /* @__PURE__ */ jsxDEV("div", { className: "mudeck-part-header", children: [
            /* @__PURE__ */ jsxDEV(
              "button",
              {
                type: "button",
                className: "mudeck-part-toggle",
                onClick: () => toggle(part.id),
                "aria-expanded": open === part.id,
                "aria-controls": `mudeck-part-${part.id}`,
                children: [
                  /* @__PURE__ */ jsxDEV(
                    "i",
                    {
                      className: `fa fa-chevron-${open === part.id ? "down" : "right"}`,
                      "aria-hidden": "true"
                    },
                    void 0,
                    false,
                    {
                      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                      lineNumber: 302,
                      columnNumber: 29
                    },
                    this
                  ),
                  /* @__PURE__ */ jsxDEV("span", { className: "mudeck-part-name", children: part.name }, void 0, false, {
                    fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                    lineNumber: 306,
                    columnNumber: 29
                  }, this)
                ]
              },
              void 0,
              true,
              {
                fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                lineNumber: 295,
                columnNumber: 25
              },
              this
            ),
            /* @__PURE__ */ jsxDEV("div", { className: "mudeck-part-actions", children: [
              /* @__PURE__ */ jsxDEV("a", { href: part.editurl, className: "btn btn-sm btn-primary", children: labels.edit }, void 0, false, {
                fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                lineNumber: 310,
                columnNumber: 29
              }, this),
              order.length > 1 && choosing !== part.id && /* @__PURE__ */ jsxDEV(
                "button",
                {
                  type: "button",
                  className: "btn btn-sm btn-secondary mudeck-part-move",
                  onClick: () => setChoosing(part.id),
                  title: labels.move,
                  children: [
                    /* @__PURE__ */ jsxDEV("i", { className: "fa fa-arrows-up-down-left-right", "aria-hidden": "true" }, void 0, false, {
                      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                      lineNumber: 318,
                      columnNumber: 37
                    }, this),
                    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: labels.move }, void 0, false, {
                      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                      lineNumber: 319,
                      columnNumber: 37
                    }, this)
                  ]
                },
                void 0,
                true,
                {
                  fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                  lineNumber: 312,
                  columnNumber: 33
                },
                this
              ),
              choosing === part.id && /* @__PURE__ */ jsxDEV(
                PositionChooser,
                {
                  id: `mudeck-move-${part.id}`,
                  label: labels.moveto,
                  confirm: labels.move,
                  count: order.length,
                  current: index + 1,
                  onPick: (position) => {
                    moveTo(part.id, position);
                    setChoosing(null);
                  },
                  onCancel: () => setChoosing(null)
                },
                void 0,
                false,
                {
                  fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                  lineNumber: 323,
                  columnNumber: 33
                },
                this
              ),
              /* @__PURE__ */ jsxDEV("a", { href: part.exporturl, className: "btn btn-sm btn-secondary", title: labels.export, children: [
                /* @__PURE__ */ jsxDEV("i", { className: "fa fa-download", "aria-hidden": "true" }, void 0, false, {
                  fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                  lineNumber: 337,
                  columnNumber: 33
                }, this),
                /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: labels.export }, void 0, false, {
                  fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                  lineNumber: 338,
                  columnNumber: 33
                }, this)
              ] }, void 0, true, {
                fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                lineNumber: 336,
                columnNumber: 29
              }, this),
              /* @__PURE__ */ jsxDEV(
                "button",
                {
                  type: "button",
                  className: "btn btn-sm btn-outline-danger",
                  onClick: () => remove(part),
                  title: labels.delete,
                  children: [
                    /* @__PURE__ */ jsxDEV("i", { className: "fa fa-trash", "aria-hidden": "true" }, void 0, false, {
                      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                      lineNumber: 346,
                      columnNumber: 33
                    }, this),
                    /* @__PURE__ */ jsxDEV("span", { className: "visually-hidden", children: labels.delete }, void 0, false, {
                      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                      lineNumber: 347,
                      columnNumber: 33
                    }, this)
                  ]
                },
                void 0,
                true,
                {
                  fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                  lineNumber: 340,
                  columnNumber: 29
                },
                this
              )
            ] }, void 0, true, {
              fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
              lineNumber: 309,
              columnNumber: 25
            }, this)
          ] }, void 0, true, {
            fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
            lineNumber: 294,
            columnNumber: 21
          }, this),
          open === part.id && /* @__PURE__ */ jsxDEV("div", { className: "mudeck-part-body", id: `mudeck-part-${part.id}`, children: [
            !part.slides && /* @__PURE__ */ jsxDEV("p", { className: "text-muted", children: labels.empty }, void 0, false, {
              fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
              lineNumber: 354,
              columnNumber: 46
            }, this),
            !!part.slides && rendered[part.id] && /* @__PURE__ */ jsxDEV(Fragment, { children: [
              /* @__PURE__ */ jsxDEV("style", { children: rendered[part.id].css }, void 0, false, {
                fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                lineNumber: 357,
                columnNumber: 37
              }, this),
              /* @__PURE__ */ jsxDEV("ol", { className: "mudeck-thumbs", ref: thumbsref, children: rendered[part.id].slides.map((slide, index2) => /* @__PURE__ */ jsxDEV("li", { className: "mudeck-thumb", children: /* @__PURE__ */ jsxDEV(
                "a",
                {
                  href: `${part.previewurl}&slide=${index2 + 1}`,
                  className: "mudeck-thumb-link",
                  title: `${labels.show} ${index2 + 1}`,
                  children: [
                    /* @__PURE__ */ jsxDEV(
                      "div",
                      {
                        className: "mudeck-thumb-slide marpit",
                        "aria-hidden": "true",
                        dangerouslySetInnerHTML: { __html: slide }
                      },
                      void 0,
                      false,
                      {
                        fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                        lineNumber: 367,
                        columnNumber: 53
                      },
                      this
                    ),
                    /* @__PURE__ */ jsxDEV("span", { className: "mudeck-thumb-number", children: index2 + 1 }, void 0, false, {
                      fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                      lineNumber: 372,
                      columnNumber: 53
                    }, this)
                  ]
                },
                void 0,
                true,
                {
                  fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                  lineNumber: 361,
                  columnNumber: 49
                },
                this
              ) }, index2, false, {
                fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                lineNumber: 360,
                columnNumber: 45
              }, this)) }, void 0, false, {
                fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
                lineNumber: 358,
                columnNumber: 37
              }, this)
            ] }, void 0, true, {
              fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
              lineNumber: 356,
              columnNumber: 33
            }, this)
          ] }, void 0, true, {
            fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
            lineNumber: 353,
            columnNumber: 25
          }, this)
        ]
      },
      part.id,
      true,
      {
        fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
        lineNumber: 279,
        columnNumber: 17
      },
      this
    ))
  ] }, void 0, true, {
    fileName: "public/mod/mudeck/js/esm/src/overview.tsx",
    lineNumber: 276,
    columnNumber: 9
  }, this);
}
__name(Overview, "Overview");
export {
  Overview as default
};
//# sourceMappingURL=overview.dev.js.map
