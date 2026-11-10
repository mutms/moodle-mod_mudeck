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
 * Strings for component 'mod_mudeck', language 'it'.
 *
 * NOTE: This Italian translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowdevicesync'] = 'Consenti la sincronizzazione tra dispositivi';
$string['allowdevicesync_help'] = 'Se l\'impostazione è abilitata, una presentazione in corso ricorda il punto in cui si trova, in modo che lo stesso utente possa seguirla su un altro dispositivo - un telefono o un tablet accanto al computer da cui si presenta. Non viene ricordato nulla finché qualcuno non avvia la presentazione, ciascuno vede solo le proprie sessioni e la disattivazione dell\'impostazione le elimina tutte.';
$string['back'] = 'Indietro';
$string['completiondetail:reachedend'] = 'Raggiungere l\'ultima diapositiva';
$string['completionreachedend'] = 'Lo studente deve raggiungere l\'ultima diapositiva';
$string['editor_help'] = 'Aiuto';
$string['editor_preview'] = 'Anteprima';
$string['editor_slide'] = 'Diapositiva {$a}';
$string['event_part_created'] = 'Parte creata';
$string['event_part_deleted'] = 'Parte eliminata';
$string['event_part_exported'] = 'Parte esportata';
$string['event_part_imported'] = 'Parte importata';
$string['event_part_updated'] = 'Parte aggiornata';
$string['event_presentation_started'] = 'Presentazione avviata';
$string['export'] = 'Esporta';
$string['help_markdown'] = 'Le diapositive sono scritte in Markdown. Una riga che contiene solo tre trattini inizia una nuova diapositiva, con una riga vuota sopra e una sotto.

    ---
    theme: orange
    paginate: true
    ---

    # Una diapositiva con il titolo

    L\'argomento della presentazione

    ---

    ## Una diapositiva con un elenco

    - **grassetto**, *corsivo* e `codice`
    - [un link](https://example.org)
    - ![un\'immagine](photo.jpg)
    - formule: $x^2$ in una frase, oppure $$ ... $$ su righe separate

    <!-- Un commento che non è una direttiva è una nota del relatore. -->

    ---

    ## Codice e diagrammi

    ```php
    echo "colorato in base al linguaggio";
    ```

    ```mermaid
    graph LR
      A[Idea] --> B[Diapositive]
    ```

    ---

    <!-- _class: lead -->

    ![bg](background.jpg)

    # Una diapositiva con lo sfondo

Un blocco `mermaid` disegna diagrammi di flusso, di sequenza, di stato e delle classi, relazioni tra entità e grafici semplici; gli altri tipi di diagramma vengono mostrati come sorgente.

`transition: slide` oppure `transition: fade` nel front matter anima ogni cambio di diapositiva; `_transition` in una diapositiva cambia solo quella e `none` disattiva l\'animazione.

Il front matter all\'inizio imposta l\'intera presentazione; un commento con il trattino basso, come `_class`, `_backgroundColor` o `_color`, imposta una sola diapositiva.

Le immagini sono indicate con il nome del file e vengono caricate in Media. L\'HTML grezzo e i blocchi `<style>` vengono scartati, quindi una diapositiva non può contenere markup proprio.';
$string['help_media'] = 'I file multimediali a cui le diapositive fanno riferimento con il nome del file. Caricare un file qui, quindi utilizzarne il nome nel Markdown.

    ![una fotografia](photo.jpg)

    ![bg](background.jpg)

    ![bg left](background.jpg)

    ![bg fit](diagram.png)

    ![w:400](logo.png)

Un\'immagine con `bg` riempie la diapositiva dietro il testo; aggiungere `left` o `right` per tenerla su un lato e `fit` per mostrarla per intero invece di ritagliarla. Le dimensioni si impostano con `w:` e `h:` in pixel.

I file possono trovarsi in cartelle e in tal caso il nome comprende la cartella: `![](pictures/city.jpg)`. GIF e WebP animati funzionano come qualsiasi altra immagine.';
$string['import'] = 'Importa';
$string['import_file'] = 'Markdown o zip';
$string['import_intro'] = 'Caricare le diapositive come file Markdown oppure come un unico zip contenente un file Markdown e i file multimediali che utilizza. In entrambi i casi le diapositive diventano una nuova parte.';
$string['import_nothing'] = 'Non è stato importato nulla. Caricare un file Markdown oppure uno zip contenente esattamente un file Markdown e i relativi file multimediali.';
$string['import_submit'] = 'Importa diapositive';
$string['loading'] = 'Caricamento della presentazione in corso…';
$string['media_intro'] = 'Caricare le immagini e gli altri file utilizzati dalle diapositive. Nel Markdown vanno indicati con il nome del file, esattamente come sono elencati qui - la scheda Aiuto mostra come fare.';
$string['modulename'] = 'Presentazione Markdown';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Presentazioni Markdown';
$string['mudeck:addinstance'] = 'Aggiungere presentazioni';
$string['mudeck:edit'] = 'Modificare le diapositive';
$string['mudeck:fullaccess'] = 'Accedere in qualsiasi momento a tutte le diapositive e alle note del relatore';
$string['mudeck:managethemes'] = 'Aggiungere e modificare i temi per diapositive propri del sito';
$string['mudeck:present'] = 'Presentare o guardare le diapositive';
$string['mudeck:syncdevices'] = 'Seguire la propria presentazione su un altro dispositivo';
$string['mudeck:view'] = 'Visualizzare i dettagli della presentazione';
$string['nopartcontent'] = 'Questa presentazione non ha ancora diapositive.';
$string['notes_current'] = 'Sullo schermo';
$string['notes_elapsed'] = 'Tempo dall\'inizio';
$string['notes_exit'] = 'Chiudi note';
$string['notes_gone'] = 'Questa sessione non è più in corso. Apri le note di quella attuale dalla scheda Sessioni.';
$string['notes_heading'] = 'Note del relatore';
$string['notes_none'] = 'Questa diapositiva non ha note.';
$string['notes_reconnect'] = 'Torna alle sessioni';
$string['notes_stale'] = 'Le diapositive sono state modificate mentre la presentazione era in corso, quindi queste note potrebbero non corrispondere a ciò che è sullo schermo. Avvia di nuovo la presentazione, quindi apri le sue note dalla scheda Sessioni - una presentazione riavviata è una nuova sessione, perciò non basta ricaricare questa pagina.';
$string['notes_waiting'] = 'In attesa dell\'avvio della presentazione sull\'altro dispositivo.';
$string['overview'] = 'Panoramica delle diapositive';
$string['paginate'] = 'Mostra i numeri delle diapositive';
$string['paginate_help'] = 'Aggiunge il numero a ogni diapositiva, a meno che la diapositiva stessa non indichi diversamente.';
$string['part_add'] = 'Aggiungi parte';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'Le diapositive di questa parte, scritte in Markdown. La scheda Aiuto accanto all\'anteprima spiega come fare.';
$string['part_delete'] = 'Elimina parte';
$string['part_delete_confirm'] = 'Eliminare la parte "{$a}" con tutte le sue diapositive e i suoi file multimediali?';
$string['part_deleted'] = 'La parte è stata eliminata.';
$string['part_edit'] = 'Modifica diapositive';
$string['part_empty'] = 'vuota';
$string['part_imported'] = 'Le diapositive sono state importate come nuova parte.';
$string['part_media'] = 'Media';
$string['part_media_missing'] = 'Questi file sono utilizzati nelle diapositive ma non sono ancora stati caricati: {$a}';
$string['part_move'] = 'Sposta';
$string['part_moved'] = 'Spostata nella posizione {$a}.';
$string['part_moveto'] = 'Sposta nella posizione';
$string['part_name'] = 'Nome della parte';
$string['part_new'] = 'Parte {$a}';
$string['part_preview'] = 'Anteprima';
$string['part_save_close'] = 'Salva e chiudi';
$string['part_save_continue'] = 'Salva e continua';
$string['part_saved'] = 'Diapositive salvate.';
$string['part_starter'] = '# Il tuo titolo

Scrivi qui l\'argomento

---

## Una diapositiva con un elenco

- il **primo** punto
- e un *inciso*

---

## Ultima diapositiva

Grazie!

<!-- Un commento è una nota del relatore: mai sulla diapositiva, sempre nella stampa. -->';
$string['pluginadministration'] = 'Gestione presentazione';
$string['pluginname'] = 'Presentazione Markdown';
$string['present'] = 'Avvia presentazione';
$string['presentation'] = 'Presentazione';
$string['print'] = 'Stampa con note';
$string['print_date'] = 'Data di stampa: {$a}';
$string['print_help'] = 'Ogni diapositiva su una pagina a sé, con sotto le note del relatore - la copia per chi presenta.';
$string['print_slides'] = 'Stampa diapositive';
$string['print_slides_help'] = 'Ogni diapositiva su una pagina a sé, senza le note del relatore. Salvata come PDF, è una copia dell\'intera presentazione che si apre ovunque.';
$string['privacy:completed'] = 'Raggiungimento della fine di una presentazione';
$string['privacy:metadata:mudeck_completed'] = 'La registrazione del fatto che un utente ha raggiunto la fine di una presentazione.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'Quando è stata raggiunta la fine.';
$string['privacy:metadata:mudeck_completed:userid'] = 'Chi ha raggiunto la fine.';
$string['privacy:metadata:mudeck_part'] = 'Le diapositive di una presentazione, che ricordano chi le ha salvate per ultimo.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'Quando le diapositive sono state salvate l\'ultima volta.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'Chi ha salvato le diapositive per ultimo.';
$string['privacy:metadata:mudeck_session'] = 'Il punto in cui si trova una presentazione in corso, in modo che lo stesso utente possa seguirla su un altro dispositivo. Nessun altro lo vede mai.';
$string['privacy:metadata:mudeck_session:partid'] = 'Quale parte era sullo schermo.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Annotazioni sulla sessione, ad esempio un\'indicazione sul dispositivo utilizzato.';
$string['privacy:metadata:mudeck_session:slide'] = 'Quale diapositiva era sullo schermo.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'Il titolo di quella diapositiva, in modo che un altro dispositivo possa mostrare a che punto è la presentazione.';
$string['privacy:metadata:mudeck_session:timeended'] = 'Quando la presentazione è stata abbandonata.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'Quando il dispositivo si è fatto vivo l\'ultima volta.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'Quando la presentazione è stata avviata.';
$string['privacy:metadata:mudeck_session:userid'] = 'A chi appartengono questi dispositivi.';
$string['privacy:metadata:mudeck_theme'] = 'Un tema per diapositive aggiunto al sito, che ricorda chi lo ha salvato per ultimo.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'Quando il tema è stato salvato l\'ultima volta.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'Chi ha salvato il tema per ultimo.';
$string['privacy:sessions'] = 'Sincronizzazione tra dispositivi';
$string['session_ago'] = '{$a} fa';
$string['session_delete'] = 'Elimina';
$string['session_deleted'] = 'La sessione è stata eliminata.';
$string['session_ended'] = 'Terminata';
$string['session_lastseen'] = 'Ultimo contatto';
$string['session_lastslide'] = 'Ultima diapositiva';
$string['session_notes'] = 'Mostra note sincronizzate';
$string['session_started'] = 'Avviata';
$string['session_unknownslide'] = 'Non ancora avviata';
$string['session_where'] = 'Diapositiva attuale';
$string['sessions'] = 'Sessioni';
$string['sessions_delete_finished'] = 'Elimina sessioni terminate';
$string['sessions_finished'] = 'Terminate';
$string['sessions_finished_deleted'] = 'Sessioni terminate eliminate: {$a}.';
$string['sessions_finished_help'] = 'Queste sessioni non possono più essere seguite: sono arrivate alla fine, non danno più segnali oppure le loro diapositive sono state modificate nel frattempo.';
$string['sessions_intro'] = 'Le presentazioni che hai in corso in questo momento. Qui sono elencati sempre e solo i tuoi dispositivi.';
$string['sessions_none'] = 'Non c\'è nulla in corso. Avvia la presentazione e comparirà qui, pronta per essere seguita da un altro dispositivo.';
$string['sessions_running'] = 'In corso';
$string['slide_exit'] = 'Esci dalla presentazione';
$string['slide_fullscreen'] = 'Schermo intero';
$string['slide_next'] = 'Diapositiva successiva';
$string['slide_notes'] = 'Note del relatore';
$string['slide_of'] = 'Diapositiva {$a->current} di {$a->total}';
$string['slide_overview'] = 'Tutte le diapositive';
$string['slide_previous'] = 'Diapositiva precedente';
$string['slides'] = 'Diapositive';
$string['tab_overview'] = 'Panoramica';
$string['theme'] = 'Tema';
$string['theme_add'] = 'Aggiungi un tema';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'Il tema Marp, scritto in CSS. Iniziare con `@import \'default\';` per partire da un tema già esistente, quindi modificare ciò che serve.

Questo CSS viene mostrato a chiunque veda una presentazione che utilizza il tema e non viene controllato. Aggiungere solo CSS attendibile.';
$string['theme_css_starter'] = '/* Parti da un tema esistente, quindi modifica ciò che serve. */
@import \'default\';

section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Default';
$string['theme_default_site'] = 'Tema di default';
$string['theme_default_site_desc'] = 'Il tema con cui inizia una nuova presentazione. Un\'attività può sceglierne uno diverso e una presentazione può indicare il proprio nel front matter con `theme:`.';
$string['theme_delete_confirm'] = 'Eliminare il tema "{$a->name}"? Numero di presentazioni che lo utilizzano e che torneranno all\'aspetto di default: {$a->used}.';
$string['theme_deleted'] = 'Il tema è stato eliminato.';
$string['theme_edit'] = 'Modifica il tema';
$string['theme_follow_site'] = 'Default del sito ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'L\'aspetto utilizzato per le diapositive che non scelgono un proprio tema nel Markdown.';
$string['theme_manage'] = 'Temi per diapositive';
$string['theme_manage_intro'] = 'I temi aggiunti qui possono essere scelti in qualsiasi presentazione di questo sito, accanto ai temi forniti con il plugin.';
$string['theme_name'] = 'Nome';
$string['theme_name_help'] = 'Il nome del tema nell\'elenco dei temi quando si configura una presentazione, ad esempio "Arancione".';
$string['theme_none'] = 'Questo sito non ha ancora temi propri.';
$string['theme_orange'] = 'Arancione';
$string['theme_saved'] = 'Il tema è stato salvato.';
$string['theme_shortname'] = 'Nome abbreviato';
$string['theme_shortname_help'] = 'Il nome utilizzato dalle diapositive, ad esempio `orange`. Scriverlo nel Markdown come `theme: orange` per scegliere questo tema per una presentazione.

Solo lettere, cifre, trattino e trattino basso. In seguito non è altrettanto facile cambiarlo: le presentazioni ricordano il nome abbreviato, non il nome.';
$string['theme_shortname_invalid'] = 'Utilizzare solo lettere, cifre, trattino e trattino basso.';
$string['theme_shortname_reserved'] = 'Un tema fornito con il plugin utilizza già questo nome abbreviato.';
$string['theme_shortname_taken'] = 'Un altro tema utilizza già questo nome abbreviato.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Utilizzato da';
