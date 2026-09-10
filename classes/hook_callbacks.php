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

/**
 * Hook callbacks.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mudeck;

/**
 * Callbacks for core hooks.
 */
final class hook_callbacks {
    /**
     * Make the bundled Marp renderer importable from our ES modules.
     *
     * Core builds ES modules without bundling, so third party libraries have to be
     * shipped pre-bundled and registered here, otherwise the bare specifier cannot
     * be resolved in the browser.
     *
     * @param \core\hook\output\before_import_map_config $hook
     */
    public static function before_import_map_config(\core\hook\output\before_import_map_config $hook): void {
        $hook->add_import(
            '@mudeck/marp-core',
            path: 'public/mod/mudeck/js/vendor/marp-core',
            devreplacements: [],
        );
    }
}
