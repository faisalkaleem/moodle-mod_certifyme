<?php
// This file is part of the Accredible Certificate module for Moodle - http://moodle.org/
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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package    mod_certifyme
 * @author     Faisal Kaleeem <faisal@wizcoders.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Certifyme mod form.
 *
 * @package    mod_certifyme
 * @author     Faisal Kaleeem <faisal@wizcoders.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_certifyme_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $DB, $COURSE, $CFG, $PAGE;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $courseid = optional_param('course', null, PARAM_INT);
        $cmid = optional_param('update', null, PARAM_INT);
        if (!$courseid) {
            $cm = get_coursemodule_from_id('certifyme', $cmid, 0, false, MUST_EXIST);
            $courseid = $cm->course;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        if (!$cmid) {
            $mform->setDefault('name', $course->fullname);
        }

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        // $mform->addRule('name', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        $mform->addElement('text', 'templateid', get_string('templateid', 'mod_certifyme'), array('size' => '64'));
        $mform->setType('templateid', PARAM_INT);
        $mform->addRule('templateid', null, 'required', null, 'client');
        $mform->addRule('templateid', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        $mform->addElement('text', 'text', get_string('text', 'mod_certifyme'), array('size' => '64'));
        $mform->setType('text', PARAM_TEXT);
        $mform->addRule('text', null, 'required', null, 'client');
        $mform->addRule('text', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        $mform->addElement('text', 'licensenumber', get_string('licensenumber', 'mod_certifyme'), array('size' => '64'));
        $mform->setType('licensenumber', PARAM_ALPHANUMEXT);
        $mform->addRule('licensenumber', null, 'required', null, 'client');
        $mform->addRule('licensenumber', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        // $mform->addElement('text', 'verifymode', get_string('verifymode', 'mod_certifyme', array('size' => '64', 'readonly' => 'readonly')));
        // $mform->setDefault('verifymode', 'Passport Number');
        // $mform->setType('verifymode', PARAM_TEXT);
        // $mform->addRule('verifymode', null, 'required', null, 'client');

        $mform->addElement('text', 'verifycode', get_string('verifycode', 'mod_certifyme'), array('size' => '64'));
        $mform->setType('verifycode', PARAM_ALPHANUM);
        $mform->addRule('verifycode', null, 'required', null, 'client');
        $mform->addRule('verifycode', get_string('maximumchars', '', 64), 'maxlength', 64, 'client');

        // Load final quiz choices.
        $quizchoices = array('' => 'Select a Quiz');
        if ($quizes = $DB->get_records_select('quiz', 'course = :course_id', array('course_id' => $course->id), '', 'id, name')) {
            foreach ($quizes as $quiz) {
                $quizchoices[$quiz->id] = $quiz->name;
            }
        }
        $mform->addElement('header', 'gradeissue', get_string('gradeissueheader', 'certifyme'));
        $mform->addElement('select', 'finalquiz', get_string('finalquiz', 'certifyme'), $quizchoices);
        $mform->addElement('text', 'passinggrade', get_string('passinggrade', 'certifyme'));
        $mform->setType('passinggrade', PARAM_INT);
        $mform->setDefault('passinggrade', 70);

        $mform->addElement('header', 'completionissue', get_string('completionissueheader', 'certifyme'));
        $mform->addElement('checkbox', 'completionactivities', get_string('completionissuecheckbox', 'certifyme'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
