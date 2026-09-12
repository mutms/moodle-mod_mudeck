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

use mod_mudeck\local\theme;

defined('MOODLE_INTERNAL') || die;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var stdClass $CFG */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Markdown slide deck activity form.
 *
 * @package    mod_mudeck
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mudeck_mod_form extends moodleform_mod {
    #[\Override]
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements(get_string('moduleintro'));

        $mform->addElement('select', 'theme', get_string('theme', 'mod_mudeck'), theme::get_activity_menu());
        $mform->setDefault('theme', '');
        $mform->addHelpButton('theme', 'theme', 'mod_mudeck');

        $mform->addElement('advcheckbox', 'paginate', get_string('paginate', 'mod_mudeck'));
        $mform->setDefault('paginate', 0);
        $mform->addHelpButton('paginate', 'paginate', 'mod_mudeck');

        $mform->addElement('advcheckbox', 'allowdevicesync', get_string('allowdevicesync', 'mod_mudeck'));
        $mform->setDefault('allowdevicesync', 0);
        $mform->addHelpButton('allowdevicesync', 'allowdevicesync', 'mod_mudeck');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    #[\Override]
    public function add_completion_rules() {
        $mform = $this->_form;
        $name = 'completionreachedend' . $this->get_suffix();

        $mform->addElement('advcheckbox', $name, '', get_string('completionreachedend', 'mod_mudeck'));
        $mform->setDefault($name, 0);

        return [$name];
    }

    #[\Override]
    public function completion_rule_enabled($data) {
        return !empty($data['completionreachedend' . $this->get_suffix()]);
    }

    #[\Override]
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        // The rule only means something with automatic completion; otherwise it is off.
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? null;
            if ($completion != COMPLETION_TRACKING_AUTOMATIC) {
                $data->{'completionreachedend' . $suffix} = 0;
            }
        }
    }
}
