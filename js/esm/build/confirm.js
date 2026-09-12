import{requireAsync as o}from"@moodle/lms/core/amd";/**
 * Confirmation dialogue using Moodle's core/notification, with window.confirm as fallback.
 *
 * @module     mod_mudeck/confirm
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */async function a(r,i,n){let t;try{t=await o("core/notification")}catch{return window.confirm(i)}try{return await t.saveCancelPromise(r,i,n),!0}catch{return!1}}export{a as confirmed};
