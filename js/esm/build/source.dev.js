var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Find where each slide sits in the Markdown. The rules match
 * `mod_mudeck\local\part::count_slides()` on the server; keep the two in step.
 *
 * @module     mod_mudeck/source
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const FRONTMATTER = /^\s*---\r?\n[\s\S]*?\r?\n---[ \t]*(\r?\n|$)/;
const FENCE = /^[ \t]{0,3}(\x60{3,}|~{3,})/;
const RULE = /^[ \t]{0,3}(-{3,}|_{3,}|\*{3,})[ \t]*$/;
const UNDERLINABLE = /^[ \t]{0,3}([#>|]|[-*+][ \t]|\d+[.)][ \t])/;
function isBreak(line, previousblank, fence) {
  return fence === null && previousblank && RULE.test(line);
}
__name(isBreak, "isBreak");
function opensBreak(line) {
  return line.trim() === "" || UNDERLINABLE.test(line);
}
__name(opensBreak, "opensBreak");
function fenceAfter(line, fence) {
  const match = line.match(FENCE);
  if (!match) {
    return fence;
  }
  const marker = match[1].slice(0, 3);
  if (fence === null) {
    return marker;
  }
  return fence === marker ? null : fence;
}
__name(fenceAfter, "fenceAfter");
function slideBreaks(text) {
  const front = text.match(FRONTMATTER);
  let at = front ? front[0].length : 0;
  const breaks = [];
  let fence = null;
  let previousblank = true;
  while (at <= text.length) {
    const newline = text.indexOf("\n", at);
    const end = newline === -1 ? text.length : newline;
    const line = text.slice(at, end).replace(/\r$/, "");
    const opened = fenceAfter(line, fence);
    if (opened !== fence) {
      fence = opened;
      previousblank = false;
    } else if (isBreak(line, previousblank, fence)) {
      breaks.push(at);
      previousblank = false;
    } else {
      previousblank = opensBreak(line);
    }
    if (newline === -1) {
      break;
    }
    at = newline + 1;
  }
  return breaks;
}
__name(slideBreaks, "slideBreaks");
function slideEnd(text, slide) {
  const breaks = slideBreaks(text);
  const wanted = Math.min(Math.max(slide, 1), breaks.length + 1);
  if (wanted > breaks.length) {
    return text.length;
  }
  const at = breaks[wanted - 1];
  return at > 0 && text.charAt(at - 1) === "\n" ? at - 1 : at;
}
__name(slideEnd, "slideEnd");
export {
  isBreak,
  opensBreak,
  slideBreaks,
  slideEnd
};
//# sourceMappingURL=source.dev.js.map
