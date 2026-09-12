<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Strings.ForbiddenStrings.Found

/**
 * Markdown slide deck language pack.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowdevicesync'] = 'Allow device sync';
$string['allowdevicesync_help'] = 'When enabled, a running presentation remembers where it is, so the same user can follow it on another device - a phone or tablet beside the machine doing the presenting. Nothing is remembered unless somebody starts the presentation, everybody sees only their own sessions, and turning this off deletes them all.';
$string['back'] = 'Back';
$string['completiondetail:reachedend'] = 'Reach the last slide';
$string['completionreachedend'] = 'Student must reach the last slide';
$string['editor_help'] = 'Help';
$string['editor_preview'] = 'Preview';
$string['editor_slide'] = 'Slide {$a}';
$string['event_part_created'] = 'Part created';
$string['event_part_deleted'] = 'Part deleted';
$string['event_part_exported'] = 'Part exported';
$string['event_part_imported'] = 'Part imported';
$string['event_part_updated'] = 'Part updated';
$string['event_presentation_started'] = 'Presentation started';
$string['export'] = 'Export';
$string['help_markdown'] = 'Slides are written in Markdown. A line holding nothing but three dashes starts a new slide, with an empty line above and below it.

    ---
    theme: orange
    paginate: true
    ---

    # A title slide

    What the talk is about

    ---

    ## A slide with points

    - **bold** and *italic* and `code`
    - [a link](https://example.org)
    - ![a picture](photo.jpg)
    - maths: $x^2$ in a sentence, or $$ ... $$ on its own lines

    <!-- A comment that is not a directive is a speaker note. -->

    ---

    ## Code and diagrams

    ```php
    echo "coloured by language";
    ```

    ```mermaid
    graph LR
      A[Idea] --> B[Slides]
    ```

    ---

    <!-- _class: lead -->

    ![bg](background.jpg)

    # A slide with a background

Front matter at the very top sets the whole deck; a comment with an underscore, such as `_class`, `_backgroundColor` or `_color`, sets one slide only.

Pictures are referred to by file name and uploaded under Media. Raw HTML and `<style>` blocks are dropped, so a slide cannot bring its own markup.';
$string['help_media'] = 'Media the slides refer to by file name. Upload a file here, then use its name in the Markdown.

    ![a photograph](photo.jpg)

    ![bg](background.jpg)

    ![bg left](background.jpg)

    ![bg fit](diagram.png)

    ![w:400](logo.png)

A picture with `bg` fills the slide behind the text; add `left` or `right` to keep it on one side, and `fit` to show all of it instead of cropping. Sizes are set with `w:` and `h:` in pixels.

Files can sit in folders, and then the name includes the folder: `![](pictures/city.jpg)`. Animated GIF and WebP work like any other picture.';
$string['import'] = 'Import';
$string['import_file'] = 'Markdown or zip';
$string['import_intro'] = 'Upload the slides as a Markdown file, or as one zip holding a Markdown file and the media it uses. Either way the slides become a new part.';
$string['import_nothing'] = 'Nothing was imported. Upload a Markdown file, or a zip holding exactly one Markdown file and its media.';
$string['import_submit'] = 'Import slides';
$string['loading'] = 'Loading the presentation…';
$string['media_intro'] = 'Upload the pictures and other files the slides use. Refer to them in the Markdown by file name, exactly as they are listed here - the Help tab shows how.';
$string['modulename'] = 'Markdown slide deck';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Markdown slide decks';
$string['mudeck:addinstance'] = 'Add a new presentation';
$string['mudeck:edit'] = 'Edit slides';
$string['mudeck:fullaccess'] = 'Access all slides and speaker notes at any time';
$string['mudeck:managethemes'] = 'Add and edit the site\'s own slide themes';
$string['mudeck:present'] = 'Present or watch the slides';
$string['mudeck:syncdevices'] = 'Follow own presentation on another device';
$string['mudeck:view'] = 'View presentation details';
$string['nopartcontent'] = 'This presentation has no slides yet.';
$string['notes_current'] = 'On screen';
$string['notes_elapsed'] = 'Time from the start';
$string['notes_exit'] = 'Close notes';
$string['notes_gone'] = 'This session is no longer running. Open the notes of the current one from the Sessions tab.';
$string['notes_heading'] = 'Speaker notes';
$string['notes_none'] = 'This slide has no notes.';
$string['notes_reconnect'] = 'Back to sessions';
$string['notes_stale'] = 'The slides changed while the presentation was running, so these notes may not match what is on screen. Start the presentation again, then open its notes from the Sessions tab - a restarted presentation is a new session, so this page cannot simply be reloaded.';
$string['notes_waiting'] = 'Waiting for the presentation to start on the other device.';
$string['overview'] = 'Slides overview';
$string['paginate'] = 'Show slide numbers';
$string['paginate_help'] = 'Adds a slide number to each slide, unless the slide itself says otherwise.';
$string['part_add'] = 'Add part';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'The slides of this part, written in Markdown. The Help tab beside the preview says how.';
$string['part_delete'] = 'Delete part';
$string['part_delete_confirm'] = 'Delete the part "{$a}" with all its slides and media?';
$string['part_deleted'] = 'The part was deleted.';
$string['part_edit'] = 'Edit slides';
$string['part_empty'] = 'empty';
$string['part_imported'] = 'The slides were imported as a new part.';
$string['part_media'] = 'Media';
$string['part_media_missing'] = 'These files are used in the slides but have not been uploaded yet: {$a}';
$string['part_move'] = 'Move';
$string['part_moved'] = 'Moved to position {$a}.';
$string['part_moveto'] = 'Move to position';
$string['part_name'] = 'Deck part';
$string['part_new'] = 'Part {$a}';
$string['part_preview'] = 'Preview';
$string['part_save_close'] = 'Save and close';
$string['part_save_continue'] = 'Save and continue';
$string['part_saved'] = 'Slides saved.';
$string['part_starter'] = '# Your title

Say what this is about

---

## A slide with points

- the **first** point
- and an *aside*

---

## Last slide

Thank you!

<!-- A comment is a speaker note: never on the slide, always on the printout. -->';
$string['pluginadministration'] = 'Presentation administration';
$string['pluginname'] = 'Markdown slide deck';
$string['present'] = 'Start presentation';
$string['presentation'] = 'Presentation';
$string['print'] = 'Print with notes';
$string['print_date'] = 'Printed {$a}';
$string['print_help'] = 'Every slide on its own page with its speaker notes underneath - the presenter copy.';
$string['print_slides'] = 'Print slides';
$string['print_slides_help'] = 'Every slide on its own page, without the speaker notes. Worried the plugin might not work on the day, or presenting offline? Save it as a PDF and you have a backup of the whole deck.';
$string['privacy:completed'] = 'Reaching the end of a presentation';
$string['privacy:metadata:mudeck_completed'] = 'A record that a user reached the end of a presentation.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'When the end was reached.';
$string['privacy:metadata:mudeck_completed:userid'] = 'Who reached the end.';
$string['privacy:metadata:mudeck_part'] = 'The slides of a presentation, which remember who saved them last.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'When the slides were last saved.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'Who saved the slides last.';
$string['privacy:metadata:mudeck_session'] = 'Where a running presentation is, so the same user can follow it on another device. Nobody else ever sees it.';
$string['privacy:metadata:mudeck_session:partid'] = 'Which part was on screen.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Notes about the session, such as a hint about the device used.';
$string['privacy:metadata:mudeck_session:slide'] = 'Which slide was on screen.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'The heading of that slide, so another device can show where the presentation is.';
$string['privacy:metadata:mudeck_session:timeended'] = 'When the presentation was left.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'When the device was last heard from.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'When the presentation was started.';
$string['privacy:metadata:mudeck_session:userid'] = 'Whose devices these are.';
$string['privacy:metadata:mudeck_theme'] = 'A slide theme added to the site, which remembers who saved it last.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'When the theme was last saved.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'Who saved the theme last.';
$string['privacy:sessions'] = 'Device sync';
$string['session_ago'] = '{$a} ago';
$string['session_delete'] = 'Delete';
$string['session_deleted'] = 'The session was deleted.';
$string['session_ended'] = 'Ended';
$string['session_lastseen'] = 'Last seen';
$string['session_lastslide'] = 'Last slide';
$string['session_notes'] = 'Show synced notes';
$string['session_started'] = 'Started';
$string['session_unknownslide'] = 'Not started yet';
$string['session_where'] = 'Current slide';
$string['sessions'] = 'Sessions';
$string['sessions_delete_finished'] = 'Delete finished sessions';
$string['sessions_finished'] = 'Finished';
$string['sessions_finished_deleted'] = '{$a} finished sessions were deleted.';
$string['sessions_finished_help'] = 'Nothing can follow these: they reached the end, went quiet, or their slides were edited since.';
$string['sessions_intro'] = 'Presentations you are running right now. Only your own devices are ever listed here.';
$string['sessions_none'] = 'Nothing is running. Start the presentation and it appears here, ready for another device to follow.';
$string['sessions_running'] = 'Running now';
$string['slide_exit'] = 'Exit presentation';
$string['slide_fullscreen'] = 'Full screen';
$string['slide_next'] = 'Next slide';
$string['slide_notes'] = 'Speaker notes';
$string['slide_of'] = 'Slide {$a->current} of {$a->total}';
$string['slide_overview'] = 'All slides';
$string['slide_previous'] = 'Previous slide';
$string['slides'] = 'Slides';
$string['tab_overview'] = 'Overview';
$string['theme'] = 'Theme';
$string['theme_add'] = 'Add a theme';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'The Marp theme, written as CSS. Start with `@import \'default\';` to build on a theme that already exists, then change what you need.

This CSS is shown to everybody who sees a presentation using the theme, and it is not checked. Only add CSS you trust.';
$string['theme_css_starter'] = '/* Start from a theme that exists, then change what you need. */
@import \'default\';

section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Default';
$string['theme_default_site'] = 'Default theme';
$string['theme_default_site_desc'] = 'The theme a new presentation starts with. An activity can pick a different one, and a deck can name its own in the front matter with `theme:`.';
$string['theme_delete_confirm'] = 'Delete the theme "{$a->name}"? It is used by {$a->used} presentations, which will fall back to the default look.';
$string['theme_deleted'] = 'The theme was deleted.';
$string['theme_edit'] = 'Edit the theme';
$string['theme_follow_site'] = 'Site default ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'The look used for slides that do not choose their own theme in the Markdown.';
$string['theme_manage'] = 'Slide themes';
$string['theme_manage_intro'] = 'Themes added here can be picked in any presentation on this site, beside the themes that come with the plugin.';
$string['theme_name'] = 'Name';
$string['theme_name_help'] = 'What the theme is called in the list of themes when someone sets up a presentation, for example "Orange".';
$string['theme_none'] = 'This site has no themes of its own yet.';
$string['theme_orange'] = 'Orange';
$string['theme_saved'] = 'The theme was saved.';
$string['theme_shortname'] = 'Short name';
$string['theme_shortname_help'] = 'The name the slides use, for example `orange`. Write it in the Markdown as `theme: orange` to pick this theme for one deck.

Letters, digits, dash and underscore only. It cannot be changed as easily later: presentations remember the short name, not the name.';
$string['theme_shortname_invalid'] = 'Use letters, digits, dash and underscore only.';
$string['theme_shortname_reserved'] = 'A theme that comes with the plugin already uses this short name.';
$string['theme_shortname_taken'] = 'Another theme already uses this short name.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Used by';
