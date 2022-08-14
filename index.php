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
 * List all files in a course.
 *
 * @package    local_choicesexport
 * @copyright  2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use local_choicesexport\course_choices;

require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $PAGE;

$id              = required_param('id', PARAM_INT);
$courseid        = required_param('courseid', PARAM_INT);
$action          = optional_param('action', '', PARAM_ALPHAEXT);
$onlyactive      = optional_param('export_onlyactive', 0, PARAM_BOOL);
$columnseparator = optional_param('column_separator', 'comma', PARAM_ALPHA);
$answerseparator = optional_param('answer_separator', 'semicolon', PARAM_ALPHA);
$chosenchoices   = optional_param_array('choice', array(), PARAM_INT);

$url = new moodle_url('/local/choicesexport/index.php', array(
    'id' => $id,
    'courseid' => $courseid
));
$PAGE->set_url($url);

$coursecontext = context_course::instance($courseid);

require_login($courseid);
require_capability('local/choicesexport:readresponses', $coursecontext);
$downloadallowed = has_capability('local/choicesexport:downloadresponses', $coursecontext);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    throw new moodle_exception('invalidcourseid');
}
if (!$cm = get_coursemodule_from_id('choice', $id)) {
    throw new moodle_exception("invalidcoursemodule");
}

$context = context_module::instance($cm->id);
$title = get_string('pluginname', 'local_choicesexport');

$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$PAGE->activityheader->set_attrs([
    'nodescription' => true
]);
$PAGE->navbar->prepend(
    $course->shortname,
    new moodle_url('/course/view.php', array('id' => $course->id)) . '#section-0',
    navigation_node::TYPE_COURSE
);
$PAGE->navbar->prepend(
    $cm->name,
    new moodle_url('/mod/choice/view.php', array('id' => $cm->id)),
    navigation_node::TYPE_COURSE
);

$coursechoices = new course_choices($course);
if ($action === 'download' && $downloadallowed) {
    require_sesskey();
    try {
        $coursechoices->download_choices($onlyactive, $chosenchoices, $columnseparator, $answerseparator);
    } catch (moodle_exception $e) {
        notification::add($e->getMessage(), \core\output\notification::NOTIFY_ERROR);
    }
}

$renderer = $PAGE->get_renderer('local_choicesexport');

echo $OUTPUT->header();
echo $renderer->overview_page($url, $coursechoices, $downloadallowed, $columnseparator, $answerseparator);
echo $OUTPUT->footer();
