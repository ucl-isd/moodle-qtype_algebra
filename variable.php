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
 * algebra answer question definition class.
 *
 * @package    qtype_algebra
 * @copyright  Roger Moore <rwmoore@ualberta.ca> M.Opitz <m.opitz@ucl.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/type/algebra/questiontype.php');
require_once($CFG->dirroot . '/question/type/algebra/parser.php');

/**
 * Class to represent an algebra question variable
 *
 * loaded from the qtype_algebra_variables table in the database.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_variable {
    /** @var int the answer id. */
    public $id;

    /** @var string the name. */
    public $name;

    /** @var string minimum value. */
    public $min = '-';

    /** @var string maximum value. */
    public $max = '-';

    /**
     * Constructor.
     *
     * @param int $id the variable.
     * @param string $name the name.
     * @param string $min the minimum value.
     * @param string $max value.
     */
    public function __construct($id, $name, $min, $max) {
        $this->id = $id;
        $this->name = $name;
        $this->min = $min;
        $this->max = $max;
    }
}
