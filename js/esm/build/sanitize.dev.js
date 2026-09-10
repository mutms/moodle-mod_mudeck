var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Everything that keeps an untrusted deck harmless.
 *
 * Slides may be written by students, so nothing an author types is trusted, and
 * neither is what Marp makes of it. The rendered HTML is sanitised here before it
 * reaches the page, inline styles included; the CSS never comes from the author's
 * render at all (see render.ts). The Markdown filter in this module is an allowlist
 * of features, not a security boundary: it decides which directives a deck may use,
 * and it happens to throw away author CSS before Marp spends time on it.
 *
 * @module     mod_mudeck/sanitize
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { DOMPurify } from "@mudeck/marp-core";
const ALLOWEDDIRECTIVES = [
  "theme",
  "paginate",
  "header",
  "footer",
  "class",
  "marp"
];
const STYLEBLOCK = /<style\b[\s\S]*?<\/style\s*>/gi;
const STYLEOPEN = /<style\b[\s\S]*$/i;
const COMMENT = /<!--([\s\S]*?)-->/g;
const isAllowedDirective = /* @__PURE__ */ __name((line) => {
  const match = line.match(/^\s*(_?)([A-Za-z][\w-]*)\s*:/);
  if (!match) {
    return false;
  }
  return ALLOWEDDIRECTIVES.includes(match[2]);
}, "isAllowedDirective");
const looksLikeDirective = /* @__PURE__ */ __name((line) => /^\s*_?[A-Za-z][\w-]*\s*:/.test(line), "looksLikeDirective");
const filterDirectiveBlock = /* @__PURE__ */ __name((body) => {
  const lines = body.split("\n");
  const directives = lines.filter(looksLikeDirective);
  if (!directives.length) {
    return body;
  }
  const kept = [];
  let dropped = null;
  for (const line of lines) {
    const indent = (line.match(/^[ \t]*/) ?? [""])[0].length;
    if (looksLikeDirective(line) && (dropped === null || indent <= dropped)) {
      dropped = isAllowedDirective(line) ? null : indent;
      if (dropped === null) {
        kept.push(line);
      }
      continue;
    }
    if (dropped !== null && (line.trim() === "" || indent > dropped)) {
      continue;
    }
    dropped = null;
    kept.push(line);
  }
  return kept.join("\n");
}, "filterDirectiveBlock");
function filterMarkdown(markdown) {
  let out = markdown.replace(STYLEBLOCK, "").replace(STYLEOPEN, "");
  const frontmatter = out.match(/^(\s*)---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/);
  if (frontmatter) {
    const filtered = filterDirectiveBlock(frontmatter[2]);
    out = out.replace(frontmatter[0], `${frontmatter[1]}---
${filtered}
---
`);
  }
  out = out.replace(COMMENT, (whole, body) => `<!--${filterDirectiveBlock(body)}-->`);
  return out;
}
__name(filterMarkdown, "filterMarkdown");
const ALLOWEDSTYLES = {
  // A single quoted url. What is inside came through markdown-it, which has already
  // percent-encoded quotes, backslashes and whitespace; the scheme is checked below.
  "background-image": /^url\("[^"'\\()\s]*"\)$/,
  "background-size": /^(?:cover|contain|auto|\d*\.?\d+(?:px|%)?)(?: (?:auto|\d*\.?\d+(?:px|%)?))?$/,
  "filter": /^(?:[a-z-]+\(\d*\.?\d+(?:px|%|deg)?\) ?)+$/
};
const ALLOWEDURL = /^url\("(?:https?:\/\/|data:image\/|[^:]*$)/i;
let probe = null;
const cleanStyle = /* @__PURE__ */ __name((value) => {
  probe = probe ?? document.createElement("span");
  probe.style.cssText = value;
  const kept = [];
  for (let i = 0; i < probe.style.length; i++) {
    const name = probe.style.item(i);
    const pattern = ALLOWEDSTYLES[name];
    if (!pattern) {
      continue;
    }
    const declared = probe.style.getPropertyValue(name).trim();
    if (!pattern.test(declared)) {
      continue;
    }
    if (name === "background-image" && !ALLOWEDURL.test(declared)) {
      continue;
    }
    kept.push(`${name}:${declared}`);
  }
  probe.style.cssText = "";
  return kept.join(";");
}, "cleanStyle");
DOMPurify.addHook("uponSanitizeAttribute", (node, data) => {
  if (data.attrName !== "style") {
    return;
  }
  data.attrValue = cleanStyle(data.attrValue);
  if (!data.attrValue) {
    data.keepAttr = false;
  }
});
DOMPurify.addHook("afterSanitizeAttributes", (node) => {
  if (node.tagName === "A" && node.hasAttribute("href")) {
    node.setAttribute("target", "_blank");
    node.setAttribute("rel", "noopener noreferrer");
  }
});
function sanitizeHtml(html) {
  return DOMPurify.sanitize(html, {
    // Marp emits plain sections with data-marpit-* attributes.
    ALLOW_DATA_ATTR: true,
    // Plain HTML only: with raw HTML off and inline SVG off, Marp has no SVG to emit.
    USE_PROFILES: { html: true },
    FORBID_TAGS: ["style", "script", "iframe", "object", "embed", "form", "base", "link", "meta"],
    FORBID_ATTR: ["srcdoc", "formaction", "ping"]
  });
}
__name(sanitizeHtml, "sanitizeHtml");
export {
  filterMarkdown,
  sanitizeHtml
};
//# sourceMappingURL=sanitize.dev.js.map
