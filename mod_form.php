<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Clear Lesson configuration form
 *
 * @package    mod_clearlesson
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/clearlesson/locallib.php');

class mod_clearlesson_mod_form extends moodleform_mod {
    public function definition() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        $mform = $this->_form;
        $config = get_config('clearlesson');
        $PAGE->add_body_class('mod-clearlesson-body');
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $options = clearlesson_get_resource_type_options();
        $select = $mform->addElement('select', 'type', get_string('type', 'clearlesson'), $options, ['disabled' => 'disabled']);
        $browseresourcesstring = get_string('browseresources', 'clearlesson');
        $buttonhtml = '<button type="button" id="resource-select-button"
                        class="btn btn-primary" disabled="disabled">' . $browseresourcesstring . '</button>';
        $refselectgroup = [];
        $autocompleteoptions = [
            'multiple' => false,
            'noselectionstring' => get_string('noresourceselected', 'mod_clearlesson') . '...',
            'ajax' => 'mod_clearlesson/form-potential-resource-selector',
            'valuehtmlcallback' => function($value) {
                global $OUTPUT, $DB;
                if ($resource = $DB->get_record('clearlesson', ['id' => $this->current->instance])) {
                    $details = mod_clearlesson\call::get_potential_resources($resource->type, $resource->externalref, true);
                    // Now get any additional details we need to display using the clearlessons api.
                    return $OUTPUT->render_from_template('mod_clearlesson/autocomplete_results/form_resource_selector_suggestion', $details);
                } else {
                    return 'No selection';
                }
            }
        ];
        $refselectgroup[] =& $mform->createElement('autocomplete', 'externalref',
                    get_string('selectaresource', 'clearlesson'),
                    [], $autocompleteoptions);
        $mform->setType('externalref', PARAM_TEXT);
        $refselectgroup[] =& $mform->createElement('html', $buttonhtml);
        $mform->addGroup($refselectgroup, 'ref_select_group', get_string('selectaresource', 'clearlesson'), array(''), false);
        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        $mform->addElement('header', 'optionssection', get_string('appearance'));
        $default = '';
        if ($this->current->instance) {
            $default = $this->current->display;
        }
        $options = clearlesson_get_display_options($config->displayoptions, $default);

        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'url'), $options);
            $mform->setDefault('display', $config->display);
            $mform->addHelpButton('display', 'displayselect', 'url');
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'url'), array('size' => 3));
            if (count($options) > 1) {
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'url'), array('size' => 3));
            if (count($options) > 1) {
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $this->standard_coursemodule_elements();
        $PAGE->requires->js_call_amd('mod_clearlesson/modal-resource-browser', 'init');
        $PAGE->requires->js_call_amd('mod_clearlesson/modal-resource-menu', 'init');
        $PAGE->requires->js_call_amd('mod_clearlesson/modal-video-player', 'init');
        $this->add_action_buttons();
    }

    /**
     * Add elements for setting the custom completion rules.
     *
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     */
    public function add_completion_rules() {

        $mform = $this->_form;

        $mform->addElement(
            'checkbox',
            $this->get_suffixed_name('completionwatchedall'),
            ' ',
            get_string('watchedallrule', 'clearlesson')
        );
        $mform->setType('completionwatchedall', PARAM_INT);
        $mform->disabledIf('completionview', 'completionwatchedall', 'eq', 1);
        $mform->disabledIf('completionwatchedall', 'completionview', 'eq', '1');

        return [$this->get_suffixed_name('completionwatchedall')];
    }

    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }

    /**
     * Called during validation to see whether some activity-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data[$this->get_suffixed_name('completionwatchedall')]));
    }

    public function data_preprocessing(&$defaultvalues) {
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
        if (!empty($defaultvalues['parameters'])) {
            $parameters = unserialize($defaultvalues['parameters']);
            $i = 0;
            foreach ($parameters as $parameter => $variable) {
                $defaultvalues['parameter_'.$i] = $parameter;
                $defaultvalues['variable_'.$i]  = $variable;
                $i++;
            }
        }
    }

    public function validation($data, $files) {
        $errors = [];
        if (empty($data['externalref'])) {
            $errors['ref_select_group'] = get_string('pleaseselectaresource', 'mod_clearlesson');
        }
        return $errors;
    }

}
