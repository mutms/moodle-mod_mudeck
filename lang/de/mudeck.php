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
 * NOTE: This German translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * HINWEIS: Diese deutsche Übersetzung wurde automatisch von einem KI-Sprachmodell
 * erstellt und noch nicht von einem Menschen geprüft. Korrekturen sind willkommen.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowdevicesync'] = 'Gerätesynchronisierung erlauben';
$string['allowdevicesync_help'] = 'Wenn aktiviert, merkt sich eine laufende Präsentation, wo sie gerade ist, sodass dieselbe Person sie auf einem weiteren Gerät verfolgen kann - etwa auf einem Handy oder Tablet neben dem Präsentationsrechner. Ohne gestartete Präsentation wird nichts gespeichert, jede Person sieht nur ihre eigenen Sitzungen, und beim Abschalten werden alle gelöscht.';
$string['back'] = 'Zurück';
$string['completiondetail:reachedend'] = 'Die letzte Folie erreichen';
$string['completionreachedend'] = 'Teilnehmer/in muss die letzte Folie erreichen';
$string['editor_help'] = 'Hilfe';
$string['editor_preview'] = 'Vorschau';
$string['editor_slide'] = 'Folie {$a}';
$string['event_part_created'] = 'Teil erstellt';
$string['event_part_deleted'] = 'Teil gelöscht';
$string['event_part_exported'] = 'Teil exportiert';
$string['event_part_imported'] = 'Teil importiert';
$string['event_part_updated'] = 'Teil aktualisiert';
$string['event_presentation_started'] = 'Präsentation gestartet';
$string['export'] = 'Exportieren';
$string['help_markdown'] = 'Folien werden in Markdown geschrieben. Eine Zeile, die nur aus drei Bindestrichen besteht, beginnt eine neue Folie; darüber und darunter muss eine Leerzeile stehen.

    ---
    theme: orange
    paginate: true
    ---

    # Eine Titelfolie

    Worum es in dem Vortrag geht

    ---

    ## Eine Folie mit Stichpunkten

    - **fett** und *kursiv* und `Code`
    - [ein Link](https://example.org)
    - ![ein Bild](foto.jpg)
    - Mathematik: $x^2$ im Satz, oder $$ ... $$ auf eigenen Zeilen

    <!-- Ein Kommentar, der keine Direktive ist, ist eine Sprechernotiz. -->

    ---

    ## Code und Diagramme

    ```php
    echo "nach Sprache gefärbt";
    ```

    ```mermaid
    graph LR
      A[Idee] --> B[Folien]
    ```

    ---

    <!-- _class: lead -->

    ![bg](hintergrund.jpg)

    # Eine Folie mit Hintergrund

Ein `mermaid`-Block zeichnet Fluss-, Sequenz-, Zustands- und Klassendiagramme, Entitätsbeziehungen und einfache Charts; andere Diagrammarten erscheinen als Quelltext.

Der Vorspann (Front Matter) ganz oben gilt für die gesamte Präsentation; ein Kommentar mit Unterstrich, etwa `_class`, `_backgroundColor` oder `_color`, gilt nur für eine einzelne Folie.

Bilder werden über ihren Dateinamen angesprochen und unter Medien hochgeladen. Rohes HTML und `<style>`-Blöcke werden verworfen, eine Folie kann also kein eigenes Markup mitbringen.';
$string['help_media'] = 'Medien, auf die die Folien über den Dateinamen verweisen. Laden Sie hier eine Datei hoch und verwenden Sie dann ihren Namen im Markdown.

    ![ein Foto](foto.jpg)

    ![bg](hintergrund.jpg)

    ![bg left](hintergrund.jpg)

    ![bg fit](diagramm.png)

    ![w:400](logo.png)

Ein Bild mit `bg` füllt die Folie hinter dem Text; mit `left` oder `right` bleibt es auf einer Seite, mit `fit` wird es ganz gezeigt statt beschnitten. Größen werden mit `w:` und `h:` in Pixeln angegeben.

Dateien können in Ordnern liegen; dann gehört der Ordner zum Namen: `![](bilder/stadt.jpg)`. Animierte GIF- und WebP-Dateien funktionieren wie jedes andere Bild.';
$string['import'] = 'Importieren';
$string['import_file'] = 'Markdown oder ZIP';
$string['import_intro'] = 'Laden Sie die Folien als Markdown-Datei hoch oder als eine ZIP-Datei, die eine Markdown-Datei und die verwendeten Medien enthält. In beiden Fällen werden die Folien zu einem neuen Teil.';
$string['import_nothing'] = 'Es wurde nichts importiert. Laden Sie eine Markdown-Datei hoch oder eine ZIP-Datei mit genau einer Markdown-Datei und ihren Medien.';
$string['import_submit'] = 'Folien importieren';
$string['loading'] = 'Präsentation wird geladen…';
$string['media_intro'] = 'Laden Sie die Bilder und anderen Dateien hoch, die die Folien verwenden. Verweisen Sie im Markdown über den Dateinamen darauf, genau wie er hier aufgeführt ist - der Tab Hilfe zeigt, wie.';
$string['modulename'] = 'Markdown-Foliensatz';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Markdown-Foliensätze';
$string['mudeck:addinstance'] = 'Neue Präsentation hinzufügen';
$string['mudeck:edit'] = 'Folien bearbeiten';
$string['mudeck:fullaccess'] = 'Jederzeit auf alle Folien und Sprechernotizen zugreifen';
$string['mudeck:managethemes'] = 'Eigene Folienthemes der Website hinzufügen und bearbeiten';
$string['mudeck:present'] = 'Folien präsentieren oder ansehen';
$string['mudeck:syncdevices'] = 'Eigene Präsentation auf einem anderen Gerät verfolgen';
$string['mudeck:view'] = 'Präsentationsdetails ansehen';
$string['nopartcontent'] = 'Diese Präsentation hat noch keine Folien.';
$string['notes_current'] = 'Auf dem Bildschirm';
$string['notes_elapsed'] = 'Zeit seit dem Start';
$string['notes_exit'] = 'Notizen schließen';
$string['notes_gone'] = 'Diese Sitzung läuft nicht mehr. Öffnen Sie die Notizen der aktuellen Sitzung über den Tab Sitzungen.';
$string['notes_heading'] = 'Sprechernotizen';
$string['notes_none'] = 'Diese Folie hat keine Notizen.';
$string['notes_reconnect'] = 'Zurück zu den Sitzungen';
$string['notes_stale'] = 'Die Folien wurden geändert, während die Präsentation lief, daher passen diese Notizen möglicherweise nicht mehr zu dem, was auf dem Bildschirm ist. Starten Sie die Präsentation erneut und öffnen Sie dann ihre Notizen über den Tab Sitzungen - eine neu gestartete Präsentation ist eine neue Sitzung, diese Seite kann also nicht einfach neu geladen werden.';
$string['notes_waiting'] = 'Warten auf den Start der Präsentation auf dem anderen Gerät.';
$string['overview'] = 'Folienübersicht';
$string['paginate'] = 'Foliennummern anzeigen';
$string['paginate_help'] = 'Fügt jeder Folie eine Foliennummer hinzu, sofern die Folie selbst nichts anderes festlegt.';
$string['part_add'] = 'Teil hinzufügen';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'Die Folien dieses Teils, geschrieben in Markdown. Der Tab Hilfe neben der Vorschau erklärt, wie.';
$string['part_delete'] = 'Teil löschen';
$string['part_delete_confirm'] = 'Den Teil "{$a}" mit allen seinen Folien und Medien löschen?';
$string['part_deleted'] = 'Der Teil wurde gelöscht.';
$string['part_edit'] = 'Folien bearbeiten';
$string['part_empty'] = 'leer';
$string['part_imported'] = 'Die Folien wurden als neuer Teil importiert.';
$string['part_media'] = 'Medien';
$string['part_media_missing'] = 'Diese Dateien werden in den Folien verwendet, wurden aber noch nicht hochgeladen: {$a}';
$string['part_move'] = 'Verschieben';
$string['part_moved'] = 'An Position {$a} verschoben.';
$string['part_moveto'] = 'Verschieben an Position';
$string['part_name'] = 'Teil der Präsentation';
$string['part_new'] = 'Teil {$a}';
$string['part_preview'] = 'Vorschau';
$string['part_save_close'] = 'Speichern und schließen';
$string['part_save_continue'] = 'Speichern und weiter';
$string['part_saved'] = 'Folien gespeichert.';
$string['part_starter'] = '# Ihr Titel

Sagen Sie, worum es geht

---

## Eine Folie mit Stichpunkten

- der **erste** Punkt
- und eine *Nebenbemerkung*

---

## Letzte Folie

Vielen Dank!

<!-- Ein Kommentar ist eine Sprechernotiz: nie auf der Folie, immer auf dem Ausdruck. -->';
$string['pluginadministration'] = 'Präsentationsverwaltung';
$string['pluginname'] = 'Markdown-Foliensatz';
$string['present'] = 'Präsentation starten';
$string['presentation'] = 'Präsentation';
$string['print'] = 'Mit Notizen drucken';
$string['print_date'] = 'Gedruckt am {$a}';
$string['print_help'] = 'Jede Folie auf einer eigenen Seite mit ihren Sprechernotizen darunter - die Ausgabe für Vortragende.';
$string['print_slides'] = 'Folien drucken';
$string['print_slides_help'] = 'Jede Folie auf einer eigenen Seite, ohne Sprechernotizen. Als PDF gespeichert ist es eine Kopie der ganzen Präsentation, die sich überall öffnen lässt.';
$string['privacy:completed'] = 'Erreichen des Endes einer Präsentation';
$string['privacy:metadata:mudeck_completed'] = 'Ein Eintrag, dass eine Person das Ende einer Präsentation erreicht hat.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'Wann das Ende erreicht wurde.';
$string['privacy:metadata:mudeck_completed:userid'] = 'Wer das Ende erreicht hat.';
$string['privacy:metadata:mudeck_part'] = 'Die Folien einer Präsentation, die sich merken, wer sie zuletzt gespeichert hat.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'Wann die Folien zuletzt gespeichert wurden.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'Wer die Folien zuletzt gespeichert hat.';
$string['privacy:metadata:mudeck_session'] = 'Wo sich eine laufende Präsentation befindet, damit dieselbe Person sie auf einem anderen Gerät verfolgen kann. Niemand sonst sieht das jemals.';
$string['privacy:metadata:mudeck_session:partid'] = 'Welcher Teil auf dem Bildschirm war.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Angaben zur Sitzung, etwa ein Hinweis auf das verwendete Gerät.';
$string['privacy:metadata:mudeck_session:slide'] = 'Welche Folie auf dem Bildschirm war.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'Die Überschrift dieser Folie, damit ein anderes Gerät anzeigen kann, wo die Präsentation gerade ist.';
$string['privacy:metadata:mudeck_session:timeended'] = 'Wann die Präsentation verlassen wurde.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'Wann sich das Gerät zuletzt gemeldet hat.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'Wann die Präsentation gestartet wurde.';
$string['privacy:metadata:mudeck_session:userid'] = 'Wem diese Geräte gehören.';
$string['privacy:metadata:mudeck_theme'] = 'Ein zur Website hinzugefügtes Folientheme, das sich merkt, wer es zuletzt gespeichert hat.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'Wann das Theme zuletzt gespeichert wurde.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'Wer das Theme zuletzt gespeichert hat.';
$string['privacy:sessions'] = 'Gerätesynchronisierung';
$string['session_ago'] = 'vor {$a}';
$string['session_delete'] = 'Löschen';
$string['session_deleted'] = 'Die Sitzung wurde gelöscht.';
$string['session_ended'] = 'Beendet';
$string['session_lastseen'] = 'Zuletzt gesehen';
$string['session_lastslide'] = 'Letzte Folie';
$string['session_notes'] = 'Synchronisierte Notizen anzeigen';
$string['session_started'] = 'Gestartet';
$string['session_unknownslide'] = 'Noch nicht gestartet';
$string['session_where'] = 'Aktuelle Folie';
$string['sessions'] = 'Sitzungen';
$string['sessions_delete_finished'] = 'Beendete Sitzungen löschen';
$string['sessions_finished'] = 'Beendet';
$string['sessions_finished_deleted'] = '{$a} beendete Sitzungen wurden gelöscht.';
$string['sessions_finished_help'] = 'Diesen kann nichts mehr folgen: Sie haben das Ende erreicht, sind verstummt oder ihre Folien wurden seitdem bearbeitet.';
$string['sessions_intro'] = 'Präsentationen, die Sie gerade halten. Hier werden nur Ihre eigenen Geräte aufgeführt.';
$string['sessions_none'] = 'Es läuft nichts. Starten Sie die Präsentation, dann erscheint sie hier, bereit, von einem anderen Gerät verfolgt zu werden.';
$string['sessions_running'] = 'Läuft gerade';
$string['slide_exit'] = 'Präsentation beenden';
$string['slide_fullscreen'] = 'Vollbild';
$string['slide_next'] = 'Nächste Folie';
$string['slide_notes'] = 'Sprechernotizen';
$string['slide_of'] = 'Folie {$a->current} von {$a->total}';
$string['slide_overview'] = 'Alle Folien';
$string['slide_previous'] = 'Vorherige Folie';
$string['slides'] = 'Folien';
$string['tab_overview'] = 'Übersicht';
$string['theme'] = 'Theme';
$string['theme_add'] = 'Theme hinzufügen';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'Das Marp-Theme, geschrieben als CSS. Beginnen Sie mit `@import \'default\';`, um auf einem bestehenden Theme aufzubauen, und ändern Sie dann, was Sie brauchen.

Dieses CSS wird allen angezeigt, die eine Präsentation mit diesem Theme sehen, und es wird nicht geprüft. Fügen Sie nur CSS hinzu, dem Sie vertrauen.';
$string['theme_css_starter'] = '/* Beginnen Sie mit einem bestehenden Theme und ändern Sie dann, was Sie brauchen. */
@import \'default\';

section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Standard';
$string['theme_default_site'] = 'Standard-Theme';
$string['theme_default_site_desc'] = 'Das Theme, mit dem eine neue Präsentation beginnt. Eine Aktivität kann ein anderes wählen, und eine Präsentation kann im Vorspann mit `theme:` ihr eigenes benennen.';
$string['theme_delete_confirm'] = 'Das Theme "{$a->name}" löschen? Es wird von {$a->used} Präsentationen verwendet, die dann auf das Standardaussehen zurückfallen.';
$string['theme_deleted'] = 'Das Theme wurde gelöscht.';
$string['theme_edit'] = 'Theme bearbeiten';
$string['theme_follow_site'] = 'Website-Standard ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'Das Aussehen für Folien, die im Markdown kein eigenes Theme wählen.';
$string['theme_manage'] = 'Folienthemes';
$string['theme_manage_intro'] = 'Hier hinzugefügte Themes können in jeder Präsentation dieser Website gewählt werden, neben den Themes, die mit dem Plugin mitgeliefert werden.';
$string['theme_name'] = 'Name';
$string['theme_name_help'] = 'So heißt das Theme in der Liste der Themes, wenn jemand eine Präsentation einrichtet, zum Beispiel "Orange".';
$string['theme_none'] = 'Diese Website hat noch keine eigenen Themes.';
$string['theme_orange'] = 'Orange';
$string['theme_saved'] = 'Das Theme wurde gespeichert.';
$string['theme_shortname'] = 'Kurzname';
$string['theme_shortname_help'] = 'Der Name, den die Folien verwenden, zum Beispiel `orange`. Schreiben Sie im Markdown `theme: orange`, um dieses Theme für eine Präsentation zu wählen.

Nur Buchstaben, Ziffern, Bindestrich und Unterstrich. Er lässt sich später nicht so leicht ändern: Präsentationen merken sich den Kurznamen, nicht den Namen.';
$string['theme_shortname_invalid'] = 'Verwenden Sie nur Buchstaben, Ziffern, Bindestrich und Unterstrich.';
$string['theme_shortname_reserved'] = 'Ein mit dem Plugin mitgeliefertes Theme verwendet diesen Kurznamen bereits.';
$string['theme_shortname_taken'] = 'Ein anderes Theme verwendet diesen Kurznamen bereits.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Verwendet von';
