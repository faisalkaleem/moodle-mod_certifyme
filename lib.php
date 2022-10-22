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
 * Certificate module core interaction API
 *
 * @package    mod_certifyme
 * @author     Faisal Kaleeem <faisal@wizcoders.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/certifyme/locallib.php');

/**
 * Add certificate instance.
 *
 * @param stdObject $post
 * @return array $certificate new certificate object
 */
function certifyme_add_instance($post) {
    global $DB;

    // Save record.
    $dbrecord = new stdClass();
    $dbrecord->name = $post->name;
    $dbrecord->course = $post->course;
    $dbrecord->templateid = $post->templateid;
    $dbrecord->text = $post->text;
    $dbrecord->licensenumber = $post->licensenumber;
    $dbrecord->verifymode = 'Passport Number';
    $dbrecord->verifycode = $post->verifycode;
    $dbrecord->finalquiz = $post->finalquiz;
    $dbrecord->passinggrade = $post->passinggrade;
    $dbrecord->completionactivities = isset($post->completionactivities) ? $post->completionactivities : null;
    $dbrecord->timecreated = time();

    return $DB->insert_record('certifyme', $dbrecord);
}

/**
 * Update certificate instance.
 *
 * @param stdClass $post
 * @return stdClass $certificate updated
 */
function certifyme_update_instance($post) {
    // To update your certificate details, go to certifyme.com.
    global $DB;

    $dbrecord = new stdClass();
    $dbrecord->id = $post->instance;
    $dbrecord->name = $post->name;
    $dbrecord->templateid = $post->templateid;
    $dbrecord->text = $post->text;
    $dbrecord->licensenumber = $post->licensenumber;
    $dbrecord->verifycode = $post->verifycode;
    $dbrecord->finalquiz = $post->finalquiz;
    $dbrecord->passinggrade = $post->passinggrade;
    $dbrecord->completionactivities = isset($post->completionactivities) ? $post->completionactivities : null;

    return $DB->update_record('certifyme', $dbrecord);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance.
 *
 * @param int $id
 * @return bool true if successful
 */
function certifyme_delete_instance($id) {
    global $DB;

    // Ensure the certificate exists.
    if (!$certificate = $DB->get_record('certifyme', array('id' => $id))) {
        return false;
    }

    return $DB->delete_records('certifyme', array('id' => $id));
}

/**
 * Supported feature list
 *
 * @uses FEATURE_MOD_INTRO
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function certifyme_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return false;
        default:
            return null;
    }
}
