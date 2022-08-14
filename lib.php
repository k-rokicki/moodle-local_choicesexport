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
 * API of local_choicesexport.
 *
 * @package    local_choicesexport
 * @copyright  2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds link to Choice secondary navigation
 *
 * @param settings_navigation $nav
 * @param context $context
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_choicesexport_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $PAGE;
    if ($context instanceof context_module) {
        if ($PAGE->activityname == 'choice'
            && has_capability('local/choicesexport:readresponses', $context)
            && has_capability('local/choicesexport:downloadresponses', $context)
            && $course = $nav->get('modulesettings')
        ) {
            $url = new moodle_url('/local/choicesexport/index.php', array(
                'id' => $context->instanceid,
                'courseid' => $context->get_course_context()->instanceid)
            );
            $course->add(
                get_string('linkname', 'local_choicesexport'),
                $url
            );
        }
    }
}
