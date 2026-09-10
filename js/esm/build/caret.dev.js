var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Where a text area is drawing its cursor.
 *
 * A text area keeps that to itself, so the only way to find out is to lay the same text
 * out again in an element that can be measured, and look at where the next character
 * would land.
 *
 * @module     mod_mudeck/caret
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const COPIED = [
  "font-family",
  "font-size",
  "font-weight",
  "font-style",
  "font-variant",
  "letter-spacing",
  "line-height",
  "text-indent",
  "text-transform",
  "word-spacing",
  "tab-size",
  "padding-top",
  "padding-right",
  "padding-bottom",
  "padding-left"
];
function caretPoint(textarea, index) {
  const style = window.getComputedStyle(textarea);
  const mirror = document.createElement("div");
  COPIED.forEach((name) => mirror.style.setProperty(name, style.getPropertyValue(name)));
  mirror.style.position = "absolute";
  mirror.style.top = "0";
  mirror.style.left = "-9999px";
  mirror.style.visibility = "hidden";
  mirror.style.whiteSpace = "pre-wrap";
  mirror.style.overflowWrap = "break-word";
  mirror.style.boxSizing = "content-box";
  const inside = textarea.clientWidth - parseFloat(style.paddingLeft || "0") - parseFloat(style.paddingRight || "0");
  mirror.style.width = `${Math.max(0, inside)}px`;
  mirror.textContent = textarea.value.slice(0, index);
  const spot = document.createElement("span");
  spot.textContent = textarea.value.slice(index) || ".";
  mirror.appendChild(spot);
  document.body.appendChild(mirror);
  const box = textarea.getBoundingClientRect();
  const point = {
    left: box.left + parseFloat(style.borderLeftWidth || "0") + spot.offsetLeft - textarea.scrollLeft,
    top: box.top + parseFloat(style.borderTopWidth || "0") + spot.offsetTop - textarea.scrollTop,
    height: parseFloat(style.lineHeight || "0") || parseFloat(style.fontSize || "16") * 1.2
  };
  mirror.remove();
  return point;
}
__name(caretPoint, "caretPoint");
export {
  caretPoint
};
//# sourceMappingURL=caret.dev.js.map
