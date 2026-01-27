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
 * Parser code for the Moodle Algebra question type Moodle algebra question type class
 *
 * @package    qtype_algebra
 * @copyright  Roger Moore <rwmoore 'at' ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class representing a null, or empty, term.
 *
 * This is the type of term returned when the parser is given an empty string to parse.
 * It takes no arguments and will never be found in a parser tree. This term is solely
 * to give a valid return type for an empty string condition and so avoids the need to
 * throw an exception in such cases.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_nullterm extends qtype_algebra_parser_term {
    /** @var The TeX multiply operator. */
    public $id;
    /**
     * Constructs an instance of a null term.
     *
     * Initializes a null term class. Since this class represents nothing no special
     * initialization is required and no arguments are needed.
     */
    public function __construct() {
        parent::__construct(self::NARGS, self::$formats, '');
    }

    /**
     * Returns the array of arguments needed to convert this class into a string.
     *
     * Since this class is represented by an empty string which has no formatting fields
     * we override the base class method to return an empty array.
     *
     * @param object $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    public function print_args($method) {
        return [];
    }

    /**
     * Evaluates the term numerically.
     *
     * Since this is an empty term we define the evaluation as zero regardless of the parameters.
     *
     * @param array $params array of the variable values to use
     * @return float|int|string
     */
    public function evaluate($params) {
        // Return something which is not a number.
        return acos(2.0);
    }

    // Static class properties.

    /** Number of arguments */
    const NARGS = 0;
    /** @var array */
    private static $formats = ['str' => '', 'tex' => ''];
}
