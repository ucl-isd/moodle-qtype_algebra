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
 * Class representing a number.
 *
 * All purely numerical quantities will be represented by this type of class. There are
 * two basic types of numbers: non-exponential and exponential. Both types are handled by
 * this single class.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_number extends qtype_algebra_parser_term {
    /**
     * Constructs an instance of a number term.
     *
     * This function initializes an instance of a number term using the string which
     * matches the number's regular expression.
     *
     * @param string $text string matching the number regular expression
     * @throws dml_exception
     */
    public function __construct($text = '') {
        // Unfortunately PHP maths will only support a '.' as a decimal point and will not support
        // ',' as used in Danish, French etc. To allow for this we always convert any commas into
        // decimal points before we parse the string.
        $text = str_replace(',', '.', $text);
        $this->sign = '';
        // Now determine whether this is in exponent form or just a plain number.
        if (preg_match('/([\.0-9]+)E([-+]?\d+)/', $text, $m)) {
            $this->base = $m[1];
            $this->exp = $m[2];
            $eformats = ['str' => '%sE%s',
                            'tex' => '%s \\' . get_config('qtype_algebra', 'multiplyoperator') . '10^{%s}'];
            parent::__construct(self::NARGS, $eformats, $text);
        } else {
            $this->base = $text;
            $this->exp = '';
            parent::__construct(self::NARGS, self::$formats, $text);
        }
    }

    /**
     * Sets this number to be negative.
     *
     * This method will convert the number into a nagetive one. It is called when
     * the parser finds a subtraction operator in front of the number which does
     * not have a variable or another number preceding it.
     *
     * @return void
     */
    public function set_negative() {
        // Prepend a minus sign to both the base and total value strings.
        $this->base = '-' . $this->base;
        $this->value = '-' . $this->value;
        $this->sign = '-';
    }

    /**
     * Checks to see if this number is equal to another number.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two numbers. Then we check that the two have the same value.
     *
     * @param object $expr the term to compare to the current one
     * @return bool true if the terms match, false otherwise
     */
    public function equals($expr) {
        // Call the default method first to check type.
        if (parent::equals($expr)) {
            return (float)$this->value == (float)$expr->value;
        } else {
            return false;
        }
    }

    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * For number terms there are two possible formats: those with an exponent and those
     * without an exponent. This method determines which to use and then pushes the correct
     * arguments into the array which is returned.
     *
     * @param object $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    public function print_args($method) {
        // When displaying the number we need to worry about whether to use a decimal point
        // or a comma depending on the language currently selected/ Do this by replacing the
        // decimal point (which we have to use internally because of the PHP math standard)
        // with the correct string from the language pack.
        $base = str_replace('.', get_string('decimal', 'qtype_algebra'), $this->base);
        // Put the base part of the number into the argument array.
        $args = [$base];
        // Check to see if we have an exponent...
        if ($this->exp) {
            // We do so add it to the argument array as well.
            $args[] = $this->exp;
        }
        // Return the list of arguments.
        return $args;
    }

    /**
     * Evaluates the term numerically.
     *
     * All this method does is return the string representing the number cast as a double
     * precision floating point variable.
     *
     * @param array $params array of the variable values to use
     */
    public function evaluate($params) {
        return doubleval($this->value);
    }

    // Static class properties.
    /** Number of arguments */
    const NARGS = 0;
    /** @var array */
    private static $formats = ['str' => '%s',
                                  'tex' => '%s '];
}
