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
 * Upgrade library code for the algebra question type.
 *
 * @package    qtype_algebra
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class for converting attempt data for algebra questions
 *
 * when upgrading attempts to the new question engine.
 *
 * This class is used by the code in question/engine/upgrade/upgradelib.php.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_qe2_attempt_updater extends question_qtype_attempt_updater {
    /**
     * The reight answer.
     *
     * @return void
     */
    public function right_answer() {
        foreach ($this->question->options->answers as $ans) {
            if ($ans->fraction > 0.999) {
                return $ans->answer;
            }
        }
    }

    /**
     * Was answered.
     *
     * @param stdClass $state
     * @return bool
     */
    public function was_answered($state) {
        return !empty($state->answer);
    }

    /**
     * Response summary.
     *
     * @param stdClass $state
     * @return null
     */
    public function response_summary($state) {
        if (!empty($state->answer)) {
            return $state->answer;
        } else {
            return null;
        }
    }

    /**
     * Set 1st step data elements.
     *
     * @param stdClass $state
     * @param stdClass $data
     * @return void
     */
    public function set_first_step_data_elements($state, &$data) {
    }

    /**
     * Supply missing 1st step data.
     *
     * @param stdClass $data
     * @return void
     */
    public function supply_missing_first_step_data(&$data) {
    }

    /**
     * Set data elements for step.
     *
     * @param stdClass $state
     * @param stdClass $data
     * @return void
     */
    public function set_data_elements_for_step($state, &$data) {
        if (!empty($state->answer)) {
            $data['answer'] = $state->answer;
        }
    }
}
