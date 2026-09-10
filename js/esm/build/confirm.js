import{requireAsync as o}from"@moodle/lms/core/amd";/**
 * Asking before something is destroyed.
 *
 * Moodle already has the dialogue for this, so the plugin borrows it rather than growing
 * a modal of its own - and falls back to the browser's own question if it cannot be had.
 *
 * @module     mod_mudeck/confirm
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */async function a(r,i,n){let t;try{t=await o("core/notification")}catch{return window.confirm(i)}try{return await t.saveCancelPromise(r,i,n),!0}catch{return!1}}export{a as confirmed};
