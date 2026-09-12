import{Marp as P}from"@mudeck/marp-core";import{detectNeeds as R,loadPlugins as p}from"./plugins";import{filterMarkdown as S,sanitizeHtml as k}from"./sanitize";/**
 * Turn Marp Markdown into slide HTML and CSS.
 *
 * The HTML is sanitised and the CSS never comes from the author's render (sanitize.ts);
 * optional plugins are loaded on demand (plugins.ts).
 *
 * @module     mod_mudeck/render
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const u=/^---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/,m=/^[ \t]*theme[ \t]*:[ \t]*(\S+)[ \t]*$/m;function M(n,t,e){const i=n.match(u)?.[1].match(m)?.[1].trim();return i&&e.includes(i)?i:t}function g(n,t){if(!t)return n;const e=n.match(u);return e?m.test(e[1])?n.replace(e[0],`---
${e[1].replace(m,`theme: ${t}`)}
---
`):n.replace(e[0],`---
theme: ${t}
${e[1]}
---
`):`---
theme: ${t}
---

${n}`}function f(n,t,e){let s=t?`---
theme: ${t}
---
`:"";e.math&&(s+=`
$x$
`),e.code&&(s+="\n```js\n1\n```\n"),e.mermaid&&(s+="\n```mermaid\ngraph TD\n  A --> B\n```\n");const{html:i,css:r}=n.render(s);return e.mermaid?r+`
`+$(i):r}function $(n){const t=n.match(/<svg data-marp-mermaid[^>]*>\s*<style>([\s\S]*?)<\/style>/);return t?`svg[data-marp-mermaid] {
${t[1].replace(/@import[^;]*;/g,"").replace(/(^|\n)[ \t]*svg[ \t]*\{/g,"$1& {")}
}`:""}const N=["fade","slide","none"],v=n=>{n.marpit.customDirectives.local.transition=t=>({transition:N.includes(String(t))?String(t):void 0}),n.core.ruler.after("marpit_directives_apply","mudeck_transition",t=>{for(const e of t.tokens){const s=e.meta?.marpitDirectives?.transition;e.type==="marpit_slide_open"&&typeof s=="string"&&e.attrSet("data-transition",s)}})};function l(n,t){const e=new P({html:!1,math:!0,inlineSVG:!1,script:!1,slug:!1,emoji:{shortcode:!0,unicode:!1}});return n.forEach(s=>e.use(s())),e.use(v),Object.values(t).forEach(s=>{try{e.themeSet.add(s)}catch(i){window.console.error("[mudeck] could not add theme",i)}}),{marp:e,known:["default","gaia","uncover",...Object.keys(t)]}}async function h(n,t,e){const s=S(n);let i=R(s),{marp:r,known:o}=l(await p(i),e);const a=M(s,t,o);let d;try{d=r.render(g(s,a))}catch(c){window.console.error("[mudeck] plugin rendering failed, showing plain slides",c),i={math:!1,code:!1,mermaid:!1},{marp:r,known:o}=l([],e),d=r.render(g(s,a))}const{html:y,comments:w}=d;return{html:k(y),css:f(r,a,i),notes:w.map(c=>c.join(`

`)),needs:i,theme:a}}async function j(n,t,e={}){const{html:s,css:i,notes:r}=await h(n,t,e);return{html:s,css:i,notes:r}}async function x(n,t={}){const e=await Promise.all(n.map(r=>h(r.markdown,r.theme,t))),s={math:e.some(r=>r.needs.math),code:e.some(r=>r.needs.code),mermaid:e.some(r=>r.needs.mermaid)},{marp:i}=l(await p(s),t);return{html:e.map(r=>r.html).join(`
`),css:e.length?f(i,e[0].theme,s):"",notes:e.flatMap(r=>r.notes),origins:e.flatMap((r,o)=>r.notes.map((a,d)=>({partid:n[o].id,parthash:n[o].hash,offset:d+1})))}}export{j as renderPart,x as renderParts};
