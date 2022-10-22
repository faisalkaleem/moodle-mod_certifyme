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

function get_quiz_name($quizid) {
    global $DB;
    if ($quizid) {
        $quizname = $DB->get_field_sql("SELECT name FROM {quiz} WHERE id=?", [$quizid]);
    }
    return !empty($quizname) ? $quizname : 'N/A';
}

function certifyme_quiz_submission_handler(\mod_quiz\event\attempt_submitted $event) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/quiz/lib.php');

    $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);

    $quiz = $event->get_record_snapshot('quiz', $attempt->quiz);
    $user = $DB->get_record('user', array('id' => $event->relateduserid));

    if ($certifymerecords = $DB->get_records('certifyme', array('course' => $event->courseid))) {
        foreach ($certifymerecords as $record) {
            // Check for the existence of an activity instance and an auto-issue rule.
            if ($record && ($record->finalquiz || $record->completionactivities)) {
                if ($quiz->id == $record->finalquiz) {
                    $usersgrade = min(( quiz_get_best_grade($quiz, $user->id) / $quiz->grade ) * 100, 100);
                    $gradeishighenough = ($usersgrade >= $record->passinggrade);
                    // Check for pass.
                    if ($gradeishighenough) {
                        $response = trigger_certifyme($record, $user, $usersgrade);
                        $certificateevent = \mod_certifyme\event\certificate_created::create(array(
                                    'objectid' => $record->id,
                                    'context' => context_module::instance($event->contextinstanceid),
                                    'relateduserid' => $event->relateduserid,
                                    'other' => $response->response
                        ));
                        $certificateevent->trigger();
                    }
                }
            }
        }
    }
}

function certifyme_course_completed_handler(\core\event\course_completed $event) {
    global $DB, $CFG;

    $user = $DB->get_record('user', array('id' => $event->relateduserid));
    // Check we have a course record.
    if ($certifymerec = $DB->get_records('certifyme', array('course' => $event->courseid))) {
        mtrace("certify records: " . count($certifymerec));
        foreach ($certifymerec as $record) {
            // Check for the existence of an activity instance and an auto-issue rule.
            if ($record && ($record->completionactivities && $record->completionactivities != 0)) {
                // Load user grade to attach in the credential.
                $usergrade = get_user_grades($record, $user->id);
                $response = trigger_certifyme($record, $user, $usergrade, $event->courseid);
                if (!$response->error) {
                    mtrace("certify response: " . $response->response);
                    $certificateevent = \mod_certifyme\event\certificate_created::create(array(
                                'objectid' => $record->id,
                                'context' => context_module::instance($event->contextinstanceid),
                                'relateduserid' => $event->relateduserid,
                                'other' => $response->response
                    ));
                    $certificateevent->trigger();
                } else {
                    mtrace("curl api error" . print_r($response->response, true));
                }
            } else {
                mtrace("api trigger not occured " . print_r($record, true));
            }
        }
    }
}

function get_user_grades($certifymerec, $userid) {
    global $DB;

    $usergrade = '';
    $gradeitemdb = $DB->get_record('grade_items', array('courseid' => $certifymerec->course, 'itemtype' => 'course'), '*', MUST_EXIST);
    $gradeitem = new \grade_item($gradeitemdb);

    $queryparams = array('gradeitem' => $gradeitemdb->id, 'userid' => $userid);
    $grade = $DB->get_records_select('grade_grades', 'itemid = :gradeitem AND userid = :userid', $queryparams);

    $usergrade = null;

    if ($grade && $grade->finalgrade) {
        // $usergrades[$grade->userid] = grade_format_gradevalue($grade->finalgrade, $gradeitem);
        $usergrade = grade_format_gradevalue($grade->finalgrade, $gradeitem);
    }

    return $usergrade;
}

function trigger_certifyme($certifyrec, $user, $grade, $coursid = null) {
    $config = get_config('mod_certifyme');
    if (empty($config->apiurl) || empty($config->username) || empty($config->password)) {
        return (object) ['error' => true, 'response' => 'Certify plugin not configured properly.'];
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config->apiurl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));
    $data = [
        'name' => (string) fullname($user),
        'template_ID' => (string) $certifyrec->templateid,
        'email' => (string) $user->email,
        'text' => (string) $certifyrec->text,
        'license_number' => (string) $certifyrec->licensenumber,
        'verify_mode' => (string) $certifyrec->verifymode,
        'verify_code' => (string) $certifyrec->verifycode
    ];
    if ($coursid) {
        $data['coursegrade'] = (string) $grade;
    } else {
        $data['quizgrade'] = (string) $grade;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $config->username . ":" . $config->password);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
    $response = curl_exec($ch);
    $error = false;
    // $info = curl_getinfo($ch, CURLINFO_PRIVATE);
    if (curl_errno($ch)) {
        $error = true;
        $response = curl_error($ch);
    }
    curl_close($ch);
    return (object) ['error' => $error, 'response' => $response];
}
