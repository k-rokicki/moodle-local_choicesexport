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
 * Output rendering for the plugin.
 *
 * @package     local_choicesexport
 * @copyright   2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_choicesexport\output;

use coding_exception;
use dml_exception;
use moodle_exception;
use moodle_url;
use local_choicesexport\course_choices;
use plugin_renderer_base;
use stdClass;

/**
 * Implements the plugin renderer
 *
 * @copyright 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render overview page.
     *
     * @param moodle_url $url
     * @param course_choices $coursechoices
     * @param bool $downloadallowed
     * @param string $columnseparator
     * @param string $answerseparator
     *
     * @return string
     * @throws coding_exception|dml_exception|moodle_exception
     */
    public function overview_page(
        moodle_url $url,
        course_choices $coursechoices,
        bool $downloadallowed,
        string $columnseparator,
        string $answerseparator
    ) : string {
        $templatedata = new stdClass();
        $templatedata->url = $url;
        $templatedata->sesskey = sesskey();
        $templatedata->choices = $coursechoices->get_choices();
        $templatedata->choices_exist = count($templatedata->choices) > 0;
        $templatedata->num_enrolled_users = $coursechoices->get_num_enrolled_users();
        $templatedata->download_allowed = $downloadallowed;
        $templatedata->column_separator = $columnseparator;
        $templatedata->answer_separator = $answerseparator;
        $helpicon = new stdClass();
        $helpicon->text = get_string('export_only_active:help', 'local_choicesexport');
        $helpicon->ltr = true;
        $templatedata->helpicon = $helpicon;
        return $this->render_from_template('local_choicesexport/view', $templatedata);
    }
}
