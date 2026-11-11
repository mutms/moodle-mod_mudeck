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
 * Strings for component 'mod_mudeck', language 'fr'.
 *
 * NOTE: This French translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowdevicesync'] = 'Autoriser la synchronisation des appareils';
$string['allowdevicesync_help'] = 'Si cette option est activée, une présentation en cours mémorise où elle en est, afin que le même utilisateur puisse la suivre sur un autre appareil - un téléphone ou une tablette à côté de la machine qui sert à présenter. Rien n’est mémorisé tant que personne ne démarre la présentation, chacun ne voit que ses propres sessions, et la désactivation de cette option les supprime toutes.';
$string['back'] = 'Retour';
$string['completiondetail:reachedend'] = 'Atteindre la dernière diapositive';
$string['completionreachedend'] = 'L’étudiant doit atteindre la dernière diapositive';
$string['editor_help'] = 'Aide';
$string['editor_preview'] = 'Aperçu';
$string['editor_slide'] = 'Diapositive {$a}';
$string['event_part_created'] = 'Section créée';
$string['event_part_deleted'] = 'Section supprimée';
$string['event_part_exported'] = 'Section exportée';
$string['event_part_imported'] = 'Section importée';
$string['event_part_updated'] = 'Section mise à jour';
$string['event_presentation_started'] = 'Présentation démarrée';
$string['export'] = 'Exporter';
$string['help_markdown'] = 'Les diapositives sont écrites en Markdown. Une ligne ne contenant que trois tirets commence une nouvelle diapositive, avec une ligne vide au-dessus et au-dessous.

    ---
    theme: orange
    paginate: true
    ---

    # Une diapositive de titre

    Le sujet de l’exposé

    ---

    ## Une diapositive avec des points

    - **gras** et *italique* et `code`
    - [un lien](https://example.org)
    - ![une image](photo.jpg)
    - maths : $x^2$ dans une phrase, ou $$ ... $$ sur des lignes séparées

    <!-- Un commentaire qui n’est pas une directive est une note du présentateur. -->

    ---

    ## Code et diagrammes

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

    # Une diapositive avec un arrière-plan

Un bloc `mermaid` dessine des organigrammes, des diagrammes de séquence, d’états et de classes, des schémas entité-association et des graphiques simples ; les autres types de diagrammes sont affichés sous forme de code source.

`transition: slide` ou `transition: fade` dans l’en-tête (front matter) anime chaque changement de diapositive ; `_transition` sur une diapositive ne modifie que celle-ci, et `none` désactive l’animation.

L’en-tête (front matter) placé tout en haut s’applique à l’ensemble du diaporama ; un commentaire avec un tiret bas, tel que `_class`, `_backgroundColor` ou `_color`, ne s’applique qu’à une seule diapositive.

Les images sont désignées par leur nom de fichier et déposées sous Médias. Le HTML brut et les blocs `<style>` sont supprimés, une diapositive ne peut donc pas apporter son propre balisage.';
$string['help_media'] = 'Les médias que les diapositives désignent par leur nom de fichier. Déposez un fichier ici, puis utilisez son nom dans le Markdown.

    ![une photographie](photo.jpg)

    ![bg](background.jpg)

    ![bg left](background.jpg)

    ![bg fit](diagram.png)

    ![w:400](logo.png)

Une image avec `bg` remplit la diapositive derrière le texte ; ajoutez `left` ou `right` pour la garder d’un seul côté, et `fit` pour l’afficher en entier au lieu de la rogner. Les dimensions sont définies avec `w:` et `h:` en pixels.

Les fichiers peuvent être placés dans des dossiers, le nom comprend alors le dossier : `![](pictures/city.jpg)`. Les GIF et WebP animés fonctionnent comme n’importe quelle autre image.';
$string['import'] = 'Importer';
$string['import_file'] = 'Markdown ou zip';
$string['import_intro'] = 'Déposez les diapositives sous la forme d’un fichier Markdown, ou d’un seul zip contenant un fichier Markdown et les médias qu’il utilise. Dans les deux cas, les diapositives deviennent une nouvelle section.';
$string['import_nothing'] = 'Rien n’a été importé. Déposez un fichier Markdown, ou un zip contenant exactement un fichier Markdown et ses médias.';
$string['import_submit'] = 'Importer les diapositives';
$string['loading'] = 'Chargement de la présentation…';
$string['media_intro'] = 'Déposez les images et les autres fichiers utilisés par les diapositives. Désignez-les dans le Markdown par leur nom de fichier, exactement tel qu’il figure ici - l’onglet Aide montre comment faire.';
$string['modulename'] = 'Diaporama Markdown';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Diaporamas Markdown';
$string['mudeck:addinstance'] = 'Ajouter une présentation';
$string['mudeck:edit'] = 'Modifier les diapositives';
$string['mudeck:fullaccess'] = 'Accéder à tout moment à toutes les diapositives et aux notes du présentateur';
$string['mudeck:managethemes'] = 'Ajouter et modifier les thèmes de diapositives propres au site';
$string['mudeck:present'] = 'Présenter ou regarder les diapositives';
$string['mudeck:syncdevices'] = 'Suivre sa propre présentation sur un autre appareil';
$string['mudeck:view'] = 'Voir les détails de la présentation';
$string['nopartcontent'] = 'Cette présentation ne contient encore aucune diapositive.';
$string['notes_current'] = 'À l’écran';
$string['notes_elapsed'] = 'Temps écoulé depuis le début';
$string['notes_exit'] = 'Fermer les notes';
$string['notes_gone'] = 'Cette session n’est plus en cours. Ouvrez les notes de la session actuelle depuis l’onglet Sessions.';
$string['notes_heading'] = 'Notes du présentateur';
$string['notes_none'] = 'Cette diapositive n’a pas de notes.';
$string['notes_reconnect'] = 'Retour aux sessions';
$string['notes_stale'] = 'Les diapositives ont été modifiées pendant la présentation, ces notes peuvent donc ne pas correspondre à ce qui est à l’écran. Redémarrez la présentation, puis ouvrez ses notes depuis l’onglet Sessions - une présentation redémarrée est une nouvelle session, cette page ne peut donc pas simplement être rechargée.';
$string['notes_waiting'] = 'En attente du démarrage de la présentation sur l’autre appareil.';
$string['overview'] = 'Vue d’ensemble des diapositives';
$string['paginate'] = 'Afficher les numéros des diapositives';
$string['paginate_help'] = 'Ajoute un numéro à chaque diapositive, sauf si la diapositive elle-même indique autre chose.';
$string['part_add'] = 'Ajouter une section';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'Les diapositives de cette section, écrites en Markdown. L’onglet Aide à côté de l’aperçu explique comment faire.';
$string['part_delete'] = 'Supprimer la section';
$string['part_delete_confirm'] = 'Supprimer la section « {$a} » avec toutes ses diapositives et tous ses médias ?';
$string['part_deleted'] = 'La section a été supprimée.';
$string['part_edit'] = 'Modifier les diapositives';
$string['part_empty'] = 'vide';
$string['part_imported'] = 'Les diapositives ont été importées dans une nouvelle section.';
$string['part_media'] = 'Médias';
$string['part_media_missing'] = 'Ces fichiers sont utilisés dans les diapositives mais n’ont pas encore été déposés : {$a}';
$string['part_move'] = 'Déplacer';
$string['part_moved'] = 'Déplacée à la position {$a}.';
$string['part_moveto'] = 'Déplacer à la position';
$string['part_name'] = 'Nom de la section';
$string['part_new'] = 'Section {$a}';
$string['part_preview'] = 'Aperçu';
$string['part_save_close'] = 'Enregistrer et fermer';
$string['part_save_continue'] = 'Enregistrer et continuer';
$string['part_saved'] = 'Diapositives enregistrées.';
$string['part_starter'] = '# Votre titre

Indiquez de quoi il s’agit

---

## Une diapositive avec des points

- le **premier** point
- et une *remarque*

---

## Dernière diapositive

Merci !

<!-- Un commentaire est une note du présentateur : jamais sur la diapositive, toujours sur la version imprimée. -->';
$string['pluginadministration'] = 'Administration de la présentation';
$string['pluginname'] = 'Diaporama Markdown';
$string['present'] = 'Démarrer la présentation';
$string['presentation'] = 'Présentation';
$string['print'] = 'Imprimer avec les notes';
$string['print_date'] = 'Imprimé le {$a}';
$string['print_help'] = 'Chaque diapositive sur sa propre page avec les notes du présentateur en dessous - l’exemplaire du présentateur.';
$string['print_slides'] = 'Imprimer les diapositives';
$string['print_slides_help'] = 'Chaque diapositive sur sa propre page, sans les notes du présentateur. Enregistré au format PDF, c’est une copie de toute la présentation qui s’ouvre partout.';
$string['privacy:completed'] = 'Atteinte de la fin d’une présentation';
$string['privacy:metadata:mudeck_completed'] = 'Un enregistrement indiquant qu’un utilisateur a atteint la fin d’une présentation.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'Le moment où la fin a été atteinte.';
$string['privacy:metadata:mudeck_completed:userid'] = 'L’utilisateur qui a atteint la fin.';
$string['privacy:metadata:mudeck_part'] = 'Les diapositives d’une présentation, qui mémorisent qui les a enregistrées en dernier.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'Le moment du dernier enregistrement des diapositives.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'L’utilisateur qui a enregistré les diapositives en dernier.';
$string['privacy:metadata:mudeck_session'] = 'L’endroit où en est une présentation en cours, afin que le même utilisateur puisse la suivre sur un autre appareil. Personne d’autre ne le voit jamais.';
$string['privacy:metadata:mudeck_session:partid'] = 'La section qui était à l’écran.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Des notes sur la session, par exemple une indication sur l’appareil utilisé.';
$string['privacy:metadata:mudeck_session:slide'] = 'La diapositive qui était à l’écran.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'Le titre de cette diapositive, afin qu’un autre appareil puisse montrer où en est la présentation.';
$string['privacy:metadata:mudeck_session:timeended'] = 'Le moment où la présentation a été quittée.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'Le moment du dernier signal reçu de l’appareil.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'Le moment où la présentation a été démarrée.';
$string['privacy:metadata:mudeck_session:userid'] = 'L’utilisateur à qui appartiennent ces appareils.';
$string['privacy:metadata:mudeck_theme'] = 'Un thème de diapositives ajouté au site, qui mémorise qui l’a enregistré en dernier.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'Le moment du dernier enregistrement du thème.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'L’utilisateur qui a enregistré le thème en dernier.';
$string['privacy:sessions'] = 'Synchronisation des appareils';
$string['session_ago'] = 'il y a {$a}';
$string['session_delete'] = 'Supprimer';
$string['session_deleted'] = 'La session a été supprimée.';
$string['session_ended'] = 'Terminée';
$string['session_lastseen'] = 'Dernière activité';
$string['session_lastslide'] = 'Dernière diapositive';
$string['session_notes'] = 'Afficher les notes synchronisées';
$string['session_started'] = 'Démarrée';
$string['session_unknownslide'] = 'Pas encore démarrée';
$string['session_where'] = 'Diapositive actuelle';
$string['sessions'] = 'Sessions';
$string['sessions_delete_finished'] = 'Supprimer les sessions terminées';
$string['sessions_finished'] = 'Terminées';
$string['sessions_finished_deleted'] = 'Nombre de sessions terminées supprimées : {$a}.';
$string['sessions_finished_help'] = 'Rien ne peut plus suivre ces sessions : elles ont atteint la fin, ne donnent plus signe d’activité, ou leurs diapositives ont été modifiées depuis.';
$string['sessions_intro'] = 'Les présentations que vous donnez en ce moment. Seuls vos propres appareils figurent ici.';
$string['sessions_none'] = 'Aucune présentation n’est en cours. Démarrez la présentation et elle apparaîtra ici, prête à être suivie sur un autre appareil.';
$string['sessions_running'] = 'En cours';
$string['slide_exit'] = 'Quitter la présentation';
$string['slide_fullscreen'] = 'Plein écran';
$string['slide_next'] = 'Diapositive suivante';
$string['slide_notes'] = 'Notes du présentateur';
$string['slide_of'] = 'Diapositive {$a->current} sur {$a->total}';
$string['slide_overview'] = 'Toutes les diapositives';
$string['slide_previous'] = 'Diapositive précédente';
$string['slides'] = 'Diapositives';
$string['tab_overview'] = 'Vue d’ensemble';
$string['theme'] = 'Thème';
$string['theme_add'] = 'Ajouter un thème';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'Le thème Marp, écrit en CSS. Commencez par `@import \'default\';` pour partir d’un thème existant, puis modifiez ce dont vous avez besoin.

Ce CSS est envoyé à toute personne qui voit une présentation utilisant le thème, et il n’est pas vérifié. N’ajoutez que du CSS de confiance.';
$string['theme_css_starter'] = '/* Partez d’un thème existant, puis modifiez ce dont vous avez besoin. */
@import \'default\';

/* Dans le CSS de Marp, section est une diapositive. */
section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Par défaut';
$string['theme_default_site'] = 'Thème par défaut';
$string['theme_default_site_desc'] = 'Le thème avec lequel une nouvelle présentation démarre. Une activité peut en choisir un autre, et un diaporama peut désigner le sien dans l’en-tête (front matter) avec `theme:`.';
$string['theme_delete_confirm'] = 'Supprimer le thème « {$a->name} » ? Nombre de présentations qui l’utilisent : {$a->used}. Elles reprendront l’apparence par défaut.';
$string['theme_deleted'] = 'Le thème a été supprimé.';
$string['theme_edit'] = 'Modifier le thème';
$string['theme_follow_site'] = 'Thème par défaut du site ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'L’apparence utilisée pour les diapositives qui ne choisissent pas leur propre thème dans le Markdown.';
$string['theme_manage'] = 'Thèmes de diapositives';
$string['theme_manage_intro'] = 'Les thèmes ajoutés ici peuvent être choisis dans toute présentation de ce site, en plus des thèmes fournis avec le plugin.';
$string['theme_name'] = 'Nom';
$string['theme_name_help'] = 'Le nom du thème dans la liste des thèmes lors de la configuration d’une présentation, par exemple « Orange ».';
$string['theme_none'] = 'Ce site n’a pas encore de thèmes propres.';
$string['theme_orange'] = 'Orange';
$string['theme_saved'] = 'Le thème a été enregistré.';
$string['theme_shortname'] = 'Nom abrégé';
$string['theme_shortname_help'] = 'Le nom utilisé par les diapositives, par exemple `orange`. Écrivez `theme: orange` dans le Markdown pour choisir ce thème pour un diaporama.

Lettres, chiffres, tiret et tiret bas uniquement. Il ne peut pas être changé aussi facilement par la suite : les présentations mémorisent le nom abrégé, pas le nom.';
$string['theme_shortname_invalid'] = 'Utilisez uniquement des lettres, des chiffres, le tiret et le tiret bas.';
$string['theme_shortname_reserved'] = 'Un thème fourni avec le plugin utilise déjà ce nom abrégé.';
$string['theme_shortname_taken'] = 'Un autre thème utilise déjà ce nom abrégé.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Utilisé par';
