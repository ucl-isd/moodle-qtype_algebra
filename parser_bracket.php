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
 * Class representing a bracket operation in an algebraic expression.
 *
 * The parser creates an instance of this term when it finds a string matching the bracket
 * operator's syntax. The string which corresponds to the term is passed to the constructor
 * of this subclass. Note that a pair of brackets is treated as a single term. There are no
 * separate open and close bracket operators.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_bracket extends qtype_algebra_parser_term {
    /**
     * The constructor.
     *
     * @param string $text
     */
    public function __construct($text) {
        parent::__construct(self::NARGS, self::$formats[$text], $text);
        $this->sign = '';
        $this->open = $text;
        switch ($this->open) {
            case '(':
                $this->close = ')';
                break;
            case '[':
                $this->close = ']';
                break;
            case '{':
                $this->close = '}';
                break;
            // Special kind of bracket. This behaves as normal brackets for a string but as invisible
            // curly brackets '{}' with LaTeX.
            case '<':
                $this->close = '>';
                break;
        }
    }

    /**
     * Evaluates the bracket operation numerically.
     *
     * Overrides the base class method to simply return the numerical value of the bracket
     * operation. The method evaluates the argument of the term, i.e. what is inside the
     * brackets, and then returns the value.
     *
     * @param array $params array of values keyed by variable name
     * @return int the numerical value of the term given the provided values for the variables
     */
    public function evaluate($params) {
        if ($this->sign == '-') {
            $mult = -1;
        } else {
            $mult = 1;
        }
        if (count($this->arguments) != $this->nargs) {
            return 0;
        }
        return $mult * $this->arguments[0]->evaluate($params);
    }

    /**
     * Set negative.
     *
     * @return void
     */
    public function set_negative() {
        // Set the sign to be a '-'.
        $this->sign = '-';
    }

    /**
     * Set the bracket type to 'special'.
     *
     * The method converts the bracket to the special type. The special type appears as a
     * normal bracket in string mode but produces the invisible curly brackets for LaTeX.
     */
    public function make_special() {
        $this->open = '<';
        $this->close = '>';
        // Call the base class constructor as if this were a new instance of the bracket.
        parent::__construct(self::NARGS, self::$formats['<'], '<');
    }

    // Member variables.
    /** @var string */
    public $open = '(';
    /** @var string */
    public $close = ')';

    // Static class properties.
    /** Number of arguments */
    const NARGS = 1;
    /** @var array */
    private static $formats = [
        '(' => ['str' => '(%s)',
                      'tex' => '\\left( %s \\right)'],
        '[' => ['str' => '[%s]',
                      'tex' => '\\left[ %s \\right]'],
        '{' => ['str' => '{%s}',
                      'tex' => '\\left\\lbrace %s \\right\\rbrace'],
        '<' => ['str' => '(%s)',
                      'tex' => '{%s}'],
    ];
}
