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
 * Class representing a special constant in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the a predefined
 * special constant such as pi or 'e' (from natural logarithms).
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_special extends qtype_algebra_parser_term {
    /**
     * Constructs an instance of a special constant term.
     *
     * This function initializes an instance of a special term using the string which
     * matches the regular expression of a special constant.
     *
     * @param string $text matching a constant's regular expression
     */
    public function __construct($text) {
        parent::__construct(self::NARGS, self::$formats[$text], $text);
        $this->sign = '';
    }

    /**
     * Sets this special to be negative.
     *
     * This method will convert the number into a nagetive one. It is called when
     * the parser finds a subtraction operator in front of the number which does
     * not have a variable or another number preceding it.
     */
    public function set_negative() {
        // Set the sign to be a '-'.
        $this->sign = '-';
    }

    /**
     * Evaluates the special constant numerically.
     *
     * Overrides the base class method to simply return the numerical value of the special
     * constant which is defined by an internal switch based on the constant's name.
     *
     * @param array $params array of values keyed by variable name
     * @return numeric the numerical value of the term given the provided values for the variables
     */
    public function evaluate($params) {
        if ($this->sign == '-') {
            $mult = -1;
        } else {
            $mult = 1;
        }
        switch ($this->value) {
            case 'pi':
                return $mult * pi();
            case 'e':
                return $mult * exp(1);
            default:
                return 0;
        }
    }

    /**
     * Returns the array of arguments needed to convert this special term into a string.
     *
     * The special term generally has a fixed, predefined formatting already hard coded so
     * the only remaining variable is the sign of the term and this is what this method
     * returns.
     *
     * @param object $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    public function print_args($method) {
        return [$this->sign];
    }

    /**
     * Checks to see if this constant is equal to another term.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two variables. Then we check that the two are the same constant.
     *
     * @param object $expr the term to compare to the current one
     * @return bool true if the terms match, false otherwise
     */
    public function equals($expr) {
        // Call the default method first to check type.
        if (parent::equals($expr)) {
            return $this->value == $expr->value && $this->sign == $expr->sign;
        } else {
            return false;
        }
    }

    // Static class properties.
    /** Number of arguments */
    const NARGS = 0;
    /** @var array */
    private static $formats = [
        'pi' => [  'str' => '%spi',
                         'tex' => '%s\\pi'],
        'e' => [  'str' => '%se',
                         'tex' => '%se'],
    ];
}
