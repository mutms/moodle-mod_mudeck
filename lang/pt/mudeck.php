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
 * Strings for component 'mod_mudeck', language 'pt'.
 *
 * NOTE: This Portuguese translation was produced automatically by an AI
 * language model and has not yet been reviewed by a human translator. Corrections
 * are welcome.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowdevicesync'] = 'Permitir a sincronização entre dispositivos';
$string['allowdevicesync_help'] = 'Quando esta opção está ativa, uma apresentação em curso memoriza o ponto em que se encontra, para que o mesmo utilizador a possa acompanhar noutro dispositivo - um telemóvel ou um tablet ao lado do computador que faz a apresentação. Nada é memorizado se ninguém iniciar a apresentação, cada utilizador vê apenas as suas próprias sessões e, ao desativar esta opção, todas as sessões são apagadas.';
$string['back'] = 'Voltar';
$string['completiondetail:reachedend'] = 'Chegar ao último diapositivo';
$string['completionreachedend'] = 'O aluno tem de chegar ao último diapositivo';
$string['editor_help'] = 'Ajuda';
$string['editor_preview'] = 'Pré-visualização';
$string['editor_slide'] = 'Diapositivo {$a}';
$string['event_part_created'] = 'Parte criada';
$string['event_part_deleted'] = 'Parte apagada';
$string['event_part_exported'] = 'Parte exportada';
$string['event_part_imported'] = 'Parte importada';
$string['event_part_updated'] = 'Parte atualizada';
$string['event_presentation_started'] = 'Apresentação iniciada';
$string['export'] = 'Exportar';
$string['help_markdown'] = 'Os diapositivos são escritos em Markdown. Uma linha que contenha apenas três hífenes inicia um novo diapositivo, com uma linha em branco acima e outra abaixo.

    ---
    theme: orange
    paginate: true
    ---

    # Um diapositivo de título

    O tema da apresentação

    ---

    ## Um diapositivo com tópicos

    - **negrito**, *itálico* e `código`
    - [um link](https://example.org)
    - ![uma imagem](foto.jpg)
    - matemática: $x^2$ numa frase, ou $$ ... $$ em linhas próprias

    <!-- Um comentário que não seja uma diretiva é uma nota do orador. -->

    ---

    ## Código e diagramas

    ```php
    echo "colorido de acordo com a linguagem";
    ```

    ```mermaid
    graph LR
      A[Ideia] --> B[Diapositivos]
    ```

    ---

    <!-- _class: lead -->

    ![bg](fundo.jpg)

    # Um diapositivo com imagem de fundo

Um bloco `mermaid` desenha fluxogramas, diagramas de sequência, de estados e de classes, relações entre entidades e gráficos simples; os outros tipos de diagrama são apresentados como código-fonte.

`transition: slide` ou `transition: fade` no front matter anima todas as mudanças de diapositivo; `_transition` num diapositivo altera apenas esse diapositivo, e `none` desativa a animação.

O front matter, mesmo no início, configura toda a apresentação; um comentário com um carácter de sublinhado, como `_class`, `_backgroundColor` ou `_color`, configura apenas um diapositivo.

As imagens são referidas pelo nome do ficheiro e carregadas em Multimédia. O HTML em bruto e os blocos `<style>` são descartados, pelo que um diapositivo não pode trazer a sua própria marcação.';
$string['help_media'] = 'Ficheiros multimédia que os diapositivos referem pelo nome do ficheiro. Carregue aqui um ficheiro e utilize depois o seu nome no Markdown.

    ![uma fotografia](foto.jpg)

    ![bg](fundo.jpg)

    ![bg left](fundo.jpg)

    ![bg fit](diagrama.png)

    ![w:400](logo.png)

Uma imagem com `bg` preenche o diapositivo por trás do texto; adicione `left` ou `right` para a manter num dos lados, e `fit` para a mostrar por inteiro em vez de a recortar. Os tamanhos são definidos com `w:` e `h:` em píxeis.

Os ficheiros podem estar em pastas e, nesse caso, o nome inclui a pasta: `![](imagens/cidade.jpg)`. Os ficheiros GIF e WebP animados funcionam como qualquer outra imagem.';
$string['import'] = 'Importar';
$string['import_file'] = 'Markdown ou zip';
$string['import_intro'] = 'Carregue os diapositivos como um ficheiro Markdown, ou como um zip que contenha um ficheiro Markdown e os ficheiros multimédia que este utiliza. Em ambos os casos, os diapositivos passam a ser uma nova parte.';
$string['import_nothing'] = 'Nada foi importado. Carregue um ficheiro Markdown, ou um zip que contenha exatamente um ficheiro Markdown e os respetivos ficheiros multimédia.';
$string['import_submit'] = 'Importar diapositivos';
$string['loading'] = 'A carregar a apresentação…';
$string['media_intro'] = 'Carregue as imagens e outros ficheiros utilizados pelos diapositivos. Refira-os no Markdown pelo nome do ficheiro, exatamente como aparecem listados aqui - o separador Ajuda mostra como.';
$string['modulename'] = 'Apresentação de diapositivos em Markdown';
$string['modulename_link'] = 'mod/mudeck/view';
$string['modulenameplural'] = 'Apresentações de diapositivos em Markdown';
$string['mudeck:addinstance'] = 'Adicionar uma nova apresentação';
$string['mudeck:edit'] = 'Editar diapositivos';
$string['mudeck:fullaccess'] = 'Aceder a todos os diapositivos e notas do orador em qualquer momento';
$string['mudeck:managethemes'] = 'Adicionar e editar os temas de diapositivos próprios do site';
$string['mudeck:present'] = 'Apresentar ou assistir aos diapositivos';
$string['mudeck:syncdevices'] = 'Acompanhar a própria apresentação noutro dispositivo';
$string['mudeck:view'] = 'Ver detalhes da apresentação';
$string['nopartcontent'] = 'Esta apresentação ainda não tem diapositivos.';
$string['notes_current'] = 'No ecrã';
$string['notes_elapsed'] = 'Tempo desde o início';
$string['notes_exit'] = 'Fechar notas';
$string['notes_gone'] = 'Esta sessão já não está em curso. Abra as notas da sessão atual a partir do separador Sessões.';
$string['notes_heading'] = 'Notas do orador';
$string['notes_none'] = 'Este diapositivo não tem notas.';
$string['notes_reconnect'] = 'Voltar às sessões';
$string['notes_stale'] = 'Os diapositivos foram alterados enquanto a apresentação estava em curso, pelo que estas notas podem não corresponder ao que está no ecrã. Inicie novamente a apresentação e abra depois as respetivas notas a partir do separador Sessões - uma apresentação reiniciada é uma nova sessão, pelo que não basta recarregar esta página.';
$string['notes_waiting'] = 'A aguardar o início da apresentação no outro dispositivo.';
$string['overview'] = 'Visão geral dos diapositivos';
$string['paginate'] = 'Mostrar números dos diapositivos';
$string['paginate_help'] = 'Adiciona o número do diapositivo a cada diapositivo, exceto se o próprio diapositivo indicar o contrário.';
$string['part_add'] = 'Adicionar parte';
$string['part_content'] = 'Markdown';
$string['part_content_help'] = 'Os diapositivos desta parte, escritos em Markdown. O separador Ajuda, ao lado da pré-visualização, explica como.';
$string['part_delete'] = 'Apagar parte';
$string['part_delete_confirm'] = 'Apagar a parte "{$a}" com todos os seus diapositivos e ficheiros multimédia?';
$string['part_deleted'] = 'A parte foi apagada.';
$string['part_edit'] = 'Editar diapositivos';
$string['part_empty'] = 'vazia';
$string['part_imported'] = 'Os diapositivos foram importados como uma nova parte.';
$string['part_media'] = 'Multimédia';
$string['part_media_missing'] = 'Estes ficheiros são utilizados nos diapositivos, mas ainda não foram carregados: {$a}';
$string['part_move'] = 'Mover';
$string['part_moved'] = 'Movida para a posição {$a}.';
$string['part_moveto'] = 'Mover para a posição';
$string['part_name'] = 'Nome da parte';
$string['part_new'] = 'Parte {$a}';
$string['part_preview'] = 'Pré-visualização';
$string['part_save_close'] = 'Guardar e fechar';
$string['part_save_continue'] = 'Guardar e continuar';
$string['part_saved'] = 'Diapositivos guardados.';
$string['part_starter'] = '# O seu título

Indique de que trata a apresentação

---

## Um diapositivo com tópicos

- o **primeiro** tópico
- e um *aparte*

---

## Último diapositivo

Obrigado!

<!-- Um comentário é uma nota do orador: nunca aparece no diapositivo, aparece sempre na versão impressa. -->';
$string['pluginadministration'] = 'Administração da apresentação';
$string['pluginname'] = 'Apresentação de diapositivos em Markdown';
$string['present'] = 'Iniciar apresentação';
$string['presentation'] = 'Apresentação';
$string['print'] = 'Imprimir com notas';
$string['print_date'] = 'Impresso em {$a}';
$string['print_help'] = 'Cada diapositivo numa página própria, com as notas do orador por baixo - a cópia do apresentador.';
$string['print_slides'] = 'Imprimir diapositivos';
$string['print_slides_help'] = 'Cada diapositivo numa página própria, sem as notas do orador. Guardada como PDF, é uma cópia de toda a apresentação que abre em qualquer lado.';
$string['privacy:completed'] = 'Chegada ao fim de uma apresentação';
$string['privacy:metadata:mudeck_completed'] = 'Registo de que um utilizador chegou ao fim de uma apresentação.';
$string['privacy:metadata:mudeck_completed:timecompleted'] = 'Quando o fim foi alcançado.';
$string['privacy:metadata:mudeck_completed:userid'] = 'Quem chegou ao fim.';
$string['privacy:metadata:mudeck_part'] = 'Os diapositivos de uma apresentação, que memorizam quem os guardou pela última vez.';
$string['privacy:metadata:mudeck_part:timemodified'] = 'Quando os diapositivos foram guardados pela última vez.';
$string['privacy:metadata:mudeck_part:usermodified'] = 'Quem guardou os diapositivos pela última vez.';
$string['privacy:metadata:mudeck_session'] = 'O ponto em que se encontra uma apresentação em curso, para que o mesmo utilizador a possa acompanhar noutro dispositivo. Mais ninguém o vê.';
$string['privacy:metadata:mudeck_session:partid'] = 'Que parte estava no ecrã.';
$string['privacy:metadata:mudeck_session:sessionjson'] = 'Notas sobre a sessão, como uma indicação do dispositivo utilizado.';
$string['privacy:metadata:mudeck_session:slide'] = 'Que diapositivo estava no ecrã.';
$string['privacy:metadata:mudeck_session:slidetitle'] = 'O título desse diapositivo, para que outro dispositivo possa mostrar em que ponto está a apresentação.';
$string['privacy:metadata:mudeck_session:timeended'] = 'Quando se saiu da apresentação.';
$string['privacy:metadata:mudeck_session:timelastseen'] = 'Quando o dispositivo comunicou pela última vez.';
$string['privacy:metadata:mudeck_session:timestarted'] = 'Quando a apresentação foi iniciada.';
$string['privacy:metadata:mudeck_session:userid'] = 'A quem pertencem estes dispositivos.';
$string['privacy:metadata:mudeck_theme'] = 'Um tema de diapositivos adicionado ao site, que memoriza quem o guardou pela última vez.';
$string['privacy:metadata:mudeck_theme:timemodified'] = 'Quando o tema foi guardado pela última vez.';
$string['privacy:metadata:mudeck_theme:usermodified'] = 'Quem guardou o tema pela última vez.';
$string['privacy:sessions'] = 'Sincronização entre dispositivos';
$string['session_ago'] = 'há {$a}';
$string['session_delete'] = 'Apagar';
$string['session_deleted'] = 'A sessão foi apagada.';
$string['session_ended'] = 'Terminada';
$string['session_lastseen'] = 'Última atividade';
$string['session_lastslide'] = 'Último diapositivo';
$string['session_notes'] = 'Mostrar notas sincronizadas';
$string['session_started'] = 'Iniciada';
$string['session_unknownslide'] = 'Ainda não iniciada';
$string['session_where'] = 'Diapositivo atual';
$string['sessions'] = 'Sessões';
$string['sessions_delete_finished'] = 'Apagar sessões terminadas';
$string['sessions_finished'] = 'Terminadas';
$string['sessions_finished_deleted'] = 'Sessões terminadas apagadas: {$a}.';
$string['sessions_finished_help'] = 'Já não é possível acompanhar estas sessões: chegaram ao fim, deixaram de comunicar ou os seus diapositivos foram entretanto editados.';
$string['sessions_intro'] = 'Apresentações que tem em curso neste momento. Apenas os seus próprios dispositivos são listados aqui.';
$string['sessions_none'] = 'Não há nada em curso. Inicie a apresentação e ela aparecerá aqui, pronta para ser acompanhada noutro dispositivo.';
$string['sessions_running'] = 'Em curso';
$string['slide_exit'] = 'Sair da apresentação';
$string['slide_fullscreen'] = 'Ecrã completo';
$string['slide_next'] = 'Diapositivo seguinte';
$string['slide_notes'] = 'Notas do orador';
$string['slide_of'] = 'Diapositivo {$a->current} de {$a->total}';
$string['slide_overview'] = 'Todos os diapositivos';
$string['slide_previous'] = 'Diapositivo anterior';
$string['slides'] = 'Diapositivos';
$string['tab_overview'] = 'Visão geral';
$string['theme'] = 'Tema';
$string['theme_add'] = 'Adicionar um tema';
$string['theme_css'] = 'CSS';
$string['theme_css_help'] = 'O tema Marp, escrito em CSS. Comece com `@import \'default\';` para partir de um tema já existente e altere depois o que for necessário.

Este CSS é apresentado a todos os que veem uma apresentação que utilize o tema e não é verificado. Adicione apenas CSS em que confie.';
$string['theme_css_starter'] = '/* Parta de um tema existente e altere depois o que for necessário. */
@import \'default\';

section {
    background: #ffffff;
}
';
$string['theme_default'] = 'Predefinido';
$string['theme_default_site'] = 'Tema predefinido';
$string['theme_default_site_desc'] = 'O tema com que uma nova apresentação começa. Uma atividade pode escolher outro, e uma apresentação pode indicar o seu próprio tema no front matter com `theme:`.';
$string['theme_delete_confirm'] = 'Apagar o tema "{$a->name}"? Número de apresentações que o utilizam e que passarão a ter o aspeto predefinido: {$a->used}.';
$string['theme_deleted'] = 'O tema foi apagado.';
$string['theme_edit'] = 'Editar o tema';
$string['theme_follow_site'] = 'Predefinição do site ({$a})';
$string['theme_gaia'] = 'Gaia';
$string['theme_help'] = 'O aspeto utilizado nos diapositivos que não escolhem o seu próprio tema no Markdown.';
$string['theme_manage'] = 'Temas de diapositivos';
$string['theme_manage_intro'] = 'Os temas adicionados aqui podem ser escolhidos em qualquer apresentação deste site, a par dos temas fornecidos com o módulo.';
$string['theme_name'] = 'Nome';
$string['theme_name_help'] = 'O nome do tema na lista de temas quando alguém configura uma apresentação, por exemplo "Laranja".';
$string['theme_none'] = 'Este site ainda não tem temas próprios.';
$string['theme_orange'] = 'Laranja';
$string['theme_saved'] = 'O tema foi guardado.';
$string['theme_shortname'] = 'Nome curto';
$string['theme_shortname_help'] = 'O nome utilizado pelos diapositivos, por exemplo `orange`. Escreva-o no Markdown como `theme: orange` para escolher este tema para uma apresentação.

Apenas letras, algarismos, hífen e carácter de sublinhado. Mais tarde não pode ser alterado com a mesma facilidade: as apresentações memorizam o nome curto, não o nome.';
$string['theme_shortname_invalid'] = 'Utilize apenas letras, algarismos, hífen e carácter de sublinhado.';
$string['theme_shortname_reserved'] = 'Um tema fornecido com o módulo já utiliza este nome curto.';
$string['theme_shortname_taken'] = 'Outro tema já utiliza este nome curto.';
$string['theme_uncover'] = 'Uncover';
$string['theme_used'] = 'Utilizado por';
