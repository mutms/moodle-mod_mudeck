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
 * Strings for component 'mod_mudeck', language 'es'.
 *
 * NOTE: This Spanish translation was produced automatically by an AI language
 * model and has not yet been reviewed by a human translator. Corrections are
 * welcome.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowdevicesync'] = 'Permitir la sincronización de dispositivos';
$string['allowdevicesync_help'] = 'Si se habilita, una presentación en curso recuerda dónde se encuentra, de modo que el mismo usuario puede seguirla en otro dispositivo: un teléfono o una tableta junto al equipo desde el que se presenta. No se recuerda nada a menos que alguien inicie la presentación, cada usuario ve únicamente sus propias sesiones y, al desactivar esta opción, se eliminan todas.';
$string['back'] = 'Atrás';
$string['completiondetail:reachedend'] = 'Llegar a la última diapositiva';
$string['completionreachedend'] = 'El estudiante debe llegar a la última diapositiva';
$string['editor_help'] = 'Ayuda';
$string['editor_preview'] = 'Vista previa';
$string['editor_slide'] = 'Diapositiva {$a}';
$string['event_part_created'] = 'Sección creada';
$string['event_part_deleted'] = 'Sección eliminada';
$string['event_part_exported'] = 'Sección exportada';
$string['event_part_imported'] = 'Sección importada';
$string['event_part_updated'] = 'Sección actualizada';
$string['event_presentation_started'] = 'Presentación iniciada';
$string['export'] = 'Exportar';
$string['help_markdown'] = 'Las diapositivas se escriben en Markdown. Una línea que solo contiene tres guiones inicia una nueva diapositiva, con una línea vacía por encima y otra por debajo.

    ---
    theme: orange
    paginate: true
    ---

    # Una diapositiva de título

    De qué trata la charla

    ---

    ## Una diapositiva con puntos

    - **negrita**, *cursiva* y `código`
    - [un enlace](https://example.org)
    - ![una imagen](photo.jpg)
    - matemáticas: $x^2$ dentro de una frase, o $$ ... $$ en líneas aparte

    <!-- Un comentario que no es una directiva es una nota del orador. -->

    ---

    ## Código y diagramas

    ```php
    echo "coloreado según el lenguaje";
    ```

    ```mermaid
    graph LR
      A[Idea] --> B[Diapositivas]
    ```

    ---

    <!-- _class: lead -->

    ![bg](background.jpg)

    # Una diapositiva con fondo

Un bloque `mermaid` dibuja diagramas de flujo, de secuencia, de estados y de clases, diagramas entidad-relación y gráficos sencillos; los demás tipos de diagrama se muestran como código fuente.

`transition: slide` o `transition: fade` en el front matter anima todos los cambios de diapositiva; `_transition` en una diapositiva cambia solo esa, y `none` desactiva la animación.

El front matter, al principio del todo, configura toda la presentación; un comentario con guion bajo, como `_class`, `_backgroundColor` o `_color`, configura una sola diapositiva.

Las imágenes se indican por su nombre de archivo y se suben en Medios. El HTML sin procesar y los bloques `<style>` se descartan, por lo que una diapositiva no puede aportar su propio marcado.';
$string['help_media'] = 'Medios a los que las diapositivas hacen referencia por nombre de archivo. Suba aquí un archivo y después utilice su nombre en el Markdown.

    ![una fotografía](photo.jpg)

    ![bg](background.jpg)

    ![bg left](background.jpg)

    ![bg fit](diagram.png)

    ![w:400](logo.png)

Una imagen con `bg` ocupa toda la diapositiva por detrás del texto; añada `left` o `right` para mantenerla a un lado, y `fit` para mostrarla entera en lugar de recortarla. Los tamaños se definen con `w:` y `h:` en píxeles.

Los archivos pueden estar en carpetas, y entonces el nombre incluye la carpeta: `![](pictures/city.jpg)`. Los GIF y WebP animados funcionan como cualquier otra imagen.';
$string['import'] = 'Importar';
$string['import_file'] = 'Markdown o zip';
$string['import_intro'] = 'Suba las diapositivas como un archivo Markdown, o como un único zip que contenga un archivo Markdown y los medios que utiliza. En ambos casos las diapositivas se convierten en una sección nueva.';
$string['import_nothing'] = 'No se ha importado nada. Suba un archivo Markdown, o un zip que contenga exactamente un archivo Markdown y sus medios.';
$string['import_submit'] = 'Importar diapositivas';
$string['loading'] = 'Cargando la presentación…';
$string['media_intro'] = 'Suba las imágenes y los demás archivos que utilizan las diapositivas. Haga referencia a ellos en el Markdown por su nombre de archivo, exactamente como aparecen aquí; la pestaña Ayuda muestra cómo.';
$string['modulename'] = 'Presentación de diapositivas Markdown';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Presentaciones de diapositivas Markdown';
$string['mudeck:addinstance'] = 'Añadir una nueva presentación';
$string['mudeck:edit'] = 'Editar diapositivas';
$string['mudeck:fullaccess'] = 'Acceder a todas las diapositivas y notas del orador en cualquier momento';
$string['mudeck:managethemes'] = 'Añadir y editar los temas de diapositivas propios del sitio';
$string['mudeck:present'] = 'Presentar o ver las diapositivas';
$string['mudeck:syncdevices'] = 'Seguir la presentación propia en otro dispositivo';
$string['mudeck:view'] = 'Ver los detalles de la presentación';
$string['nopartcontent'] = 'Esta presentación todavía no tiene diapositivas.';
$string['notes_current'] = 'En pantalla';
$string['notes_elapsed'] = 'Tiempo desde el inicio';
$string['notes_exit'] = 'Cerrar notas';
$string['notes_gone'] = 'Esta sesión ya no está en curso. Abra las notas de la sesión actual desde la pestaña Sesiones.';
$string['notes_heading'] = 'Notas del orador';
$string['notes_none'] = 'Esta diapositiva no tiene notas.';
$string['notes_reconnect'] = 'Volver a las sesiones';
$string['notes_stale'] = 'Las diapositivas han cambiado mientras la presentación estaba en curso, por lo que estas notas pueden no coincidir con lo que aparece en pantalla. Inicie de nuevo la presentación y después abra sus notas desde la pestaña Sesiones: una presentación reiniciada es una sesión nueva, por lo que no basta con recargar esta página.';
$string['notes_waiting'] = 'Esperando a que la presentación se inicie en el otro dispositivo.';
$string['overview'] = 'Vista general de las diapositivas';
$string['paginate'] = 'Mostrar los números de diapositiva';
$string['paginate_help'] = 'Añade un número de diapositiva a cada diapositiva, salvo que la propia diapositiva indique otra cosa.';
$string['part_add'] = 'Añadir sección';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'Las diapositivas de esta sección, escritas en Markdown. La pestaña Ayuda, junto a la vista previa, explica cómo.';
$string['part_delete'] = 'Eliminar sección';
$string['part_delete_confirm'] = '¿Eliminar la sección "{$a}" con todas sus diapositivas y medios?';
$string['part_deleted'] = 'La sección ha sido eliminada.';
$string['part_edit'] = 'Editar diapositivas';
$string['part_empty'] = 'vacía';
$string['part_imported'] = 'Las diapositivas se han importado como una sección nueva.';
$string['part_media'] = 'Medios';
$string['part_media_missing'] = 'Estos archivos se utilizan en las diapositivas, pero todavía no se han subido: {$a}';
$string['part_move'] = 'Mover';
$string['part_moved'] = 'Movida a la posición {$a}.';
$string['part_moveto'] = 'Mover a la posición';
$string['part_name'] = 'Nombre de la sección';
$string['part_new'] = 'Sección {$a}';
$string['part_preview'] = 'Vista previa';
$string['part_save_close'] = 'Guardar y cerrar';
$string['part_save_continue'] = 'Guardar y continuar';
$string['part_saved'] = 'Diapositivas guardadas.';
$string['part_starter'] = '# Su título

Indique de qué trata

---

## Una diapositiva con puntos

- el **primer** punto
- y un *inciso*
---

## Última diapositiva

¡Gracias!

<!-- Un comentario es una nota del orador: nunca aparece en la diapositiva, siempre en la copia impresa. -->';
$string['pluginadministration'] = 'Administración de la presentación';
$string['pluginname'] = 'Presentación de diapositivas Markdown';
$string['present'] = 'Iniciar presentación';
$string['presentation'] = 'Presentación';
$string['print'] = 'Imprimir con notas';
$string['print_date'] = 'Impreso el {$a}';
$string['print_help'] = 'Cada diapositiva en su propia página con sus notas del orador debajo: la copia del presentador.';
$string['print_slides'] = 'Imprimir diapositivas';
$string['print_slides_help'] = 'Cada diapositiva en su propia página, sin las notas del orador. Guardada como PDF, es una copia de toda la presentación que se abre en cualquier parte.';
$string['privacy:completed'] = 'Llegada al final de una presentación';
$string['privacy:metadata:mudeck_completed'] = 'Registro de que un usuario ha llegado al final de una presentación.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'Cuándo se llegó al final.';
$string['privacy:metadata:mudeck_completed:userid'] = 'Quién llegó al final.';
$string['privacy:metadata:mudeck_part'] = 'Las diapositivas de una presentación, que recuerdan quién las guardó por última vez.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'Cuándo se guardaron las diapositivas por última vez.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'Quién guardó las diapositivas por última vez.';
$string['privacy:metadata:mudeck_session'] = 'Dónde se encuentra una presentación en curso, para que el mismo usuario pueda seguirla en otro dispositivo. Nadie más lo ve nunca.';
$string['privacy:metadata:mudeck_session:partid'] = 'Qué sección estaba en pantalla.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Notas sobre la sesión, como una indicación del dispositivo utilizado.';
$string['privacy:metadata:mudeck_session:slide'] = 'Qué diapositiva estaba en pantalla.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'El encabezado de esa diapositiva, para que otro dispositivo pueda mostrar dónde se encuentra la presentación.';
$string['privacy:metadata:mudeck_session:timeended'] = 'Cuándo se abandonó la presentación.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'Cuándo se tuvo noticia del dispositivo por última vez.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'Cuándo se inició la presentación.';
$string['privacy:metadata:mudeck_session:userid'] = 'De quién son estos dispositivos.';
$string['privacy:metadata:mudeck_theme'] = 'Un tema de diapositivas añadido al sitio, que recuerda quién lo guardó por última vez.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'Cuándo se guardó el tema por última vez.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'Quién guardó el tema por última vez.';
$string['privacy:sessions'] = 'Sincronización de dispositivos';
$string['session_ago'] = 'hace {$a}';
$string['session_delete'] = 'Eliminar';
$string['session_deleted'] = 'La sesión ha sido eliminada.';
$string['session_ended'] = 'Finalizada';
$string['session_lastseen'] = 'Última actividad';
$string['session_lastslide'] = 'Última diapositiva';
$string['session_notes'] = 'Mostrar notas sincronizadas';
$string['session_started'] = 'Iniciada';
$string['session_unknownslide'] = 'Aún no iniciada';
$string['session_where'] = 'Diapositiva actual';
$string['sessions'] = 'Sesiones';
$string['sessions_delete_finished'] = 'Eliminar sesiones finalizadas';
$string['sessions_finished'] = 'Finalizadas';
$string['sessions_finished_deleted'] = 'Sesiones finalizadas eliminadas: {$a}.';
$string['sessions_finished_help'] = 'Estas sesiones ya no se pueden seguir: llegaron al final, dejaron de dar señales o sus diapositivas se han editado desde entonces.';
$string['sessions_intro'] = 'Presentaciones que tiene en curso en este momento. Aquí solo aparecen sus propios dispositivos.';
$string['sessions_none'] = 'No hay nada en curso. Inicie la presentación y aparecerá aquí, lista para seguirla desde otro dispositivo.';
$string['sessions_running'] = 'En curso ahora';
$string['slide_exit'] = 'Salir de la presentación';
$string['slide_fullscreen'] = 'Pantalla completa';
$string['slide_next'] = 'Diapositiva siguiente';
$string['slide_notes'] = 'Notas del orador';
$string['slide_of'] = 'Diapositiva {$a->current} de {$a->total}';
$string['slide_overview'] = 'Todas las diapositivas';
$string['slide_previous'] = 'Diapositiva anterior';
$string['slides'] = 'Diapositivas';
$string['tab_overview'] = 'Vista general';
$string['theme'] = 'Tema';
$string['theme_add'] = 'Añadir un tema';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'El tema Marp, escrito como CSS. Empiece con `@import \'default\';` para partir de un tema que ya existe y después cambie lo que necesite.

Este CSS se muestra a todos los que ven una presentación que utiliza el tema, y no se comprueba. Añada únicamente CSS en el que confíe.';
$string['theme_css_starter'] = '/* Parta de un tema que ya existe y después cambie lo que necesite. */
@import \'default\';

/* En el CSS de Marp, section es una diapositiva. */
section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Predeterminado';
$string['theme_default_site'] = 'Tema predeterminado';
$string['theme_default_site_desc'] = 'El tema con el que comienza una presentación nueva. Una actividad puede elegir otro distinto, y una presentación puede indicar el suyo en el front matter con `theme:`.';
$string['theme_delete_confirm'] = '¿Eliminar el tema "{$a->name}"? Número de presentaciones que lo utilizan: {$a->used}; pasarán a mostrarse con el aspecto predeterminado.';
$string['theme_deleted'] = 'El tema ha sido eliminado.';
$string['theme_edit'] = 'Editar el tema';
$string['theme_follow_site'] = 'Predeterminado del sitio ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'El aspecto que se utiliza en las diapositivas que no eligen su propio tema en el Markdown.';
$string['theme_manage'] = 'Temas de diapositivas';
$string['theme_manage_intro'] = 'Los temas añadidos aquí pueden elegirse en cualquier presentación de este sitio, junto a los temas que vienen con el plugin.';
$string['theme_name'] = 'Nombre';
$string['theme_name_help'] = 'Cómo se llama el tema en la lista de temas cuando alguien configura una presentación, por ejemplo "Naranja".';
$string['theme_none'] = 'Este sitio todavía no tiene temas propios.';
$string['theme_orange'] = 'Naranja';
$string['theme_saved'] = 'El tema ha sido guardado.';
$string['theme_shortname'] = 'Nombre corto';
$string['theme_shortname_help'] = 'El nombre que utilizan las diapositivas, por ejemplo `orange`. Escríbalo en el Markdown como `theme: orange` para elegir este tema en una presentación.

Solo letras, dígitos, guion y guion bajo. Después no se puede cambiar con la misma facilidad: las presentaciones recuerdan el nombre corto, no el nombre.';
$string['theme_shortname_invalid'] = 'Utilice únicamente letras, dígitos, guion y guion bajo.';
$string['theme_shortname_reserved'] = 'Un tema que viene con el plugin ya utiliza este nombre corto.';
$string['theme_shortname_taken'] = 'Otro tema ya utiliza este nombre corto.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Utilizado por';
