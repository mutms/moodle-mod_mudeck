import{Marp as y}from"@mudeck/marp-core";import{detectNeeds as P,loadPlugins as d}from"./plugins";import{filterMarkdown as R,sanitizeHtml as $}from"./sanitize";/**
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * The HTML is sanitised and the CSS never comes from the author's render (sanitize.ts);
 * optional plugins are loaded on demand (plugins.ts).
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const g=/^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/,c=/^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;function w(r,t,e){const i=r.match(g)?.[1].match(c)?.[1].trim();return i&&e.includes(i)?i:t}function M(r,t){if(!t)return r;const e=r.match(g);return e?c.test(e[1])?r.replace(e[0],`---
${e[1].replace(c,`theme: ${t}`)}
---
`):r.replace(e[0],`---
theme: ${t}
${e[1]}
---
`):`---
theme: ${t}
---

${r}`}function h(r,t,e){let s=t?`---
theme: ${t}
---
`:"";e.math&&(s+=`
$x$
`),e.code&&(s+="\n```js\n1\n```\n"),e.mermaid&&(s+="\n```mermaid\ngraph TD\n  A --> B\n```\n");const{html:i,css:n}=r.render(s);return e.mermaid?n+`
`+N(i):n}function N(r){const t=r.match(/<svg data-marp-mermaid[^>]*>\s*<style>([\s\S]*?)<\/style>/);return t?`svg[data-marp-mermaid] {
${t[1].replace(/@import[^;]*;/g,"").replace(/(^|\n)[ \t]*svg[ \t]*\{/g,"$1& {")}
}`:""}function l(r,t){const e=new y({html:!1,math:!0,inlineSVG:!1,script:!1,slug:!1,emoji:{shortcode:!0,unicode:!1}});return r.forEach(s=>e.use(s())),Object.values(t).forEach(s=>{try{e.themeSet.add(s)}catch(i){window.console.error("[mudeck] could not add theme",i)}}),{marp:e,known:["default","gaia","uncover",...Object.keys(t)]}}async function u(r,t,e){const s=R(r),i=P(s),{marp:n,known:o}=l(await d(i),e),a=w(s,t,o),{html:m,comments:p}=n.render(M(s,a));return{html:$(m),css:h(n,a,i),notes:p.map(f=>f.join(`

`)),needs:i,theme:a}}async function b(r,t,e={}){const{html:s,css:i,notes:n}=await u(r,t,e);return{html:s,css:i,notes:n}}async function j(r,t={}){const e=await Promise.all(r.map(n=>u(n.markdown,n.theme,t))),s={math:e.some(n=>n.needs.math),code:e.some(n=>n.needs.code),mermaid:e.some(n=>n.needs.mermaid)},{marp:i}=l(await d(s),t);return{html:e.map(n=>n.html).join(`
`),css:e.length?h(i,e[0].theme,s):"",notes:e.flatMap(n=>n.notes),origins:e.flatMap((n,o)=>n.notes.map((a,m)=>({partid:r[o].id,parthash:r[o].hash,offset:m+1})))}}export{b as renderPart,j as renderParts};
