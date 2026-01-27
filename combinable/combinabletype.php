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
 * Defines the hooks necessary to make the algebra question type combinable
 *
 * @package   qtype_algebra
 * @copyright  2019 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/algebra/parser.php');

/**
 * Class for combined algebra types.
 *
 * @package   qtype_algebra
 * @copyright  2019 Jean-Michel Vedrine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_combinable_type_algebra extends qtype_combined_combinable_type_base {
    /**
     * @var string
     */
    protected $identifier = 'algebra';

    /**
     * Extra questoon properties.
     *
     * @return array
     */
    protected function extra_question_properties() {
        return ['answerprefix' => '', 'allowedfuncs' => ['all' => 1]];
    }

    /**
     * Extra answer properties.
     *
     * @return array
     */
    protected function extra_answer_properties() {
        return ['fraction' => '1', 'feedback' => ['text' => '', 'format' => FORMAT_PLAIN]];
    }

    /**
     * Subq form fragment question option fields.
     *
     * @return null[]
     */
    public function subq_form_fragment_question_option_fields() {
        return ['compareby' => null,
                     'nchecks' => null,
                     'disallow' => null,
                     'allowedfuncs' => null];
    }
}
