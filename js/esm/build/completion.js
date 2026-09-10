import{requireAsync as r}from"@moodle/lms/core/amd";/**
 * Tell the server that the last slide was reached.
 *
 * @module     mod_mudeck/completion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */async function s(n){let e=null;try{const i=await r("core/pending");e=new i("mod_mudeck/reachedend")}catch{e=null}try{await fetch(n.url,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({sesskey:n.sesskey}),keepalive:!0})}catch{}finally{e?.resolve()}}export{s as reportReachedEnd};
