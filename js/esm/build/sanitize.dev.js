var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Sanitise untrusted decks: the rendered HTML is cleaned here, and the CSS never comes
 * from the author's render (see render.ts). The Markdown filter is a feature allowlist,
 * not a security boundary.
 *
 * What the renderer emits, and how each piece is handled:
 *
 *  - HTML from Markdown: DOMPurify's HTML profile; raw HTML is off in Marp.
 *  - Inline styles: parsed by the browser's CSS engine, only listed properties with matching values are kept.
 *  - SVG from MathJax and Mermaid: DOMPurify's SVG profile, foreignObject and style elements forbidden.
 *  - MathJax's container: a custom element admitted by name with three attributes.
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
  "backgroundColor",
  "backgroundImage",
  "backgroundPosition",
  "backgroundRepeat",
  "backgroundSize",
  "color",
  "transition",
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
const VAR = "var\\(--marp-[a-z-]+(?:, var\\(--marp-[a-z-]+\\))?\\)";
const COLOUR = `(?:[a-z]+|#[0-9a-f]{3,8}|(?:rgb|rgba|hsl|hsla)\\([0-9., %/]+\\)|${VAR})`;
const LENGTH = "(?:0|-?\\d*\\.?\\d+(?:px|em|ex|rem|%|vw|vh))";
const ALLOWEDSTYLES = {
  // The url was percent-encoded by markdown-it; the scheme is checked separately below.
  "background-image": /^(?:none|url\("[^"'\\()\s]*"\))$/,
  "background-size": new RegExp(`^(?:cover|contain|auto|${LENGTH})(?: (?:auto|${LENGTH}))?$`),
  "background-position": new RegExp(
    `^(?:left|right|top|bottom|center|${LENGTH})(?: (?:left|right|top|bottom|center|${LENGTH}))?$`
  ),
  "background-repeat": /^(?:repeat|no-repeat|repeat-x|repeat-y|space|round)(?: (?:repeat|no-repeat|space|round))?$/,
  "background-color": new RegExp(`^${COLOUR}$`),
  "color": new RegExp(`^${COLOUR}$`),
  "filter": /^(?:[a-z-]+\(\d*\.?\d+(?:px|%|deg)?\) ?)+$/,
  "font-style": /^(?:normal|italic)$/,
  "font-weight": /^(?:normal|bold|[1-9]00)$/,
  "text-decoration": new RegExp(
    `^(?:none|underline|line-through|overline)(?: (?:solid|double|dotted|dashed|wavy))?(?: ${COLOUR})?$`
  ),
  "vertical-align": new RegExp(`^${LENGTH}$`),
  "display": /^(?:block|inline|inline-block)$/,
  "width": new RegExp(`^(?:auto|${LENGTH})$`),
  "min-width": new RegExp(`^(?:auto|${LENGTH})$`),
  "max-width": new RegExp(`^(?:none|${LENGTH})$`),
  "height": new RegExp(`^(?:auto|${LENGTH})$`),
  "max-height": new RegExp(`^(?:none|${LENGTH})$`),
  "margin-top": new RegExp(`^(?:auto|${LENGTH})$`),
  "margin-right": new RegExp(`^(?:auto|${LENGTH})$`),
  "margin-bottom": new RegExp(`^(?:auto|${LENGTH})$`),
  "margin-left": new RegExp(`^(?:auto|${LENGTH})$`)
};
const CUSTOMPROPERTY = /^--[a-z][a-z0-9_-]*$/;
const CUSTOMVALUE = new RegExp(`^${VAR}$`);
const ALLOWEDURL = /^url\("(?:https?:\/\/|data:image\/|[^:]*$)/i;
let probe = null;
const cleanStyle = /* @__PURE__ */ __name((value) => {
  probe = probe ?? document.createElement("span");
  probe.style.cssText = value;
  const kept = [];
  for (let i = 0; i < probe.style.length; i++) {
    const name = probe.style.item(i);
    const declared = probe.style.getPropertyValue(name).trim();
    if (CUSTOMPROPERTY.test(name)) {
      if (CUSTOMVALUE.test(declared)) {
        kept.push(`${name}:${declared}`);
      }
      continue;
    }
    const pattern = ALLOWEDSTYLES[name];
    if (!pattern || !pattern.test(declared)) {
      continue;
    }
    if (name === "background-image" && declared !== "none" && !ALLOWEDURL.test(declared)) {
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
const DIAGRAMSCALE = 2.2;
const DIAGRAMMAXWIDTH = 1100;
const DIAGRAMMAXHEIGHT = 540;
DOMPurify.addHook("afterSanitizeAttributes", (node) => {
  if (node.tagName.toLowerCase() !== "svg" || !node.hasAttribute("data-marp-mermaid")) {
    return;
  }
  const width = parseFloat(node.getAttribute("width") ?? "");
  const height = parseFloat(node.getAttribute("height") ?? "");
  if (!(width > 0) || !(height > 0)) {
    return;
  }
  const factor = Math.min(DIAGRAMSCALE, DIAGRAMMAXWIDTH / width, DIAGRAMMAXHEIGHT / height);
  node.setAttribute("width", (width * factor).toFixed(2));
  node.setAttribute("height", (height * factor).toFixed(2));
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
    // HTML from Markdown, SVG from MathJax and Mermaid.
    USE_PROFILES: { html: true, svg: true },
    // MathJax's container element and its three attributes.
    CUSTOM_ELEMENT_HANDLING: {
      tagNameCheck: /^mjx-[a-z-]+$/,
      attributeNameCheck: /^(?:jax|display|overflow)$/,
      allowCustomizedBuiltInElements: false
    },
    ADD_ATTR: ["focusable"],
    FORBID_TAGS: ["style", "script", "iframe", "object", "embed", "form", "base", "link", "meta", "foreignobject"],
    FORBID_ATTR: ["srcdoc", "formaction", "ping"]
  });
}
__name(sanitizeHtml, "sanitizeHtml");
export {
  filterMarkdown,
  sanitizeHtml
};
//# sourceMappingURL=sanitize.dev.js.map
