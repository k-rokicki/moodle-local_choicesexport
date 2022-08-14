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
 * Internal API of local_choicesexport.
 *
 * @package    local_choicesexport
 * @copyright  2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_choicesexport;

use coding_exception;
use context;
use context_course;
use csv_export_writer;
use csv_import_reader;
use dml_exception;
use grade_helper;
use moodle_exception;
use stdClass;

/**
 * Class course_choices
 * @package local_choicesexport
 */
class course_choices {
    /**
     * @var array
     */
    protected $choices = null;

    /**
     * @var stdClass
     */
    protected $course;

    /**
     * @var context
     */
    protected $coursecontext;

    /**
     * @var int
     */
    protected $numenrolledusers;

    /**
     * course_choices constructor.
     * @param stdClass $course
     */
    public function __construct(stdClass $course) {
        $this->course = $course;
        $this->coursecontext = context_course::instance($course->id);
        $this->numenrolledusers = count_enrolled_users($this->coursecontext);
    }

    /**
     * Getter for numenrolledusers.
     *
     * @return int
     */
    public function get_num_enrolled_users(): int {
        return $this->numenrolledusers;
    }

    /**
     * Retrieve all choices within a course.
     *
     * @return array
     * @throws dml_exception|moodle_exception
     */
    public function get_choices(): ?array {
        if ($this->choices !== null) {
            return $this->choices;
        }

        $choices = $this->get_course_choices();

        $this->choices = $choices;
        return $choices;
    }

    /**
     * Download a CSV file of the choices with the given ids.
     *
     * This function does not return if the file could be created.
     *
     * @param bool $onlyactive         True if including only active enrolments
     * @param array $choiceids         Choice ids
     * @param string $columnseparator  String separating columns
     * @param string $answerseparator  String separating answers inside choice columns
     * @throws moodle_exception
     */
    public function download_choices(bool $onlyactive, array $choiceids, string $columnseparator, string $answerseparator) {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');
        require_once($CFG->dirroot . '/grade/lib.php');

        $choiceids = array_keys($choiceids);
        if (count($choiceids) == 0) {
            throw new moodle_exception('no_choice_selected', 'local_choicesexport');
        }

        $checkedchoices = $this->check_choices($choiceids);

        $strchoices = get_string('modulenameplural', 'choice');
        $shortname = format_string($this->course->shortname, true, array('context' => $this->coursecontext));
        $downloadfilename = clean_filename("$shortname $strchoices");

        $csvexport = new csv_export_writer($columnseparator);
        $csvexport->set_filename($downloadfilename);

        $profilefields = grade_helper::get_user_profile_fields($this->course->id);

        // Print names of all the fields
        $exporttitle = array();
        foreach ($profilefields as $field) {
            $exporttitle[] = $field->fullname;
        }
        if (!$onlyactive) {
            $exporttitle[] = get_string('suspended');
        }
        foreach ($checkedchoices as $choice) {
            $exporttitle[] = $choice->name;
        }
        $csvexport->add_data($exporttitle);

        $users = get_enrolled_users($this->coursecontext, '', 0, 'u.*', null, 0, 0, $onlyactive);

        foreach ($users as $user) {
            $exportdata = array();
            foreach ($profilefields as $field) {
                $fieldvalue = grade_helper::get_user_field_value($user, $field);
                $exportdata[] = $fieldvalue;
            }
            if (!$onlyactive) {
                $issuspended = ($user->suspendedenrolment) ? get_string('yes') : '';
                $exportdata[] = $issuspended;
            }
            foreach ($checkedchoices as $choice) {
                $choiceanswers = array_filter($choice->answers, function ($answer) use ($user, $choice) {
                    return $answer->userid == $user->id && $answer->choiceid == $choice->instance;
                });
                $optionids = array_map(function ($answer) {
                    return $answer->optionid;
                }, $choiceanswers);
                $choiceoptions = array_filter($choice->options, function ($option) use ($optionids) {
                   return in_array($option->id, $optionids);
                });
                $optiontexts = array_map(function ($option) {
                    return $option->text;
                }, $choiceoptions);
                $exportdata[] = implode(csv_import_reader::get_delimiter($answerseparator), $optiontexts);
            }
            $csvexport->add_data($exportdata);
        }

        $csvexport->download_file();
    }

    /**
     * Check given choices whether they are available in the current course.
     *
     * @param array $choiceids ids of choices
     * @return array choices that are available
     * @throws dml_exception|coding_exception|moodle_exception
     */
    protected function check_choices(array $choiceids): array {
        return array_filter($this->get_choices(), function ($choice) use ($choiceids) {
            return in_array($choice->id, $choiceids);
        });
    }

    /**
     * Retrieve all choices within a course.
     *
     * @return array|false
     * @throws dml_exception|moodle_exception
     */
    protected function get_course_choices() {
        $choicelist = array();
        if ($choices = get_coursemodules_in_course('choice', $this->course->id)) {
            foreach ($choices as $choice) {
                $choicelist[] = new course_choice($choice, $this->numenrolledusers);
            }
            return $choicelist;
        }
        return false;
    }
}
