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

$string['allowdevicesync'] = 'Povolit synchronizaci zařízení';
$string['allowdevicesync_help'] = 'Když je zapnuto, běžící prezentace si pamatuje, kde právě je, takže ji tentýž uživatel může sledovat na dalším zařízení - třeba na telefonu vedle promítacího počítače. Bez spuštěné prezentace se nepamatuje nic, každý vidí jen svá vlastní sezení a vypnutím se všechna smažou.';
$string['back'] = 'Zpět';
$string['completiondetail:reachedend'] = 'Dojít na poslední snímek';
$string['completionreachedend'] = 'Student musí dojít na poslední snímek';
$string['editor_help'] = 'Nápověda';
$string['editor_preview'] = 'Náhled';
$string['editor_slide'] = 'Snímek {$a}';
$string['event_part_created'] = 'Část vytvořena';
$string['event_part_deleted'] = 'Část smazána';
$string['event_part_exported'] = 'Část exportována';
$string['event_part_imported'] = 'Část importována';
$string['event_part_updated'] = 'Část upravena';
$string['event_presentation_started'] = 'Prezentace spuštěna';
$string['export'] = 'Export';
$string['help_markdown'] = 'Snímky se píší v Markdownu. Řádek, na kterém nejsou nic než tři pomlčky, začíná nový snímek; nad ním i pod ním musí být prázdný řádek.

    ---
    theme: orange
    paginate: true
    ---

    # Úvodní snímek

    O čem přednáška je

    ---

    ## Snímek s body

    - **tučně** a *kurzívou* a `kód`
    - [odkaz](https://example.org)
    - ![obrázek](foto.jpg)
    - matematika: $x^2$ ve větě, nebo $$ ... $$ na vlastních řádcích

    <!-- Komentář, který není direktiva, je poznámka přednášejícího. -->

    ---

    ## Kód a diagramy

    ```php
    echo "obarveno podle jazyka";
    ```

    ```mermaid
    graph LR
      A[Nápad] --> B[Snímky]
    ```

    ---

    <!-- _class: lead -->

    ![bg](pozadi.jpg)

    # Snímek s pozadím

Blok `mermaid` kreslí vývojové, sekvenční, stavové a třídní diagramy, vztahy entit a jednoduché grafy; jiné druhy diagramů se zobrazí jako zdrojový text.

`transition: slide` nebo `transition: fade` v úvodním bloku animuje každou změnu snímku; `_transition` na jednom snímku mění jen ten a `none` animaci vypne.

Blok na úplném začátku nastavuje celou prezentaci; komentář s podtržítkem, například `_class`, `_backgroundColor` nebo `_color`, nastavuje jen jeden snímek.

Obrázky se uvádějí jménem souboru a nahrávají se pod Média. Přímé HTML a bloky `<style>` se zahazují, snímek si tedy nemůže přinést vlastní kód.';
$string['help_media'] = 'Média, na která se snímky odkazují jménem souboru. Nahrajte soubor sem a v Markdownu použijte jeho jméno.

    ![fotografie](foto.jpg)

    ![bg](pozadi.jpg)

    ![bg left](pozadi.jpg)

    ![bg fit](schema.png)

    ![w:400](logo.png)

Obrázek s `bg` vyplní snímek za textem; `left` nebo `right` ho nechá jen na jedné straně a `fit` zobrazí celý místo oříznutí. Velikost se nastavuje pomocí `w:` a `h:` v pixelech.

Soubory mohou být ve složkách, pak jméno obsahuje i složku: `![](obrazky/mesto.jpg)`. Animovaný GIF a WebP fungují jako každý jiný obrázek.';
$string['import'] = 'Import';
$string['import_file'] = 'Markdown nebo zip';
$string['import_intro'] = 'Nahrajte snímky jako Markdown soubor, nebo jako jeden zip s Markdown souborem a médii, která používá. V obou případech vznikne nová část.';
$string['import_nothing'] = 'Nic se neimportovalo. Nahrajte Markdown soubor, nebo zip s právě jedním Markdown souborem a jeho médii.';
$string['import_submit'] = 'Importovat snímky';
$string['loading'] = 'Načítá se prezentace…';
$string['media_intro'] = 'Nahrajte obrázky a další soubory, které snímky používají. V Markdownu se na ně odkazujte jménem souboru přesně tak, jak jsou uvedené tady - jak na to, ukazuje karta Nápověda.';
$string['modulename'] = 'Prezentace v Markdownu';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Prezentace v Markdownu';
$string['mudeck:addinstance'] = 'Přidat novou prezentaci';
$string['mudeck:edit'] = 'Upravovat snímky';
$string['mudeck:fullaccess'] = 'Zobrazit kdykoliv celou prezentaci včetně poznámek';
$string['mudeck:managethemes'] = 'Přidávat a upravovat vlastní motivy prezentací';
$string['mudeck:present'] = 'Prezentovat nebo sledovat snímky';
$string['mudeck:syncdevices'] = 'Sledovat vlastní prezentaci na dalším zařízení';
$string['mudeck:view'] = 'Zobrazit údaje o prezentaci';
$string['nopartcontent'] = 'Tato prezentace zatím nemá žádné snímky.';
$string['notes_current'] = 'Na plátně';
$string['notes_elapsed'] = 'Čas od začátku';
$string['notes_exit'] = 'Zavřít poznámky';
$string['notes_gone'] = 'Toto sezení už neběží. Otevřete poznámky toho aktuálního na kartě Sezení.';
$string['notes_heading'] = 'Poznámky přednášejícího';
$string['notes_none'] = 'Tento snímek nemá poznámky.';
$string['notes_reconnect'] = 'Zpět na sezení';
$string['notes_stale'] = 'Snímky se během prezentace změnily, takže tyto poznámky nemusí odpovídat tomu, co je na plátně. Spusťte prezentaci znovu a otevřete její poznámky na kartě Sezení - restartovaná prezentace je nové sezení, tuto stránku tedy nestačí načíst znovu.';
$string['notes_waiting'] = 'Čeká se, až se prezentace spustí na druhém zařízení.';
$string['overview'] = 'Přehled snímků';
$string['paginate'] = 'Zobrazovat čísla snímků';
$string['paginate_help'] = 'Na každý snímek přidá jeho číslo, pokud si snímek neurčí jinak.';
$string['part_add'] = 'Přidat část';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'Snímky této části, psané v Markdownu. Jak na to, říká karta Nápověda vedle náhledu.';
$string['part_delete'] = 'Smazat část';
$string['part_delete_confirm'] = 'Smazat část "{$a}" včetně všech snímků a médií?';
$string['part_deleted'] = 'Část byla smazána.';
$string['part_edit'] = 'Upravit snímky';
$string['part_empty'] = 'prázdná';
$string['part_imported'] = 'Snímky byly importovány jako nová část.';
$string['part_media'] = 'Média';
$string['part_media_missing'] = 'Tyto soubory jsou ve slidech použité, ale zatím nebyly nahrány: {$a}';
$string['part_move'] = 'Přesunout';
$string['part_moved'] = 'Přesunuto na pozici {$a}.';
$string['part_moveto'] = 'Přesunout na pozici';
$string['part_name'] = 'Název části';
$string['part_new'] = 'Část {$a}';
$string['part_preview'] = 'Náhled';
$string['part_save_close'] = 'Uložit a zavřít';
$string['part_save_continue'] = 'Uložit a pokračovat';
$string['part_saved'] = 'Snímky byly uloženy.';
$string['part_starter'] = '# Váš nadpis

O čem to bude

---

## Snímek s body

- **první** bod
- a *poznámka na okraj*

---

## Poslední snímek

Děkuji!

<!-- Komentář je poznámka přednášejícího: nikdy není na snímku, vždy je na výtisku. -->';
$string['pluginadministration'] = 'Správa prezentace';
$string['pluginname'] = 'Prezentace v Markdownu';
$string['present'] = 'Spustit prezentaci';
$string['presentation'] = 'Prezentace';
$string['print'] = 'Tisk s poznámkami';
$string['print_date'] = 'Vytištěno {$a}';
$string['print_help'] = 'Každý snímek na vlastní stránce s poznámkami pod ním - kopie pro přednášejícího.';
$string['print_slides'] = 'Tisk snímků';
$string['print_slides_help'] = 'Každý snímek na vlastní stránce, bez poznámek. Uložené jako PDF je to kopie celé prezentace, která se otevře kdekoli.';
$string['privacy:completed'] = 'Dosažení konce prezentace';
$string['privacy:metadata:mudeck_completed'] = 'Záznam o tom, že uživatel došel na konec prezentace.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'Kdy byl konec dosažen.';
$string['privacy:metadata:mudeck_completed:userid'] = 'Kdo došel na konec.';
$string['privacy:metadata:mudeck_part'] = 'Snímky prezentace, které si pamatují, kdo je naposledy uložil.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'Kdy byly snímky naposledy uloženy.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'Kdo naposledy uložil snímky.';
$string['privacy:metadata:mudeck_session'] = 'Kde právě je běžící prezentace, aby ji tentýž uživatel mohl sledovat na dalším zařízení. Nikdo jiný to nevidí.';
$string['privacy:metadata:mudeck_session:partid'] = 'Která část byla na obrazovce.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Poznámky k sezení, například údaj o použitém zařízení.';
$string['privacy:metadata:mudeck_session:slide'] = 'Který snímek byl na obrazovce.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'Nadpis snímku, aby další zařízení ukázalo, kde prezentace je.';
$string['privacy:metadata:mudeck_session:timeended'] = 'Kdy byla prezentace opuštěna.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'Kdy se zařízení naposledy ozvalo.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'Kdy byla prezentace spuštěna.';
$string['privacy:metadata:mudeck_session:userid'] = 'Čí jsou to zařízení.';
$string['privacy:metadata:mudeck_theme'] = 'Motiv snímků přidaný na tento web, který si pamatuje, kdo ho naposledy uložil.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'Kdy byl motiv naposledy uložen.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'Kdo motiv naposledy uložil.';
$string['privacy:sessions'] = 'Synchronizace zařízení';
$string['session_ago'] = 'před {$a}';
$string['session_delete'] = 'Smazat';
$string['session_deleted'] = 'Sezení bylo smazáno.';
$string['session_ended'] = 'Ukončeno';
$string['session_lastseen'] = 'Naposledy viděno';
$string['session_lastslide'] = 'Poslední snímek';
$string['session_notes'] = 'Zobrazit synchronizované poznámky';
$string['session_started'] = 'Spuštěno';
$string['session_unknownslide'] = 'Zatím nespuštěno';
$string['session_where'] = 'Aktuální snímek';
$string['sessions'] = 'Sezení';
$string['sessions_delete_finished'] = 'Smazat ukončená sezení';
$string['sessions_finished'] = 'Ukončené';
$string['sessions_finished_deleted'] = 'Smazáno ukončených sezení: {$a}.';
$string['sessions_finished_help'] = 'Tato sezení už nelze sledovat: došla na konec, ztichla, nebo se jejich snímky mezitím změnily.';
$string['sessions_intro'] = 'Prezentace, které právě promítáte. Vidíte tu vždy jen svá vlastní zařízení.';
$string['sessions_none'] = 'Nic neběží. Spusťte prezentaci a objeví se tady připravená pro další zařízení.';
$string['sessions_running'] = 'Právě běží';
$string['slide_exit'] = 'Ukončit prezentaci';
$string['slide_fullscreen'] = 'Celá obrazovka';
$string['slide_next'] = 'Další snímek';
$string['slide_notes'] = 'Poznámky přednášejícího';
$string['slide_of'] = 'Snímek {$a->current} z {$a->total}';
$string['slide_overview'] = 'Všechny snímky';
$string['slide_previous'] = 'Předchozí snímek';
$string['slides'] = 'Snímky';
$string['tab_overview'] = 'Přehled';
$string['theme'] = 'Vzhled';
$string['theme_add'] = 'Přidat motiv';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'Motiv Marp zapsaný jako CSS. Začněte řádkem `@import \'default\';`, tím navážete na existující motiv, a pak změňte, co potřebujete.

Toto CSS se zobrazí každému, kdo uvidí prezentaci s tímto motivem, a nijak se nekontroluje. Vkládejte jen CSS, kterému věříte.';
$string['theme_css_starter'] = '/* Začněte od existujícího motivu a změňte, co potřebujete. */
@import \'default\';

section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Výchozí';
$string['theme_default_site'] = 'Výchozí motiv';
$string['theme_default_site_desc'] = 'Motiv, se kterým začíná nová prezentace. Aktivita si může vybrat jiný a prezentace může vlastní motiv uvést přímo v textu pomocí `theme:`.';
$string['theme_delete_confirm'] = 'Smazat motiv „{$a->name}“? Používá ho {$a->used} prezentací, které se vrátí k výchozímu vzhledu.';
$string['theme_deleted'] = 'Motiv byl smazán.';
$string['theme_edit'] = 'Upravit motiv';
$string['theme_follow_site'] = 'Výchozí motiv webu ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'Vzhled snímků, které si samy v Markdownu neurčí jiný.';
$string['theme_manage'] = 'Motivy prezentací';
$string['theme_manage_intro'] = 'Zde přidané motivy si může vybrat kterákoli prezentace na tomto webu, vedle motivů dodávaných s modulem.';
$string['theme_name'] = 'Název';
$string['theme_name_help'] = 'Jak se motiv jmenuje v nabídce při nastavení prezentace, například „Orange“.';
$string['theme_none'] = 'Tento web zatím nemá vlastní motivy.';
$string['theme_orange'] = 'Oranžový';
$string['theme_saved'] = 'Motiv byl uložen.';
$string['theme_shortname'] = 'Krátký název';
$string['theme_shortname_help'] = 'Název, který používají slidy, například `orange`. V Markdownu se zapisuje jako `theme: orange` a vybere tento motiv pro jednu prezentaci.

Jen písmena, číslice, pomlčka a podtržítko. Později se mění obtížně: prezentace si pamatují krátký název, ne název.';
$string['theme_shortname_invalid'] = 'Použijte jen písmena, číslice, pomlčku a podtržítko.';
$string['theme_shortname_reserved'] = 'Tento krátký název už používá motiv dodávaný s modulem.';
$string['theme_shortname_taken'] = 'Tento krátký název už používá jiný motiv.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Použit v';
