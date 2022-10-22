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
 * Handles viewing a certificate
 *
 * @package    mod_certifyme
 * @author     Faisal Kaleeem <faisal@wizcoders.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("locallib.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT); // Course Module ID.

$cm = get_coursemodule_from_id('certifyme', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$modcertify = $DB->get_record('certifyme', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/certifyme:manage', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/certifyme/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($modcertify->name));
$PAGE->set_heading(format_string($course->fullname));

// Echo the page.
echo $OUTPUT->header();

$table = new \html_table();
$table->head = [
    get_string('name'),
    get_string('templateid', 'mod_certifyme'),
    get_string('text', 'mod_certifyme'),
    get_string('licensenumber', 'mod_certifyme'),
    get_string('verifymode', 'mod_certifyme'),
    get_string('verifycode', 'mod_certifyme'),
    get_string('finalquiz', 'mod_certifyme'),
    get_string('passinggrade', 'mod_certifyme'),
    get_string('completionactivities', 'mod_certifyme'),
];
$data[] = [
    $modcertify->name,
    $modcertify->templateid,
    $modcertify->text,
    $modcertify->licensenumber,
    $modcertify->verifymode,
    $modcertify->verifycode,
    get_quiz_name($modcertify->finalquiz),
    $modcertify->finalquiz ? $modcertify->passinggrade : 'N/A',
    $modcertify->completionactivities ? 'Yes' : 'N/A'
];
$table->data = $data;

echo html_writer::table($table);

echo $OUTPUT->footer($course);
