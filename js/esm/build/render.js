import{Marp as g}from"@mudeck/marp-core";import{filterMarkdown as p,sanitizeHtml as u}from"./sanitize";/**
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * Nothing Marp produces from an author's text is trusted: the HTML is sanitised and the
 * CSS is never taken from the author's render, see sanitize.ts.
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const m=/^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/,a=/^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;function R(e,r,t){const s=e.match(m)?.[1].match(a)?.[1].trim();return s&&t.includes(s)?s:r}function $(e,r){if(!r)return e;const t=e.match(m);return t?a.test(t[1])?e.replace(t[0],`---
${t[1].replace(a,`theme: ${r}`)}
---
`):e.replace(t[0],`---
theme: ${r}
${t[1]}
---
`):`---
theme: ${r}
---

${e}`}function S(e,r,t={}){const n=new g({html:!1,math:!1,inlineSVG:!1,script:!1,emoji:{shortcode:!0,unicode:!1}});Object.values(t).forEach(c=>{try{n.themeSet.add(c)}catch(l){window.console.error("[mudeck] could not add theme",l)}});const s=["default","gaia","uncover",...Object.keys(t)],i=p(e),o=R(i,r,s),{html:d,comments:h}=n.render($(i,o)),{css:f}=n.render(o?`---
theme: ${o}
---
`:"");return{html:u(d),css:f,notes:h.map(c=>c.join(`

`))}}function M(e,r={}){const t=e.map(n=>S(n.markdown,n.theme,r));return{html:t.map(n=>n.html).join(`
`),css:t.length?t[0].css:"",notes:t.flatMap(n=>n.notes),origins:t.flatMap((n,s)=>n.notes.map((i,o)=>({partid:e[s].id,parthash:e[s].hash,offset:o+1})))}}export{S as renderPart,M as renderParts};
