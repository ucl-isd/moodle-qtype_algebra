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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/parser_exception.php');
require_once(__DIR__ . '/parser_term.php');
require_once(__DIR__ . '/parser_nullterm.php');
require_once(__DIR__ . '/parser_number.php');
require_once(__DIR__ . '/parser_variable.php');
require_once(__DIR__ . '/parser_power.php');
require_once(__DIR__ . '/parser_divide.php');
require_once(__DIR__ . '/parser_multiply.php');
require_once(__DIR__ . '/parser_add.php');
require_once(__DIR__ . '/parser_subtract.php');
require_once(__DIR__ . '/parser_special.php');
require_once(__DIR__ . '/parser_function.php');
require_once(__DIR__ . '/parser_bracket.php');

/**
 * Helper function which will compare two strings using their length only.
 *
 * This function is intended for use in sorting arrays of strings by their string
 * length. This is used to order arrays for regular expressions so that the longest
 * expressions are checked first.
 * In this version, if both strings have equal length, string order is used. So this
 * version of the sort is stable.
 *
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @param string $a first string to compare
 * @param string $b second string to compare
 * @return numeric -1 if $a is longer than $b,  and +1 if $a is shorter
 */
function qtype_algebra_parser_strlen_sort($a, $b) {
    // Get the two string lengths once so we don't have to repeat the function call.
    $alen = strlen($a);
    $blen = strlen($b);
    // If the two lengths are equal use strings order.
    if ($alen == $blen) {
        return ($a > $b) ? -1 : +1;
    }
    // Otherwise return +1 if a is shorter or -1 if longer.
    return ($alen > $blen) ? -1 : +1;
}

/**
 * The main parser class.
 *
 * This class implements the methods needed to parse an expression. It uses a series of
 * regular expressions to indentify the different terms in the expression and then creates
 * instances of the correct subclass to handle them.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser {
    // Special constants which the parser will understand.
    /** @var array */
    public static $specials = [
        'pi',
        'e',
    ];

    // Functions which the parser will understand. These should all be standard PHP math functions.
    /** @var array */
    public static $functions = ['sqrt',
                                      'ln',
                                      'log',
                                      'cosh',
                                      'sinh',
                                      'sin',
                                      'cos',
                                      'tan',
                                      'asin',
                                      'acos',
                                      'atan',
                                      ];

    // Array to define the priority of the different operations. The parser implements the standard BODMAS priority:
    // brackets, order (power), division, mulitplication, addition, subtraction.
    /** @var array */
    private static $priority = [
        ['qtype_algebra_parser_power'],
        ['qtype_algebra_parser_function'],
        ['qtype_algebra_parser_divide', 'qtype_algebra_parser_multiply'],
        ['qtype_algebra_parser_add', 'qtype_algebra_parser_subtract'],
    ];

    // Regular experssion to match an open bracket.
    /** @var string */
    private static $openb = '/[\{\(\[]/A';
    // Regular experssion to match a close bracket.
    /** @var string */
    private static $closeb = '/[\}\)\]]/A';
    // Regular expression to match a plain float or integer number without exponent.
    /** @var string */
    private static $plainnumber = '(([0-9]+(\.|,)[0-9]*)|([0-9]+)|((\.|,)[0-9]+))';
    // Regular expression to match a float or integer number with an exponent.
    /** @var string */
    private static $expnumber = '(([0-9]+(\.|,)[0-9]*)|([0-9]+)|((\.|,)[0-9]+))E([-+]?\d+)';
    // Array to associate close brackets with the correct open bracket type.
    /** @var array */
    private static $bramap = [')' => '(', ']' => '[', '}' => '{'];
    /**
     * @var array|array[]
     */
    private array $tokens;

    /**
     * Constructor for the main parser class.
     *
     * This constructor initializes the token map of the main parser class. It constructs a map of
     * regular expressions to class types. As it parses a string it uses these regular expressions to
     * find tokens in the input string which are then fed to the corresponding term class for
     * interpretation.
     */
    public function __construct() {
        $this->tokens = [
            ['/(\^|\*\*)/A', 'qtype_algebra_parser_power' ],
            ['/(' . implode('|', self::$functions) . ')/A', 'qtype_algebra_parser_function' ],
            ['/\//A', 'qtype_algebra_parser_divide' ],
            ['/\*/A', 'qtype_algebra_parser_multiply' ],
            ['/\+/A', 'qtype_algebra_parser_add' ],
            ['/-/A', 'qtype_algebra_parser_subtract' ],
            ['/(' . implode('|', self::$specials) . ')/A', 'qtype_algebra_parser_special' ],
            ['/(' . self::$expnumber . '|' . self::$plainnumber . ')/A', 'qtype_algebra_parser_number' ],
            ['/[A-Za-z][A-Za-z0-9_]*/A', 'qtype_algebra_parser_variable' ],
        ];
    }

    /**
     * Parses a given string containing an algebric epxression and returns the corresponding parse tree.
     *
     * This method loops over the string using the regular expressions in the token map to break down the
     * string into tokens. These tokens are arranged into a structured stack, taking account of the
     * bracket structure. Finally then method calls the interpret() method to convert the structured
     * token strings into a fully parsed term structure. The method can optionally be passed a list of
     * variables which are used in the expression. If such a list is passed then the parser will attempt
     * to match the current position in the string with one of these given variables before any other
     * token. When passing a variable list a third parameter allows a choice of whether to allow additional
     * undeclared variables. This defaults to false when a list of variables is passed and is ignored otherwise.
     *
     * @param string $text string containing the expression to parse
     * @param array $variables array containing known variable names
     * @param bool $undecvars whether to allow (true) undeclared variable names
     * @return top term of the parsed expression
     * @throws coding_exception
     * @throws parser_exception
     */
    public function parse($text, $variables = [], $undecvars = false) {
        // Create a regular expression to match the known variables if an array is specified.
        if (!empty($variables)) {
            // Create an empty array to store the list of extra regular expressions to match.
            $reextra = [];
            // Loop over all the variable names we are given.
            foreach ($variables as $var) {
                // Create a temporary variable term using the current name.
                $tmpvar = new qtype_algebra_parser_variable($var);
                // If the variable name has a subscript then create a new regular expression to
                // search for which includes an underscore.
                if (!empty($tmpvar->subscript)) {
                    $reextra[] = $tmpvar->base . '_' . $tmpvar->subscript;
                }
            }
            // Merge the variable name array with the array of extra regular expressions to match.
            $variables = array_merge($variables, $reextra);
            // Sort the array in order of increasing variable length in order to prevent 'x1' matching
            // a variable 'x' before 'x1'. Do this using a helper function, which will compare two
            // strings using their length only, and use this with the usort function.
            usort($variables, 'qtype_algebra_parser_strlen_sort');
            // Generate a single regular expression which will match both all known variables.
            $revar = '/(' . implode('|', $variables) . ')/A';
        } else {
            $revar = '';
        }
        $i = 0;
        // Create an array to store the parse tree.
        $tree = [];
        // Create an array to act as a temporary storage stack. This stack is used to
        // push higher levels of the parse tree as it is assembled from the expression.
        $stack = [];
        // Array used to store the match results from regular expression searches.
        $m = [];
        // Loop over the expression string moving along it using the offset variable $i while
        // there are still characters left to parse.
        while ($i < strlen($text)) {
            // Match any white space at the start of the string and 'remove' it by advancing
            // the pointer by the length of the string matching the regular expression white
            // space pattern.
            if (preg_match('/\s+/A', substr($text, $i), $m)) {
                $i += strlen($m[0]);
                // Return to the start of the loop in case this was white space characters at
                // the end of the string.
                continue;
            }
            // Since we don't have any white space the first thing we look for (top priority)
            // are open brackets.
            if (preg_match(self::$openb, substr($text, $i), $m)) {
                // Check for a non-operator and if one is found assume implicit multiplication.
                if (
                        count($tree) > 0 &&
                        (
                            is_array($tree[count($tree) - 1]) ||
                            (
                                is_object($tree[count($tree) - 1]) &&
                                $tree[count($tree) - 1]->n_args() == 0
                            )
                        )
                ) {
                    // Make the implicit assumption explicit by adding an appropriate
                    // multiplication operator.
                    array_push($tree, new qtype_algebra_parser_multiply('*'));
                }
                // Push the current parse tree onto the stack.
                array_push($stack, $tree);
                // Create a new parse tree starting with a bracket term.
                $tree = [new qtype_algebra_parser_bracket($m[0])];
                // Increment the string pointer by the length of the string that was matched.
                $i += strlen($m[0]);
                // Return to the start of the loop.
                continue;
            }
            // Now see if we have a close bracket here.
            if (preg_match(self::$closeb, substr($text, $i), $m)) {
                // First check that the current parse tree has at least one term.
                if (count($tree) == 0) {
                    throw new parser_exception(get_string('badclosebracket', 'qtype_algebra'));
                }
                // Now check that the current tree started with a bracket.
                if (!is_a($tree[0], 'qtype_algebra_parser_bracket')) {
                    throw new parser_exception(get_string('mismatchedcloseb', 'qtype_algebra'));
                } else if ($tree[0]->value != self::$bramap[$m[0]]) {
                    // Check that the open and close bracket are of the same type.
                    throw new parser_exception(get_string('mismatchedbracket', 'qtype_algebra', $tree[0]->value . $m[0]));
                }
                // Append the current tree to the tree one level up on the stack.
                array_push($stack[count($stack) - 1], $tree);
                // The new tree is the lowest level tree on the stack so we
                // pop the new tree off the stack.
                $tree = array_pop($stack);
                $i += strlen($m[0]);
                continue;
            }
            // If a list of predefined variables was given to the method then check for them here.
            if (!empty($revar) && preg_match($revar, substr($text, $i), $m)) {
                // Check for a zero argument term or brackets preceding the variable and if there is one then
                // add the implicit multiplication operation.
                if (count($tree) > 0 && (is_array($tree[count($tree) - 1]) || $tree[count($tree) - 1]->n_args() == 0)) {
                    array_push($tree, new qtype_algebra_parser_multiply('*'));
                }
                // Increment the string index by the length of the variable's name.
                $i += strlen($m[0]);
                // Push a new variable term onto the parse tree.
                array_push($tree, new qtype_algebra_parser_variable($m[0]));
                continue;
            }
            // Here we have not found any open or close brackets or known variables so we can
            // parse the string for a normal token.
            foreach ($this->tokens as $token) {
                if (preg_match($token[0], substr($text, $i), $m)) {
                    // Check for a variable and throw an exception if undeclared variables are
                    // not allowed and a list of defined variables was passed.
                    if (!empty($revar) && !$undecvars && $token[1] == 'qtype_algebra_parser_variable') {
                        throw new parser_exception(get_string('undeclaredvar', 'qtype_algebra', $m[0]));
                    }
                    // Check for a zero argument term preceding a variable, function or special and then
                    // add the implicit multiplication.
                    if (
                        count($tree) > 0 &&
                        (
                            $token[1] == 'qtype_algebra_parser_variable' ||
                            $token[1] == 'qtype_algebra_parser_function' ||
                            $token[1] == 'qtype_algebra_parser_special'
                        ) &&
                        (
                            is_array($tree[count($tree) - 1]) ||
                            $tree[count($tree) - 1]->n_args() == 0
                        )
                    ) {
                        array_push($tree, new qtype_algebra_parser_multiply('*'));
                    }
                    $i += strlen($m[0]);
                    array_push($tree, new $token[1]($m[0]));
                    continue 2;
                }
            }
            throw new parser_exception(get_string('unknownterm', 'qtype_algebra', substr($text, $i)));
        } // End while loop over tokens.
        // If all the open brackets have been closed then the stack will be empty and the
        // tree will contain the entire parsed expression.
        if (count($stack) > 0) {
            throw new parser_exception(get_string('mismatchedopenb', 'qtype_algebra'));
        }
        return $this->interpret($tree);
    }

    /**
     * Takes a structured token map and converts it into a parsed term structure.
     *
     * This is an internal method of the parser class and is called by the parse()
     * method. It performs the final stage of the parsing process and returns the fully
     * parsed term structure.
     *
     * @param array $tree structured token array
     * @return top term of the fully parsed structure
     */
    public function interpret($tree) {
        // First check to see if we are passed anything at all. If not then simply
        // return a qtype_algebra_parser_nullterm.
        if (count($tree) == 0) {
            return new qtype_algebra_parser_nullterm();
        }
        // Now we check to see if this tree is inside brackets. If so then
        // we remove the bracket object from the tree and store it in a
        // temporary variable. We will then parse the remainder of the tree
        // and make the top level term the bracket's argument if applicable.
        if (is_a($tree[0], 'qtype_algebra_parser_bracket')) {
            $bracket = array_splice($tree, 0, 1);
            $bracket = $bracket[0];
        } else {
            $bracket = '';
        }
        // Next we loop over the tree and look for arrays. These represent
        // brackets inside our tree and so we need to process them first.
        for ($i = 0; $i < count($tree); $i++) {
            // Check for a list type if we find one then replace
            // it with the interpreted term.
            if (is_array($tree[$i])) {
                $tree[$i] = $this->interpret($tree[$i]);
            }
        }
        // The next job is to check the subtraction operations to determine whether they are
        // really subtraction operations or whether they are minus signs for negative numbers.
        $toremove = [];
        for ($i = 0; $i < count($tree); $i++) {
            // Check that this element is an addition or subtraction operator.
            if (is_a($tree[$i], 'qtype_algebra_parser_subtract') || is_a($tree[$i], 'qtype_algebra_parser_add')) {
                // Check whether the precedding argument (if there is one) is a number or
                // a variable. In either case this is a addition/subtraction operation so we continue.
                if (
                    $i > 0 && (is_a($tree[$i - 1], 'qtype_algebra_parser_variable') ||
                        is_a($tree[$i - 1], 'qtype_algebra_parser_number') ||
                        is_a($tree[$i - 1], 'qtype_algebra_parser_bracket'))
                ) {
                    continue;
                } else {
                    // Otherwise we have found a minus sign indicating a positive or negative quantity...
                    // Check that we do have a number following otherwise generate an exception...
                    if ($i == (count($tree) - 1) || !method_exists($tree[$i + 1], 'set_negative')) {
                        throw new parser_exception(get_string('illegalplusminus', 'qtype_algebra'));
                    }
                    // If we have a subtract operation then we need to make the following number negative.
                    if (is_a($tree[$i], 'qtype_algebra_parser_subtract')) {
                        // Set the number to be negative.
                        $tree[$i + 1]->set_negative();
                    }
                    // Add the term to the removal list.
                    $toremove[$i] = 1;
                }
            }
        }
        // Remove the elements from the tree who's keys are found in the removal list.
        $tree = array_diff_key($tree, $toremove);
        // Re-key the tree array so that the keys are sequential.
        $tree = array_values($tree);
        foreach (self::$priority as $ops) {
            $i = 0;
            while ($i < count($tree)) {
                if (in_array(get_class($tree[$i]), $ops)) {
                    if ($tree[$i]->n_args() == 1) {
                        if (($i + 1) < count($tree)) {
                            $tree[$i]->set_arguments(array_splice($tree, $i + 1, 1));
                            $i++;
                            continue;
                        } else {
                            throw new parser_exception(get_string('missingonearg', 'qtype_algebra', $tree[$i]->value));
                        }
                    } else if ($tree[$i]->n_args() == 2) {
                        if ($i > 0 && $i < (count($tree) - 1)) {
                            $tree[$i]->set_arguments([$tree[$i - 1],
                                                    $tree[$i + 1]]);
                            array_splice($tree, $i + 1, 1);
                            array_splice($tree, $i - 1, 1);
                            continue;
                        } else {
                            throw new parser_exception(get_string('missingtwoargs', 'qtype_algebra', $tree[$i]->value));
                        }
                    }
                } else {
                    $i++;
                }
            }
        }
        // If there are no terms in the parse tree then we were passed an empty string
        // in which case we create a null term and return it.
        if (count($tree) == 0) {
            return new qtype_algebra_parser_nullterm();
        } else if (count($tree) != 1) {
            throw new parser_exception(get_string('notopterm', 'qtype_algebra'));
        }
        if ($bracket) {
            $bracket->set_arguments([$tree[0]]);
            return $bracket;
        } else {
            return $tree[0];
        }
    }
}

// Sort static arrays once here by inverse string length.
usort(qtype_algebra_parser_variable::$greek, 'qtype_algebra_parser_strlen_sort');
usort(qtype_algebra_parser::$functions, 'qtype_algebra_parser_strlen_sort');
