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
 * Class representing a multiplication operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the multiplication
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_multiply extends qtype_algebra_parser_term {
    /**
     * Constructs an instance of a multiplication operator term.
     *
     * This function initializes an instance of a multiplication operator term using the string which
     * matches the multiplication operator expression. Since this is simply the character representing
     * the operator it is not used except when producing a string representation of the term.
     *
     * @param string $text string matching the term's regular expression
     */
    public function __construct($text) {
        $this->mformats = [
            '*' => ['str' => '%s*%s',
                'tex' => '%s \\' . get_config('qtype_algebra', 'multiplyoperator') . ' %s',
                ],
            '.' => ['str' => '%s %s',
                'tex' => '%s %s',
                'sage' => '%s*%s',
                ],
        ];
        parent::__construct(self::NARGS, $this->mformats['*'], $text, true);
    }

    /**
     * Sets the arguments of the term to the values in the given array.
     *
     * This method sets the term's arguments to those in the given array.
     *
     * @param array $args array to set the arguments of the term to
     */
    public function set_arguments($args) {
        // First perform default argument setting method. This will generate
        // an error if there is a problem with the number of arguments.
        parent::set_arguments($args);
        // Set the default explicit format.
        $this->formatarray = $this->mformats['*'];
        // Only allow the implicit multiplication if the second argument is either a
        // special, variable, function or bracket and not negative. In all other cases the operator must be
        // explicitly written.
        if (
            is_a($args[1], 'qtype_algebra_parser_bracket') ||
            is_a($args[1], 'qtype_algebra_parser_variable') ||
            is_a($args[1], 'qtype_algebra_parser_special') ||
            is_a($args[1], 'qtype_algebra_parser_function')
        ) {
            if (!method_exists($args[1], 'set_negative') || $args[1]->sign == '') {
                $this->formatarray = $this->mformats['.'];
            }
        }
        // Check for one more special exemption: if the second argument is a power expression
        // then we use the same criteria on the first argument of it.
        if (is_a($args[1], 'qtype_algebra_parser_power')) {
            // Get the arguments from the power term. Note we do not check these since
            // power terms are parsed before multiplication ones and are required to
            // have two arguments.
            $powargs = $args[1]->arguments();
            // Allow the implicit multiplication if the power's first argument is either a
            // special, variable, function or bracket and not negative.
            if (
                is_a($powargs[0], 'qtype_algebra_parser_bracket') ||
                is_a($powargs[0], 'qtype_algebra_parser_variable') ||
                is_a($powargs[0], 'qtype_algebra_parser_special') ||
                is_a($powargs[0], 'qtype_algebra_parser_function')
            ) {
                if (!method_exists($powargs[0], 'set_negative') || $powargs[0]->sign == '') {
                    $this->formatarray = $this->mformats['.'];
                }
            }
        }
    }

    /**
     * Evaluates the multiplication operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the multiplication
     * operation. The method evaluates the two arguments of the term and then simply multiplies
     * them to get the return value.
     *
     * @param array $params array of values keyed by variable name
     * @return numeric the numerical value of the term given the provided values for the variables
     */
    public function evaluate($params) {
        $this->check_arguments();
        return $this->arguments[0]->evaluate($params) * $this->arguments[1]->evaluate($params);
    }

    // Static class properties.
    /** Number of arguments */
    const NARGS = 2;
}
