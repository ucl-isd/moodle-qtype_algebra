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
 * Class representing a divide operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the divide
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_divide extends qtype_algebra_parser_term {
    // Static class properties.
    /** Number of arguments */
    const NARGS = 2;
    /**
     * @var array|string[]
     */
    private static array $formats0;
    /** @var array */
    private static $formats = [
        'str' => '%s/%s',
        'tex' => '\\frac{%s}{%s}',
    ];

    /**
     * Constructs an instance of a divide operator term.
     *
     * This function initializes an instance of a divide operator term using the string which
     * matches the divide operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param string $text string matching the term's regular expression
     */
    public function __construct($text) {
        parent::__construct(self::NARGS, self::$formats, $text);
    }

    /**
     * Evaluates the divide operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the divide
     * operation. The method evaluates the two arguments of the term and then simply divides
     * them to get the return value.
     *
     * @param array $params array of values keyed by variable name
     * @return numeric the numerical value of the term given the provided values for the variables
     */
    public function evaluate($params) {
        $this->check_arguments();
        // Get the value we are trying to divide by.
        $divby = $this->arguments[1]->evaluate($params);
        // Check to see if this is zero.
        if ($divby == 0) {
            // Check the sign of the other argument and use to determine whether we return
            // plus or minus infinity.
            return INF * $this->arguments[0]->evaluate($params);
        } else {
            return $this->arguments[0]->evaluate($params) / $divby;
        }
    }
}
