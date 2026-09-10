var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * Nothing Marp produces from an author's text is trusted: the HTML is sanitised and the
 * CSS is never taken from the author's render, see sanitize.ts.
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { Marp } from "@mudeck/marp-core";
import { filterMarkdown, sanitizeHtml } from "./sanitize";
const FRONTMATTER = /^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/;
const OWNTHEME = /^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;
function chooseTheme(markdown, theme, known) {
  const front = markdown.match(FRONTMATTER);
  const own = front?.[1].match(OWNTHEME)?.[1].trim();
  if (own && known.includes(own)) {
    return own;
  }
  return theme;
}
__name(chooseTheme, "chooseTheme");
function withTheme(markdown, theme) {
  if (!theme) {
    return markdown;
  }
  const front = markdown.match(FRONTMATTER);
  if (!front) {
    return `---
theme: ${theme}
---

${markdown}`;
  }
  if (OWNTHEME.test(front[1])) {
    return markdown.replace(front[0], `---
${front[1].replace(OWNTHEME, `theme: ${theme}`)}
---
`);
  }
  return markdown.replace(front[0], `---
theme: ${theme}
${front[1]}
---
`);
}
__name(withTheme, "withTheme");
function renderPart(markdown, theme, themecss = {}) {
  const marp = new Marp({
    // No raw HTML from the author, ever.
    html: false,
    // The bundle ships without a maths engine, so a dollar sign in a slide is a dollar
    // sign - with maths on, marp reaches for the engine that is not there and throws.
    // Formulas are typeset by the site's own filter instead, see filters.ts.
    math: false,
    // Plain HTML sections rather than inline SVG, so screen readers get a sane reading order.
    inlineSVG: false,
    script: false,
    // Shortcodes become the character itself; nothing is fetched from an emoji CDN.
    emoji: { shortcode: true, unicode: false }
  });
  Object.values(themecss).forEach((css2) => {
    try {
      marp.themeSet.add(css2);
    } catch (e) {
      window.console.error("[mudeck] could not add theme", e);
    }
  });
  const known = ["default", "gaia", "uncover", ...Object.keys(themecss)];
  const safe = filterMarkdown(markdown);
  const chosen = chooseTheme(safe, theme, known);
  const { html, comments } = marp.render(withTheme(safe, chosen));
  const { css } = marp.render(chosen ? `---
theme: ${chosen}
---
` : "");
  return {
    html: sanitizeHtml(html),
    css,
    notes: comments.map((slide) => slide.join("\n\n"))
  };
}
__name(renderPart, "renderPart");
function renderParts(parts, themecss = {}) {
  const rendered = parts.map((part) => renderPart(part.markdown, part.theme, themecss));
  return {
    html: rendered.map((one) => one.html).join("\n"),
    // Every part uses the same theme set, so the first stylesheet covers them all.
    css: rendered.length ? rendered[0].css : "",
    notes: rendered.flatMap((one) => one.notes),
    origins: rendered.flatMap((one, index) => one.notes.map((_note, slide) => ({
      partid: parts[index].id,
      parthash: parts[index].hash,
      offset: slide + 1
    })))
  };
}
__name(renderParts, "renderParts");
export {
  renderPart,
  renderParts
};
//# sourceMappingURL=render.dev.js.map
