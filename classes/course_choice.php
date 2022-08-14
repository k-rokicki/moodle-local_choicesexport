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

namespace local_choicesexport;

use dml_exception;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Class course_choice
 * @package    local_choicesexport
 * @copyright  2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_choice {
    /**
     * @var stdClass
     */
    protected $choice;

    /**
     * @var int
     */
    protected $courseid = 0;

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $instance = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var false|string
     */
    public $url = false;

    /**
     * @var false|string
     */
    public $editurl = false;

    /**
     * @var false|string
     */
    public $responsesurl = false;

    /**
     * @var array
     */
    public $options = array();

    /**
     * @var array
     */
    public $answers = array();

    /**
     * @var int
     */
    public $numoptions = 0;

    /**
     * @var int
     */
    public $numanswers = 0;

    /**
     * @var string
     */
    public $optionslisthtml = '';

    /**
     * @var string
     */
    public $mostfrequentresponseshtml = '';

    /**
     * @var int
     */
    public $usersresponded = 0;

    /**
     * @var int
     */
    public $responserate = 0;

    /**
     * course_choice constructor.
     * @param stdClass $choice
     * @param int $numenrolledusers
     * @throws dml_exception|moodle_exception
     */
    public function __construct(stdClass $choice, int $numenrolledusers) {
        $this->choice = $choice;
        $this->courseid = $choice->course;
        $this->id = $choice->id;
        $this->instance = $choice->instance;
        $this->name = $choice->name;

        $this->url = $this->get_url();
        $this->editurl = $this->get_edit_url();
        $this->responsesurl = $this->get_responses_url();

        $this->options = $this->get_options();
        $this->answers = $this->get_answers();
        $this->numoptions = count($this->options);
        $this->numanswers = count($this->answers);

        $this->optionslisthtml = implode(array_map(function ($option) {
            return "<li>" . $option->text . "</li>";
        }, $this->options));

        $this->mostfrequentresponseshtml = $this->get_most_frequent_answers_html();

        $this->usersresponded = count(array_unique(array_map(function ($answer) {
            return $answer->userid;
        }, $this->answers)));

        $this->responserate = $numenrolledusers == 0
            ? 0
            : round($this->usersresponded * 100 / $numenrolledusers);
    }

    /**
     * Getter for choice
     * @return stdClass
     */
    public function get_choice() : stdClass {
        return $this->choice;
    }

    /**
     * Get the preview url for a choice.
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_url() : ?moodle_url {
        return new moodle_url('/mod/choice/view.php',
            array('id' => $this->id));
    }

    /**
     * Get the edit url for a choice.
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_edit_url() : ?moodle_url {
        return new moodle_url('/course/modedit.php',
            array('update' => $this->id));
    }

    /**
     * Get the responses url for a choice.
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_responses_url() : ?moodle_url {
        return new moodle_url('/mod/choice/report.php',
            array('id' => $this->id));
    }

    /**
     * Get options for a choice.
     *
     * @return array
     * @throws dml_exception
     */
    protected function get_options(): array {
        global $DB;
        return $DB->get_records("choice_options", array("choiceid" => $this->instance));
    }

    /**
     * Get answers to a choice.
     *
     * @return array
     * @throws dml_exception
     */
    protected function get_answers(): array {
        global $DB;
        return $DB->get_records("choice_answers", array("choiceid" => $this->instance));
    }

    /**
     * Get most frequent options HTML.
     *
     * @return string
     */
    protected function get_most_frequent_answers_html(): string {
        $options = $this->options;
        $answers = $this->answers;
        $maxresponses = 0;
        $options = array_map(function ($option) use (&$maxresponses, $answers) {
            $option->numresponses = count(array_filter($answers, function($answer) use ($option) {
                return $answer->optionid == $option->id;
            }));
            $maxresponses = max($maxresponses, $option->numresponses);
            return $option;
        }, $options);
        if ($maxresponses == 0) {
            return "";
        }
        $maxoptions = array_filter($options, function($option) use ($maxresponses) {
            return $option->numresponses == $maxresponses;
        });
        return implode(array_map(function ($option) {
            return "<li>" . $option->text . " (" . $option->numresponses . ")</li>";
        }, $maxoptions));
    }
}
