var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Deck runtime on top of marp-core output: shows one slide at a time, scales it to the
 * viewport, and handles keyboard, swipe and edge tap input. Plain DOM; the chrome is React.
 *
 * @module     mod_mudeck/presenter
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const FALLBACKWIDTH = 1280;
const FALLBACKHEIGHT = 720;
const SWIPEDISTANCE = 50;
const TAPZONE = 0.25;
const DEFAULTTRANSITION = "fade";
const startViewTransition = document.startViewTransition?.bind(document);
const sharedKey = /* @__PURE__ */ __name((el) => {
  if (el instanceof HTMLImageElement) {
    const src = el.getAttribute("src");
    return src ? `img:${src}` : null;
  }
  const text = el.textContent?.trim();
  return text ? `${el.tagName}:${text}` : null;
}, "sharedKey");
const tagShared = /* @__PURE__ */ __name((from, to) => {
  const candidates = /* @__PURE__ */ __name((slide) => Array.from(slide.querySelectorAll("img, h1, h2, h3")), "candidates");
  const before = /* @__PURE__ */ new Map();
  for (const el of candidates(from)) {
    const key = sharedKey(el);
    if (key && !before.has(key)) {
      before.set(key, el);
    }
  }
  const tagged = [];
  const used = /* @__PURE__ */ new Set();
  for (const el of candidates(to)) {
    const key = sharedKey(el);
    const match = key ? before.get(key) : void 0;
    if (!key || !match || used.has(key)) {
      continue;
    }
    used.add(key);
    const name = `mudeck-shared-${tagged.length}`;
    match.style.setProperty("view-transition-name", name);
    el.style.setProperty("view-transition-name", name);
    tagged.push(match, el);
  }
  return tagged;
}, "tagShared");
const isInteractive = /* @__PURE__ */ __name((target) => {
  const el = target instanceof Element ? target.closest("a,button,input,select,textarea,summary,[role=button]") : null;
  return el !== null;
}, "isInteractive");
const isTextEntry = /* @__PURE__ */ __name((target) => {
  if (!(target instanceof Element)) {
    return false;
  }
  return target.closest('input,textarea,select,[contenteditable=""],[contenteditable=true]') !== null;
}, "isTextEntry");
const isActivatable = /* @__PURE__ */ __name((target) => {
  if (!(target instanceof Element)) {
    return false;
  }
  return target.closest("a,button,summary,[role=button]") !== null;
}, "isActivatable");
function mountPresenter(container, onState) {
  const slides = Array.from(container.querySelectorAll("section"));
  if (!slides.length) {
    return {
      next: /* @__PURE__ */ __name(() => void 0, "next"),
      previous: /* @__PURE__ */ __name(() => void 0, "previous"),
      "goto": /* @__PURE__ */ __name(() => void 0, "goto"),
      toggleFullscreen: /* @__PURE__ */ __name(() => void 0, "toggleFullscreen"),
      focus: /* @__PURE__ */ __name(() => void 0, "focus"),
      titles: /* @__PURE__ */ __name(() => [], "titles"),
      destroy: /* @__PURE__ */ __name(() => void 0, "destroy")
    };
  }
  let index = 0;
  let running = null;
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const fit = /* @__PURE__ */ __name(() => {
    const slide = slides[index];
    const width = slide.offsetWidth || FALLBACKWIDTH;
    const height = slide.offsetHeight || FALLBACKHEIGHT;
    const scale = Math.min(container.clientWidth / width, container.clientHeight / height);
    container.style.setProperty("--mudeck-scale", String(scale > 0 ? scale : 1));
    container.style.setProperty("--mudeck-slide-width", `${width}px`);
    container.style.setProperty("--mudeck-slide-height", `${height}px`);
  }, "fit");
  const show = /* @__PURE__ */ __name((next) => {
    const target = Math.max(0, Math.min(slides.length - 1, next));
    const change = /* @__PURE__ */ __name(() => {
      index = target;
      slides.forEach((slide, i) => {
        slide.hidden = i !== index;
      });
      fit();
      onState({ current: index + 1, total: slides.length });
    }, "change");
    const kind = slides[target].dataset.transition ?? DEFAULTTRANSITION;
    if (target === index || kind === "none" || reducedMotion.matches || !startViewTransition) {
      change();
      return;
    }
    running?.skipTransition();
    const root = document.documentElement;
    root.dataset.mudeckTransition = kind;
    root.dataset.mudeckDirection = target > index ? "forward" : "back";
    const shared = tagShared(slides[index], slides[target]);
    const transition = startViewTransition(change);
    running = transition;
    transition.finished.finally(() => {
      shared.forEach((el) => el.style.removeProperty("view-transition-name"));
      if (running === transition) {
        delete root.dataset.mudeckTransition;
        delete root.dataset.mudeckDirection;
        running = null;
      }
    });
  }, "show");
  const showAndFit = show;
  const onKey = /* @__PURE__ */ __name((event) => {
    if (isTextEntry(event.target)) {
      return;
    }
    switch (event.key) {
      case " ":
        if (isActivatable(event.target)) {
          return;
        }
        showAndFit(index + 1);
        event.preventDefault();
        break;
      case "ArrowRight":
      case "PageDown":
        showAndFit(index + 1);
        event.preventDefault();
        break;
      case "ArrowLeft":
      case "PageUp":
        showAndFit(index - 1);
        event.preventDefault();
        break;
      case "Home":
        showAndFit(0);
        event.preventDefault();
        break;
      case "End":
        showAndFit(slides.length - 1);
        event.preventDefault();
        break;
      default:
        break;
    }
  }, "onKey");
  let startx = 0;
  let starty = 0;
  let dragging = false;
  const onPointerDown = /* @__PURE__ */ __name((event) => {
    if (isInteractive(event.target)) {
      return;
    }
    dragging = true;
    startx = event.clientX;
    starty = event.clientY;
  }, "onPointerDown");
  const onPointerUp = /* @__PURE__ */ __name((event) => {
    if (!dragging || isInteractive(event.target)) {
      dragging = false;
      return;
    }
    dragging = false;
    const dx = event.clientX - startx;
    const dy = event.clientY - starty;
    if (Math.abs(dx) > SWIPEDISTANCE && Math.abs(dx) > Math.abs(dy)) {
      showAndFit(dx < 0 ? index + 1 : index - 1);
      return;
    }
    if (Math.abs(dx) < SWIPEDISTANCE && Math.abs(dy) < SWIPEDISTANCE) {
      const rect = container.getBoundingClientRect();
      const relative = (event.clientX - rect.left) / rect.width;
      if (relative <= TAPZONE) {
        showAndFit(index - 1);
      } else if (relative >= 1 - TAPZONE) {
        showAndFit(index + 1);
      }
    }
  }, "onPointerUp");
  const onResize = /* @__PURE__ */ __name(() => fit(), "onResize");
  showAndFit(0);
  window.setTimeout(fit, 100);
  document.addEventListener("keydown", onKey);
  window.addEventListener("resize", onResize);
  document.addEventListener("fullscreenchange", onResize);
  container.addEventListener("pointerdown", onPointerDown, { passive: true });
  container.addEventListener("pointerup", onPointerUp, { passive: true });
  return {
    next: /* @__PURE__ */ __name(() => showAndFit(index + 1), "next"),
    previous: /* @__PURE__ */ __name(() => showAndFit(index - 1), "previous"),
    "goto": /* @__PURE__ */ __name((target) => showAndFit(target - 1), "goto"),
    focus: /* @__PURE__ */ __name(() => container.focus({ preventScroll: true }), "focus"),
    titles: /* @__PURE__ */ __name(() => slides.map((slide, i) => {
      const heading = slide.querySelector("h1,h2,h3,h4")?.textContent?.trim();
      return heading || String(i + 1);
    }), "titles"),
    toggleFullscreen: /* @__PURE__ */ __name(() => {
      const root = container.closest(".mudeck-deck") ?? container;
      if (!document.fullscreenElement) {
        root.requestFullscreen?.();
      } else {
        document.exitFullscreen?.();
      }
    }, "toggleFullscreen"),
    destroy: /* @__PURE__ */ __name(() => {
      document.removeEventListener("keydown", onKey);
      window.removeEventListener("resize", onResize);
      document.removeEventListener("fullscreenchange", onResize);
      container.removeEventListener("pointerdown", onPointerDown);
      container.removeEventListener("pointerup", onPointerUp);
    }, "destroy")
  };
}
__name(mountPresenter, "mountPresenter");
export {
  mountPresenter
};
//# sourceMappingURL=presenter.dev.js.map
