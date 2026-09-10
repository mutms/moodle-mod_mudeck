import{DOMPurify as c}from"@mudeck/marp-core";/**
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
 */const f=["theme","paginate","header","footer","class","marp"],g=/<style\b[\s\S]*?<\/style\s*>/gi,m=/<style\b[\s\S]*$/i,p=/<!--([\s\S]*?)-->/g,d=e=>{const t=e.match(/^\s*(_?)([A-Za-z][\w-]*)\s*:/);return t?f.includes(t[2]):!1},a=e=>/^\s*_?[A-Za-z][\w-]*\s*:/.test(e),u=e=>{const t=e.split(`
`);if(!t.filter(a).length)return e;const r=[];let n=null;for(const i of t){const o=(i.match(/^[ \t]*/)??[""])[0].length;if(a(i)&&(n===null||o<=n)){n=d(i)?null:o,n===null&&r.push(i);continue}n!==null&&(i.trim()===""||o>n)||(n=null,r.push(i))}return r.join(`
`)};function L(e){let t=e.replace(g,"").replace(m,"");const s=t.match(/^(\s*)---\r?\n([\s\S]*?)\r?\n---[ \t]*(\r?\n|$)/);if(s){const r=u(s[2]);t=t.replace(s[0],`${s[1]}---
${r}
---
`)}return t=t.replace(p,(r,n)=>`<!--${u(n)}-->`),t}const A={"background-image":/^url\("[^"'\\()\s]*"\)$/,"background-size":/^(?:cover|contain|auto|\d*\.?\d+(?:px|%)?)(?: (?:auto|\d*\.?\d+(?:px|%)?))?$/,filter:/^(?:[a-z-]+\(\d*\.?\d+(?:px|%|deg)?\) ?)+$/},b=/^url\("(?:https?:\/\/|data:image\/|[^:]*$)/i;let l=null;const h=e=>{l=l??document.createElement("span"),l.style.cssText=e;const t=[];for(let s=0;s<l.style.length;s++){const r=l.style.item(s),n=A[r];if(!n)continue;const i=l.style.getPropertyValue(r).trim();n.test(i)&&(r==="background-image"&&!b.test(i)||t.push(`${r}:${i}`))}return l.style.cssText="",t.join(";")};c.addHook("uponSanitizeAttribute",(e,t)=>{t.attrName==="style"&&(t.attrValue=h(t.attrValue),t.attrValue||(t.keepAttr=!1))});c.addHook("afterSanitizeAttributes",e=>{e.tagName==="A"&&e.hasAttribute("href")&&(e.setAttribute("target","_blank"),e.setAttribute("rel","noopener noreferrer"))});function k(e){return c.sanitize(e,{ALLOW_DATA_ATTR:!0,USE_PROFILES:{html:!0},FORBID_TAGS:["style","script","iframe","object","embed","form","base","link","meta"],FORBID_ATTR:["srcdoc","formaction","ping"]})}export{L as filterMarkdown,k as sanitizeHtml};
