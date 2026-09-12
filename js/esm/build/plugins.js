/**
 * Optional Marp plugins (maths, code colouring, diagrams), loaded on demand and cached once per page.
 *
 * @module     mod_mudeck/plugins
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const n=/^[ \t]{0,3}(?:\x60{3,}|~{3,})[ \t]*[A-Za-z]/m,i=/^[ \t]{0,3}(?:\x60{3,}|~{3,})[ \t]*mermaid\b/m;function a(t){const r=i.test(t);return{math:t.includes("$"),code:n.test(t),mermaid:r}}const l={math:()=>import("@mudeck/marp-mathjax"),code:()=>import("@mudeck/marp-shiki"),mermaid:()=>import("@mudeck/marp-mermaid")},o={};async function c(t){const r=Object.keys(l).filter(e=>t[e]);return(await Promise.all(r.map(e=>(o[e]=o[e]??l[e]().then(s=>s.default),o[e].catch(s=>(window.console.error(`[mudeck] could not load the ${e} plugin`,s),delete o[e],null)))))).filter(e=>e!==null)}export{a as detectNeeds,c as loadPlugins};
