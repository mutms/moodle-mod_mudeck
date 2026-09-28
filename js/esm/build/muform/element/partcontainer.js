import l from"@moodle/lms/tool_mulib/muform/element";import{mountReactApp as i}from"@moodle/lms/core/mount";import u from"../../editor";/**
 * Browser side of the part editor layout: tabs of the right pane and the slide preview.
 *
 * @module     mod_mudeck/muform/element/partcontainer
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */class T extends l{constructor(e,c){super(e,c);const r=Array.from(e.querySelectorAll("[data-mudeck-tab]")),d=Array.from(e.querySelectorAll("[data-mudeck-pane]"));r.forEach(o=>o.addEventListener("click",()=>{r.forEach(t=>{const m=t===o;t.classList.toggle("active",m),t.setAttribute("aria-selected",String(m))}),d.forEach(t=>{t.hidden=t.dataset.mudeckPane!==o.dataset.mudeckTab})}));const a=e.querySelector('[data-muform-name="content"] textarea'),n=e.querySelector('[data-region="mudeck-editor-slides"]');if(!a||!n)return;const s={...JSON.parse(e.dataset.mudeckEditor??"{}"),textarea:a,caretstart:e.querySelector('input[name="caretstart"]'),caretend:e.querySelector('input[name="caretend"]'),mediapane:e.querySelector('[data-mudeck-pane="media"]')};i(n,u,s)}}export{T as default};
