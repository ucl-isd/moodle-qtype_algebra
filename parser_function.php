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
 * Class representing a function in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the function's
 * syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_function extends qtype_algebra_parser_term {
    /**
     * Constructs an instance of a function term.
     *
     * This function initializes an instance of a function term using the string which
     * matches the name of a function.
     *
     * @param string $text string matching the function's regular expression
     */
    public function __construct($text) {
        if (!function_exists($text) && !array_key_exists($text, self::$fnmap)) {
            throw new parser_exception(get_string('undefinedfunction', 'qtype_algebra', $text));
        }
        $formats = ['str' => '%s' . $text . '%s'];
        if (array_key_exists($text, self::$texmap)) {
            $formats['tex'] = '%s' . self::$texmap[$text] . ' %s';
        } else {
            $formats['tex'] = '%s\\' . $text . ' %s';
        }
        $this->sign = '';
        parent::__construct(self::NARGS, $formats, $text);
    }

    /**
     * Sets this function to be negative.
     *
     * This method will convert the function into a negative one. It is called when
     * the parser finds a subtraction operator in front of the function which does
     * not have a variable or another number preceding it e.g. 3*-sin(x)
     */
    public function set_negative() {
        // Set the sign to be a '-'.
        $this->sign = '-';
    }

    /**
     * Sets the arguments of the term to the values in the given array.
     *
     * The code here overrides the base class's method. The code uses this method to actually
     * set the arguments in the given array but a second stage to insert brackets around the
     * function's argument is required.
     *
     * @param array $args array to set the arguments of the term to
     */
    public function set_arguments($args) {
        if (count($args) != $this->nargs) {
            throw new parser_exception(get_string('badfuncargs', 'qtype_algebra', $this->value));
        }
        if (!is_a($args[0], 'qtype_algebra_parser_bracket')) {
            // Check to see if this function requires a special bracket.
            if (in_array($this->value, self::$bracketmap)) {
                $b = new qtype_algebra_parser_bracket('<');
            } else {
                // Does not require special brackets so create normal ones.
                $b = new qtype_algebra_parser_bracket('(');
            }
            $b->set_arguments($args);
            $this->arguments = [$b];
        } else {
            // First term already a bracket.
            // Check to see if we need a special bracket.
            if (in_array($this->value, self::$bracketmap)) {
                // Make the bracket special.
                $args[0]->make_special();
            }
            // Set the arguments to the given type.
            $this->arguments = $args;
        }
    }

    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * The string of the function depends solely on the function argument and the sign.
     * The name has already been coded in at construction time.
     *
     * @param object $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    public function print_args($method) {
        // First ensure that there are the correct number of arguments.
        $this->check_arguments();
        return [$this->sign, $this->arguments[0]->$method()];
    }

    /**
     * Evaluates the function numerically.
     *
     * Overrides the base class method to simply return the numerical value of the function.
     * Each function name is first checked against an internal map to determine the corresponding
     * PHP math function to call. If the function is not in the map it is assumed to already be
     * the correct name for a PHP math function.
     *
     * @param array $params array of values keyed by variable name
     * @return numeric the numerical value of the term given the provided values for the variables
     */
    public function evaluate($params) {
        // First ensure that there are the correct number of arguments.
        $this->check_arguments();
        // Get the correct sign to multiply the value by.
        if ($this->sign == '-') {
            $mult = -1;
        } else {
            $mult = 1;
        }
        // Check to see if there is an entry to map the function name to a PHP function.
        if (array_key_exists($this->value, self::$fnmap)) {
            $func = self::$fnmap[$this->value];
            return $mult * $func($this->arguments[0]->evaluate($params));
        } else {
            // No map entry so the function name must already be a PHP function...
            $tmp = $this->value;
            return $mult * $tmp($this->arguments[0]->evaluate($params));
        }
    }

    /**
     * Checks to see if this function is equal to another term.
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
    const NARGS = 1;
    /** @var array */
    public static $fnmap = ['ln' => 'log',
                                  'log' => 'log10',
                                  ];
    /** @var array */
    public static $texmap = ['asin' => '\\sin^{-1}',
                                  'acos' => '\\cos^{-1}',
                                  'atan' => '\\tan^{-1}',
                                  'sqrt' => '\\sqrt',
                                  ];
    // List of functions requiring special brackets.
    /** @var array */
    public static $bracketmap = ['sqrt',
                                       ];
}
