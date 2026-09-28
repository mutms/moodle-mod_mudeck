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

namespace mod_mudeck\muform\element;

use core\url;
use core_renderer;
use mod_mudeck\local\theme;
use stdClass;
use tool_mulib\muform\element;

/**
 * Part editor layout: the form fields on the left, slide preview, media and help on the right.
 *
 * Children are placed by name in the template, the element module draws the preview.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class partcontainer extends element {
    /** @var stdClass mudeck record */
    private stdClass $mudeck;

    /**
     * Constructor.
     *
     * @param string $name
     * @param stdClass $mudeck
     */
    public function __construct(string $name, stdClass $mudeck) {
        parent::__construct($name);
        $this->mudeck = $mudeck;
    }

    #[\Override]
    public function accepts_children(): bool {
        return true;
    }

    #[\Override]
    public function returns_data(): bool {
        return false;
    }

    #[\Override]
    protected function parse_value(): void {
        $this->value = null;
    }

    #[\Override]
    protected function get_template_data(core_renderer $output, array $allerrors): array {
        $context = parent::get_template_data($output, $allerrors);

        // The preview reads the pictures from the draft area, so an upload shows before the part is saved.
        $draftitemid = (int)$this->get_form()->get_element('attachments')->get_value();
        $config = [
            'themecss' => theme::get_custom_css(new url('/mod/mudeck')),
            'theme' => theme::resolve($this->mudeck->theme),
            'imagesurl' => url::routed_path("/api/rest/v2/mod_mudeck/part/edit/{$draftitemid}/images")->out(false),
            'mediabase' => url::make_draftfile_url($draftitemid, '/', '')->out(false),
            'labels' => [
                'preview' => get_string('editor_preview', 'mod_mudeck'),
                'slide' => get_string('editor_slide', 'mod_mudeck', '{$a}'),
                'media' => get_string('part_media', 'mod_mudeck'),
                'fullscreen' => get_string('slide_fullscreen', 'mod_mudeck'),
            ],
        ];
        // Hex escapes keep the JSON free of entities, the attribute escaping then round trips exactly.
        $flags = JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $context['configjson'] = json_encode($config, $flags);
        // Help that stays open and can be copied from, which a popover cannot do.
        $context['helpmarkdownhtml'] = markdown_to_html(get_string('help_markdown', 'mod_mudeck'));
        $context['helpmediahtml'] = markdown_to_html(get_string('help_media', 'mod_mudeck'));

        return $context;
    }
}
