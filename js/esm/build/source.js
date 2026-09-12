/**
 * Find where each slide sits in the Markdown. The rules match
 * `mod_mudeck\local\part::count_slides()` on the server; keep the two in step.
 *
 * @module     mod_mudeck/source
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const a=/^\s*---\r?\n[\s\S]*?\r?\n---[ \t]*(\r?\n|$)/,f=/^[ \t]{0,3}(\x60{3,}|~{3,})/,g=/^[ \t]{0,3}(-{3,}|_{3,}|\*{3,})[ \t]*$/,h=/^[ \t]{0,3}([#>|]|[-*+][ \t]|\d+[.)][ \t])/;function b(n,e,t){return t===null&&e&&g.test(n)}function m(n){return n.trim()===""||h.test(n)}function p(n,e){const t=n.match(f);if(!t)return e;const s=t[1].slice(0,3);return e===null?s:e===s?null:e}function d(n){const e=n.match(a);let t=e?e[0].length:0;const s=[];let r=null,l=!0;for(;t<=n.length;){const o=n.indexOf(`
`,t),c=o===-1?n.length:o,i=n.slice(t,c).replace(/\r$/,""),u=p(i,r);if(u!==r?(r=u,l=!1):b(i,l,r)?(s.push(t),l=!1):l=m(i),o===-1)break;t=o+1}return s}function k(n,e){const t=d(n),s=Math.min(Math.max(e,1),t.length+1);if(s>t.length)return n.length;const r=t[s-1];return r>0&&n.charAt(r-1)===`
`?r-1:r}export{b as isBreak,m as opensBreak,d as slideBreaks,k as slideEnd};
