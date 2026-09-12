import{useEffect as m,useRef as f,useState as p}from"react";import{renderPart as h}from"./render";import{Fragment as y,jsx as n,jsxs as v}from"react/jsx-runtime";/**
 * The first slide on the welcome page, drawn inside the start card.
 *
 * @module     mod_mudeck/welcome
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function g({markdown:l,theme:o,themecss:c}){const i=f(null),[t,a]=p(null);return m(()=>{let e=!1;return(async()=>{const{html:r,css:s}=await h(l,o,c??{});if(e)return;const d=document.createElement("div");d.innerHTML=r;const u=d.querySelector("section");a(u?{html:u.outerHTML,css:s}:null)})(),()=>{e=!0}},[l,o,c]),m(()=>{const e=i.current;if(!e)return;const r=()=>e.style.setProperty("--mudeck-poster-width",String(e.clientWidth));r();const s=new ResizeObserver(r);return s.observe(e),()=>s.disconnect()},[t]),n("div",{className:"mudeck-poster-box",ref:i,"aria-hidden":"true",children:t&&v(y,{children:[n("style",{children:t.css}),n("div",{className:"mudeck-poster-slide marpit",dangerouslySetInnerHTML:{__html:t.html}})]})})}export{g as default};
