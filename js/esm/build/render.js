import{Marp as w}from"@mudeck/marp-core";import{detectNeeds as R,loadPlugins as h}from"./plugins";import{filterMarkdown as $,sanitizeHtml as M}from"./sanitize";/**
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * The HTML is sanitised and the CSS never comes from the author's render (sanitize.ts);
 * optional plugins are loaded on demand (plugins.ts).
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const u=/^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/,c=/^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;function N(r,t,e){const i=r.match(u)?.[1].match(c)?.[1].trim();return i&&e.includes(i)?i:t}function g(r,t){if(!t)return r;const e=r.match(u);return e?c.test(e[1])?r.replace(e[0],`---
${e[1].replace(c,`theme: ${t}`)}
---
`):r.replace(e[0],`---
theme: ${t}
${e[1]}
---
`):`---
theme: ${t}
---

${r}`}function p(r,t,e){let s=t?`---
theme: ${t}
---
`:"";e.math&&(s+=`
$x$
`),e.code&&(s+="\n```js\n1\n```\n"),e.mermaid&&(s+="\n```mermaid\ngraph TD\n  A --> B\n```\n");const{html:i,css:n}=r.render(s);return e.mermaid?n+`
`+S(i):n}function S(r){const t=r.match(/<svg data-marp-mermaid[^>]*>\s*<style>([\s\S]*?)<\/style>/);return t?`svg[data-marp-mermaid] {
${t[1].replace(/@import[^;]*;/g,"").replace(/(^|\n)[ \t]*svg[ \t]*\{/g,"$1& {")}
}`:""}function l(r,t){const e=new w({html:!1,math:!0,inlineSVG:!1,script:!1,slug:!1,emoji:{shortcode:!0,unicode:!1}});return r.forEach(s=>e.use(s())),Object.values(t).forEach(s=>{try{e.themeSet.add(s)}catch(i){window.console.error("[mudeck] could not add theme",i)}}),{marp:e,known:["default","gaia","uncover",...Object.keys(t)]}}async function f(r,t,e){const s=$(r);let i=R(s),{marp:n,known:o}=l(await h(i),e);const a=N(s,t,o);let d;try{d=n.render(g(s,a))}catch(m){window.console.error("[mudeck] plugin rendering failed, showing plain slides",m),i={math:!1,code:!1,mermaid:!1},{marp:n,known:o}=l([],e),d=n.render(g(s,a))}const{html:y,comments:P}=d;return{html:M(y),css:p(n,a,i),notes:P.map(m=>m.join(`

`)),needs:i,theme:a}}async function b(r,t,e={}){const{html:s,css:i,notes:n}=await f(r,t,e);return{html:s,css:i,notes:n}}async function j(r,t={}){const e=await Promise.all(r.map(n=>f(n.markdown,n.theme,t))),s={math:e.some(n=>n.needs.math),code:e.some(n=>n.needs.code),mermaid:e.some(n=>n.needs.mermaid)},{marp:i}=l(await h(s),t);return{html:e.map(n=>n.html).join(`
`),css:e.length?p(i,e[0].theme,s):"",notes:e.flatMap(n=>n.notes),origins:e.flatMap((n,o)=>n.notes.map((a,d)=>({partid:r[o].id,parthash:r[o].hash,offset:d+1})))}}export{b as renderPart,j as renderParts};
