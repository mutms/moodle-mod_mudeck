import{requireAsync as n}from"@moodle/lms/core/amd";/**
 * Hand freshly rendered slides to Moodle's filters.
 *
 * Slides are built in the browser, so nothing on the server ever sees them and the site's
 * filters never run. Telling the filter subsystem about the new nodes is what makes maths
 * work: the deck carries the TeX the teacher typed, and the site's own MathJax typesets it
 * with the site's own settings. No second maths engine is shipped with this plugin.
 *
 * @module     mod_mudeck/filters
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */async function i(t){try{(await n("core_filters/events")).notifyFilterContentUpdated([t])}catch(e){window.console.error("[mudeck] could not notify the filters",e)}}export{i as filterSlides};
