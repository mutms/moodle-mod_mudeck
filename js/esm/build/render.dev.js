var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
/**
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * The HTML is sanitised and the CSS never comes from the author's render (sanitize.ts);
 * optional plugins are loaded on demand (plugins.ts).
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { Marp } from "@mudeck/marp-core";
import { detectNeeds, loadPlugins } from "./plugins";
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
function stylesheet(marp, theme, needs) {
  let stub = theme ? `---
theme: ${theme}
---
` : "";
  if (needs.math) {
    stub += "\n$x$\n";
  }
  if (needs.code) {
    stub += "\n```js\n1\n```\n";
  }
  if (needs.mermaid) {
    stub += "\n```mermaid\ngraph TD\n  A --> B\n```\n";
  }
  const { html, css } = marp.render(stub);
  return needs.mermaid ? css + "\n" + mermaidCss(html) : css;
}
__name(stylesheet, "stylesheet");
function mermaidCss(html) {
  const found = html.match(/<svg data-marp-mermaid[^>]*>\s*<style>([\s\S]*?)<\/style>/);
  if (!found) {
    return "";
  }
  const rules = found[1].replace(/@import[^;]*;/g, "").replace(/(^|\n)[ \t]*svg[ \t]*\{/g, "$1& {");
  return `svg[data-marp-mermaid] {
${rules}
}`;
}
__name(mermaidCss, "mermaidCss");
function createMarp(plugins, themecss) {
  const marp = new Marp({
    // Never raw HTML from the author.
    html: false,
    // Typeset only when the MathJax plugin is loaded.
    math: true,
    // Plain sections rather than inline SVG, for screen reader reading order.
    inlineSVG: false,
    script: false,
    // Heading ids from different parts would collide on one page.
    slug: false,
    // No emoji images from a CDN.
    emoji: { shortcode: true, unicode: false }
  });
  plugins.forEach((plugin) => marp.use(plugin()));
  Object.values(themecss).forEach((css) => {
    try {
      marp.themeSet.add(css);
    } catch (e) {
      window.console.error("[mudeck] could not add theme", e);
    }
  });
  return { marp, known: ["default", "gaia", "uncover", ...Object.keys(themecss)] };
}
__name(createMarp, "createMarp");
async function renderOne(markdown, theme, themecss) {
  const safe = filterMarkdown(markdown);
  const needs = detectNeeds(safe);
  const { marp, known } = createMarp(await loadPlugins(needs), themecss);
  const chosen = chooseTheme(safe, theme, known);
  const { html, comments } = marp.render(withTheme(safe, chosen));
  return {
    html: sanitizeHtml(html),
    // Never the CSS of the author's render.
    css: stylesheet(marp, chosen, needs),
    notes: comments.map((slide) => slide.join("\n\n")),
    needs,
    theme: chosen
  };
}
__name(renderOne, "renderOne");
async function renderPart(markdown, theme, themecss = {}) {
  const { html, css, notes } = await renderOne(markdown, theme, themecss);
  return { html, css, notes };
}
__name(renderPart, "renderPart");
async function renderParts(parts, themecss = {}) {
  const rendered = await Promise.all(parts.map((part) => renderOne(part.markdown, part.theme, themecss)));
  const needs = {
    math: rendered.some((one) => one.needs.math),
    code: rendered.some((one) => one.needs.code),
    mermaid: rendered.some((one) => one.needs.mermaid)
  };
  const { marp } = createMarp(await loadPlugins(needs), themecss);
  return {
    html: rendered.map((one) => one.html).join("\n"),
    css: rendered.length ? stylesheet(marp, rendered[0].theme, needs) : "",
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
