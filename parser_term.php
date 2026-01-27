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
 * Class which represents a single term in an algebraic expression.
 *
 * A single algebraic term is considered to be either an operation, for example addition,
 * subtraction, raising to a power etc. or something operated on, such as a number or
 * variable. Each type of term implements a subclass of this base class.
 *
 * @package     qtype_algebra
 * @author      Roger Moore <rwmoore 'at' ualberta.ca>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_parser_term {
    // Member variables.
    /** @var string */
    public $value;              // String of the actual term itself.
    /** @var array */
    public $arguments = [];     // Array of arguments in class form.
    /** @var array */
    public $formatarray;       // Array of format strings.
    /** @var int */
    public $nargs;              // Number of arguments for this term.

    /** @var bool */
    public $commutes;
    /** @var string */
    public $sign;
    /** @var string */
    public $base;
    /** @var string */
    public $exp;
    /** @var string */
    public $subscript;
    /** @var array */
    public $mformats;

    /**
     * Constructor for the generic parser term.
     *
     * This method is called by all subclasses to initialize the base class for use.
     * It initializes the number of arguments required, the format strings to use
     * when converting the term in various strng formats, the parser text associated
     * with the term and whether the term is one which commutes.
     *
     * @param int $nargs number of arguments which this type of term requires
     * @param array $formats an array of the format strings for this term keyed by type
     * @param string $text the text from the expression associated with the array
     * @param bool $commutes if set to true then this term commutes (only for 2 argument terms)
     */
    public function __construct($nargs, $formats, $text = '', $commutes = false) {
        $this->value = $text;
        $this->nargs = $nargs;
        $this->formatarray = $formats;
        $this->commutes = $commutes;
    }

    /**
     * Generates the list of arguments needed when converting the term into a string.
     *
     * This method returns an array with the arguments needed when converting the term
     * into a string. The arrys can then be used with a format string to generate the
     * string representation. The method is recursive because it needs to convert the
     * arguments of the term into strings and so it will walk down the parse tree.
     *
     * @param object $method name of method to call to convert arguments into strings
     * @return array of the arguments that, with a format string, can be passed to sprintf
     */
    public function print_args($method) {
        // Create an empty array to store the arguments in.
        $args = [];
        // Handle zero argument terms differently by making the
        // first 'argument' the value of the term itself.
        if ($this->nargs == 0) {
            $args[] = $this->value;
        } else {
            foreach ($this->arguments as $arg) {
                $args[] = $arg->$method();
            }
        }
        // Return the array of arguments.
        return $args;
    }

    /**
     * Produces a 'prettified' string of the expression using the standard input syntax.
     *
     * This method will use the print_args() method to convert the term and all its
     * arguments into a string.
     *
     * @return string input syntax format string of the expression
     * @throws parser_exception
     */
    public function str() {
        // First check to see if the class has been given all the arguments.
        $this->check_arguments();
        // Get an array of all the arguments except for the format string.
        $args = $this->print_args('str');
        // Insert the format string at the front of the argument array.
        array_unshift($args, $this->formatarray['str']);
        // Call sprintf using the argument array as the arguments.
        return call_user_func_array('sprintf', $args);
    }

    /**
     * Produces a LaTeX formatted string of the expression.
     *
     * This method will use the print_args() method to convert the term and all its
     * arguments into a LaTeX formatted string. This can then be given to the main Moodle
     * engine, with TeX filter enabled, to produce a graphical representation of the
     * expression.
     *
     * @return string LaTeX format string of the expression
     * @throws parser_exception
     */
    public function tex() {
        // First check to see if the class has been given all the arguments.
        $this->check_arguments();
        // Get an array of all the arguments except for the format string.
        $args = $this->print_args('tex');
        // Insert the format string at the front of the argument array.
        array_unshift($args, $this->formatarray['tex']);
        // Call sprintf using the argument array as the arguments.
        return call_user_func_array('sprintf', $args);
    }

    /**
     * Produces a SAGE formatted string of the expression.
     *
     * This method will use the print_args() method to convert the term and all its
     * arguments into a SAGE formatted string. This can then be passed to SAGE via XML-RPC
     * for symbolic comparisons. The format is very similar to the str() method but
     * has all multiplications made explicit with an asterix.
     *
     * @return object SAGE format string of the expression
     * @throws parser_exception
     */
    public function sage() {
        // First check to see if the class has been given all the arguments.
        $this->check_arguments();
        // Get an array of all the arguments except for the format string.
        $args = $this->print_args('sage');
        // Insert the format string at the front of the argument array. First we
        // check to see if there is a format element called 'sage' if not then we
        // default to the standard string format.
        if (array_key_exists('sage', $this->formatarray)) {
            // Insert the sage format string at the front of the argument array.
            array_unshift($args, $this->formatarray['sage']);
        } else {
            // Insert the normal format string at the front of the argument array.
            array_unshift($args, $this->formatarray['str']);
        }
        // Call sprintf using the argument array as the arguments.
        return call_user_func_array('sprintf', $args);
    }

    /**
     * Returns the list of arguments for the term.
     *
     * This method provides access to the arguments of the term. Although this should
     * ideally be private information it is needed in certain cases to determine
     * how neighbouring terms should display themselves.
     *
     * @return array of arguments for this term
     */
    public function arguments() {
        return $this->arguments;
    }

    /**
     * Sets the arguments of the term to the values in the given array.
     *
     * The code here overrides the base class's method. The code uses this method to actually
     * set the arguments in the given array but a second stage to choose the format of the
     * multiplication operator is required. This is because a 'x' symbol is required when
     * multiplying two numbers. However this can be omitted when multiplying two variables,
     * a variable and a function etc.
     *
     * @param array $args array to set the arguments of the term to
     * @return void
     * @throws coding_exception
     * @throws parser_exception
     */
    public function set_arguments($args) {
        if (count($args) != $this->nargs) {
            throw new parser_exception(get_string('nargswrong', 'qtype_algebra', $this->value));
        }
        $this->arguments = $args;
    }

    /**
     * Checks to ensure that the correct number of arguments are defined.
     *
     * Note that this method just checks for the number or arguments it does not check
     * whether they are valid arguments. If the parameter passed is true (default value)
     * an exception will be thrown if the correct number of arguments are not present. Otherwise
     * the function returns false.
     *
     * @param object $exc if true then an exception will be thrown if the number of arguments is incorrect
     * @return bool true if the correct number of arguments are present, false otherwise
     */
    public function check_arguments($exc = true) {
        $retval = (count($this->arguments) == $this->nargs);
        if ($exc && !$retval) {
            throw new parser_exception(get_string('nargswrong', 'qtype_algebra', $this->value));
        } else {
            return $retval;
        }
    }

    /**
     * Returns a list of all the variable names found in the expression.
     *
     * This method uses the collect() method to walk down the parse tree and collect
     * a list of all the variables which the parser has found in the expression. The names
     * of the variables are then returned.
     *
     * @return array an array containing all the variables names in the expression
     */
    public function get_variables() {
        $list = [];
        $this->collect($list, 'qtype_algebra_parser_variable');
        return array_keys($list);
    }

    /**
     * Returns a list of all the function names found in the expression.
     *
     * This method uses the collect() method to walk down the parse tree and collect
     * a list of all the functions which the parser has found in the expression. The names
     * of the functions are then returned.
     *
     * @return array an array containing all the function names used in the expression
     */
    public function get_functions() {
        $list = [];
        $this->collect($list, 'qtype_algebra_parser_function');
        return array_keys($list);
    }

    /**
     * Collects all the terms of a given type with unique values in the parse tree
     *
     * This method walks recursively down the parse tree by calling itself for the arguments
     * of the current term. The method simply adds the current term to the given imput array
     * using a key set to the value of the term but only if the term matches the selected type.
     * In this way terms only a single entry per term value is return which is the functionality
     * required for the get_variables() and get_functions() methods.
     *
     * @param array $list the array to add the term to if it matches the type
     * @param object $type the name of the type of term to collect.
     * @return array an array containing all the terms of the selected type keyed by their value
     */
    public function collect(&$list, $type) {
        // Add this class to the list if of the correct type.
        if (is_a($this, $type)) {
            // Add a key to the array with the value of the term, this means
            // that multiple terms with the same value will overwrite each
            // other so only one will remain.
            $list[$this->value] = 0;
        }
        // Now loop over all the argument for this term (if any) and check them.
        foreach ($this->arguments as $arg) {
            // Collect terms from the arguments as well.
            $arg->collect($list, $type);
        }
    }

    /**
     * Checks to see if this term is equal to another term ignoring arguments.
     *
     * This method compares the current term to another term. The default method simply compares
     * the class of each term. Terms which require more than this, for example comparing values
     * too, override this method in theor own classes.
     *
     * @param object $term the term to compare to the current one
     * @return bool true if the terms match, false otherwise
     */
    public function equals($term) {
        // Default method just checks to ensure that the Terms are both of the same type.
        return is_a($term, get_class($this));
    }

    /**
     * Compares this term, including any arguments, with another term.
     *
     * This method uses the equals() method to see if the current and given term match.
     * It then looks at any arguments which the two terms have and, recursively, calls their
     * compare methods to determine if they also match. For terms with two arguments which
     * also commute the reverse ordering of the arguments is also tried if the first order
     * fails to match.
     *
     * @param object $expr top level term of an expression to compare against
     * @return bool true if the expressions match, false otherwise
     */
    public function equivalent($expr) {
        // Check that the argument is also a term.
        if (!is_a($expr, 'qtype_algebra_parser_term')) {
            throw new parser_exception(get_string('badequivtype', 'qtype_algebra'));
        }
        // Now check that this term is the same as the given term.
        if (!$this->equals($expr)) {
            // Terms are not equal immediately return false since the two do not match.
            return false;
        }
        // Now compare the arguments recursively...
        switch ($this->nargs) {
            case 0:
                // For zero arguments we already compared this class and found it the same so
                // because there are no arguments to check we are equivalent!
                return true;
            case 1:
                // For one argument we also need to compare the argument of each term.
                return $this->arguments[0]->equivalent($expr->arguments[0]);
            case 2:
                // Now it gets interesting. First we compare the two arguments in the same
                // order and see what we get...
                if (
                    $this->arguments[0]->equivalent($expr->arguments[0]) &&
                        $this->arguments[1]->equivalent($expr->arguments[1])
                ) {
                    // Both arguments are equivalent so we have a match.
                    return true;
                } else if (
                    $this->commutes && $this->arguments[0]->equivalent($expr->arguments[1]) &&
                        $this->arguments[1]->equivalent($expr->arguments[0])
                ) {
                    // Otherwise if the operator commutes we can see if the first argument matches
                    // the second argument and vice versa.
                    return true;
                } else {
                    return false;
                }
            default:
                throw new parser_exception(get_string('morethantwoargs', 'qtype_algebra'));
        }
    }

    /**
     * Returns the number of arguments required by the term.
     *
     * @return int the number of arguments required by the term
     */
    public function n_args() {
        return $this->nargs;
    }

    /**
     * Evaluates the term numerically using the given variable values.
     *
     * The given parameter array is keyed by the name of the variable and the numerical
     * value to assign it is stored in the array value. This method is an abstract one
     * which must be implemented by all subclasses. Failure to do so will generate an
     * exception when the method is called.
     *
     * @param array $params array of values keyed by variable name
     * @return numeric the numerical value of the term given the provided values for the variables
     */
    public function evaluate($params) {
        throw new parser_exception(get_string('noevaluate', 'qtype_algebra', $this->value));
    }

    /**
     * Dumps the term and its arguments to standard out.
     *
     * This method will recursively call the entire parse tree attached to it and produce
     * a nicely formatted dump of the term structure. This is mainly useful for debugging
     * purposes.
     *
     * @param object $params variable values to use if an evaluation is also desired
     * @param string $indent string containing the indentation to use
     * @return void a string indicating the type of the term
     * @throws parser_exception
     */
    public function dump(&$params = [], $indent = '') {
        echo "$indent<Term type '" . get_class($this) . '\' with value \'' . $this->value;
        if (!empty($params)) {
            echo ' eval = \'' . $this->evaluate($params) . "'>\n";
        } else {
            echo "'>\n";
        }
        foreach ($this->arguments as $arg) {
            $arg->dump($params, $indent . '  ');
        }
    }

    /**
     * Special casting operator method to convert the term object to a string.
     *
     * This is primarily a debug method. It is called when the term object is cast into a
     * string, such as happens when echoing or printing it. It simply returns a string
     * indicating the type of the parser term.
     *
     * @return string a string indicating the type of the term
     */
    public function __toString() {
        return '<Algebraic parser term of type \'' . get_class($this) . '\'>';
    }
}
