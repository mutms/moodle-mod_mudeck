/**
 * Tell the server that the last slide was reached.
 *
 * @module     mod_mudeck/completion
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const s=()=>window.M?.util;async function i(e){const n="mod_mudeck/reachedend";s()?.js_pending?.(n);try{await fetch(e.url,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({sesskey:e.sesskey}),keepalive:!0})}catch{}finally{s()?.js_complete?.(n)}}export{i as reportReachedEnd};
