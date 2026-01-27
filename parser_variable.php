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
 * Class representing a variable term in an algebraic expression.
 *
 * When the parser finds a text string which does not correspond to a function it creates
 * this type of term and puts the contents of that text into it. Variables with names
 * corresponding to the names of the greek letters are replaced by those letters when
 * rendering the term in LaTeX. Other variables display their first letter with all
 * subsequent letters being lowercase. This reduces confusion when rendering expressions
 * consisting of multiplication of two variables.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_variable extends qtype_algebra_parser_term {
    // Define the list of variable names which will be replaced by greek letters.
    /** @var array */
    public static $greek = [
        'alpha',
        'beta',
        'gamma',
        'delta',
        'epsilon',
        'zeta',
        'eta',
        'theta',
        'iota',
        'kappa',
        'lambda',
        'mu',
        'nu',
        'xi',
        'omicron',
        'pi',
        'rho',
        'sigma',
        'tau',
        'upsilon',
        'phi',
        'chi',
        'psi',
        'omega',
     ];

    /**
     * Constructor for an algebraic term cass representing a variable.
     *
     * Initializes an instance of the variable term subclass. The method is given the text
     * in the expression corresponding to the variable name. This is then parsed to get the
     * variable name which is split into a base and subscript. If the start of the string
     * matches the name of a greek letter this is taken as the base and the remainder as the
     * subscript. Failing that either the subscript must be explicitly specified using an
     * underscore character or the first character is taken as the base.
     *
     * @param string $text text matching the variable name
     */
    public function __construct($text) {
        // Create the array to store the regular expression matches in.
        $m = [];
        // Set the sign of the variable to be empty.
        $this->sign = '';
        // Try to match the text to a greek letter.
        if (preg_match('/(' . implode('|', self::$greek) . ')/A', $text, $m)) {
            // Take the base name of the variable to be the greek letter.
            $this->base = $m[1];
            // Extract the remaining characters for use as the subscript.
            $this->subscript = substr($text, strlen($m[1]));
            // If the first letter of the subscript is an underscore then remove it.
            if (strlen($this->subscript) != 0 && $this->subscript[0] == '_') {
                $this->subscript = substr($this->subscript, 1);
            }
            // Call the base class constructor with the variable text set to the combination of the
            // base name and the subscript without an underscore between them.
            parent::__construct(
                self::NARGS,
                self::$formats['greek'],
                $this->base . $this->subscript
            );
        } else {
            // Otherwise we have a simple multi-letter variable name. Treat the fist letter as the base
            // name and the rest as the subscript.

            // Get the variable's base name.
            $this->base = substr($text, 0, 1);
            // Now set the subscript to the remaining letters.
            $this->subscript = substr($text, 1);
            // If the first letter of the subscript is an underscore then remove it.
            if (strlen($this->subscript) != 0 && $this->subscript[0] == '_') {
                $this->subscript = substr($this->subscript, 1);
            }
            // Call the base class constructor with the variable text set to the combination of the
            // base name and the subscript without an underscore between them.
            parent::__construct(
                self::NARGS,
                self::$formats['std'],
                $this->base . $this->subscript
            );
        }
    }

    /**
     * Sets this variable to be negative.
     *
     * This method will convert the number into a nagetive one. It is called when
     * the parser finds a subtraction operator in front of the number which does
     * not have a variable or another number preceding it.
     *
     * @return void
     */
    public function set_negative() {
        // Set the sign to be a '-'.
        $this->sign = '-';
    }

    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * The string of the variable depends solely on the name and subscript and hence these
     * are the only two arguments returned in the array.
     *
     * @param object $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    public function print_args($method) {
        return [$this->sign, $this->base, $this->subscript];
    }

    /**
     * Evaluates the number numerically.
     *
     * Overrides the base class method to simply return the numerical value of the number the
     * class represents.
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
        if (array_key_exists($this->value, $params)) {
            return $mult * doubleval($params[$this->value]);
        } else {
            // Found an indefined variable. Cannot evaluate numerically so throw exception.
            throw new parser_exception(get_string('undefinedvariable', 'qtype_algebra', $this->value));
        }
    }

    /**
     * Checks to see if this variable is equal to another variable.
     *
     * This is a two step process. First we use the base class equals method to ensure
     * that we are comparing two variables. Then we check that the two are the same variable.
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
        'greek' => ['str' => '%s%s%s',
                          'tex' => '%s\%s_{%s}'],
        'std' => ['str' => '%s%s%s',
                          'tex' => '%s%s_{%s}'],
    ];
}
