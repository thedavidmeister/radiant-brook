<?php

namespace AppBundle;

/**
 * Ensure various post and preconditions in a convenient and consistent way.
 */
final class Ensure
{

    /**
     * No instantiation allowed.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // Do nothing.
    }

    /**
     * Ensure that a value is boolean-y.
     *
     * Boolean-y values follow PHP rules, 1/0, "yes"/"no", "true"/"false" and
     * true/false are all boolean-y. Everything else is not.
     *
     * @param boolean-y $value
     *   The value that may or may not be boolean-y.
     *
     * @return boolean-y $value
     */
    public static function isBooleany($value)
    {
        return null !== filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ? $value : fail('%s is not a boolean.');
    }

    /**
     * Ensure that a value is not null.
     *
     * @param mixed $value
     *   Any value that may or may not be null.
     *
     * @return mixed $value
     */
    public static function notNull($value)
    {
        return (null !== $value) ? $value : self::fail('%s is not set.', $value);
    }

    /**
     * Ensures that a value is empty.
     *
     * @param mixed $value
     *   The value to ensure is empty.
     *
     * @return mixed $value
     */
    public static function isEmpty($value)
    {
        return empty($value) ? $value : self::fail('%s is not empty.', $value);
    }

    /**
     * Ensures that a value is not empty.
     *
     * @param mixed $value
     *   The value to ensure is not empty.
     *
     * @return mixed $value
     */
    public static function notEmpty($value)
    {
        return !empty($value) ? $value : self::fail('%s is empty.', $value);
    }

    /**
     * Ensures that a value is numeric.
     *
     * @param number $value
     *   The number to check.
     *
     * @return boolean
     */
    public static function isNumeric($value)
    {
        return is_numeric($value) ? $value : self::fail('%s is not numeric.', $value);
    }

    /**
     * Ensures that a value is int-y.
     *
     * Uses filter_var and is_numeric under the hood.
     *
     * Things that are int-y:
     *   - Strings like '1' and '-1'
     *   - Floats like 1.0 and -1.0
     *   - Numbers like 1 and -1
     *
     * Things that are not int-y:
     *   - Booleans
     *   - Strings like 1.1
     *   - Floats like 1.1
     *   - Other data
     *
     * @param inty $value
     *   Some inty value. An exception is thrown for non-inty values.
     *
     * @return inty $value
     *   The value if it is inty.
     */
    public static function isInt($value)
    {
        $message = '%s is not an int.';

        // Early return obvious integers.
        if (is_int($value)) {
            return $value;
        }

        // At the least, $value must be numeric.
        if (is_numeric($value)) {
            // If filter_var thinks this is an int, then it is.
            if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                return $value;
            } else {
                // If it doesn't, it may well be a string in exponential
                // notation.
                // If we examine the value as a string we can test certain
                // patterns.
                $stringValue = (string) $value;

                // Positive exponents in scientific notation are always an integer.
                // Negative exponents in scientific notation are never an integer.
                // e-0, e-00, etc... will all be normalized by (string).
                if (stripos($stringValue, 'e') !== false) {
                    return (stripos($stringValue, 'e-') === false) ? $value : self::fail($message, $value);
                } else {
                    self::fail($message, $value);
                }
            }
        } else {
            self::fail($message, $value);
        }
    }

    /**
     * Ensures that the first value passed is strictly less than the second.
     *
     * @param number $small
     *   The number that must be less than $big.
     *
     * @param number $big
     *   The number that must be greater than $small.
     *
     * @return number $small
     *   The smaller number is returned.
     */
    public static function lessThan($small, $big)
    {
        return ($small < $big) ? $small : self::fail('%s is not less than %s.', $small, $big);
    }

    /**
     * Ensures that the first value passed is less or equal to the second.
     *
     * @param number $small
     *   The number that must be less than or equal to $big.
     *
     * @param number $big
     *   The number that must be greater than or equal to $small.
     *
     * @return number $small
     *   The smaller number is returned.
     */
    public static function lessThanEqual($small, $big)
    {
        return ($small <= $big) ? $small : self::fail('%s is not less than or equal to %s.', $small, $big);
    }

    /**
     * Ensures that the first value passed is equal to the second.
     *
     * Checks equality with ==.
     *
     * @param mixed $left
     *
     * @param mixed $right
     *
     * @return mixed $left
     *   Returns the first argument.
     */
    public static function equal($left, $right)
    {
        return ($left == $right) ? $left : self::fail('%s is not equal to %s.', $left, $right);
    }

    /**
     * Ensures that the first value passed is identical to the second.
     *
     * Checks equality with ===.
     *
     * @see notIdentical()
     *
     * @param mixed $left
     *
     * @param mixed $right
     *
     * @return mixed $left
     *   Returns the first argument.
     */
    public static function identical($left, $right)
    {
        return ($left === $right) ? $left : self::fail('%s is not identical to %s.', $left, $right);
    }

    /**
     * Ensures that the first value passed is not identical to the second.
     *
     * Checks equality with !==.
     *
     * @see identical()
     *
     * @param mixed $left
     *
     * @param mixed $right
     *
     * @return mixed $left
     *   Returns the first argument.
     */
    public static function notIdentical($left, $right)
    {
        return ($left !== $right) ? $left : self::fail('%s is identical to %s', $left, $right);
    }

    /**
     * Ensures that the first value is greater than or equal to the second.
     *
     * @param number $big
     *   The number that must be greater than or equal to $small.
     *
     * @param number $small
     *   The number that must be less than or equal to $big.
     *
     * @return number $big
     *   The larger number is returned.
     */
    public static function greaterThanEqual($big, $small)
    {
        return ($big >= $small) ? $big : self::fail('%s is not greater than or equal to %s.', $big, $small);
    }

    /**
     * Ensures that the first value passed is strictly greater than the second.
     *
     * @param number $big
     *   The number that must be greater than $small.
     *
     * @param number $small
     *   The number that must be less than $big.
     *
     * @return number $big
     *   The larger number is returned.
     */
    public static function greaterThan($big, $small)
    {
        return ($big > $small) ? $big : self::fail('%s is not greater than %s.', $big, $small);
    }

    /**
     * Ensures that a numerical value is within a given range.
     *
     * The bounds given can both be either an upper or lower bound, only the
     * overall range is important.
     *
     * @param  number $value
     *   The value to ensure is in bounds.
     *
     * @param  number $boundOne
     *   The first bound on the range.
     *
     * @param  number $boundTwo
     *   The second bound on the range.
     *
     * @return number $value
     */
    public static function inRange($value, $boundOne, $boundTwo)
    {
        $min = min([$boundOne, $boundTwo]);
        $max = max([$boundOne, $boundTwo]);

        return ($min <= $value && $value <= $max) ? $value : self::fail('%s is not in the range of %s and %s.', $value, $min, $max);
    }

    /**
     * Ensures that a value is an instance of a given class.
     *
     * @param object $value
     *   An object with a class to test.
     *
     * @param string $class
     *   The expected class of the object.
     *
     * @return object
     *   $value
     */
    public static function isInstanceOf($value, $class)
    {
        return ($value instanceof $class) ? $value : self::fail('%s is not an instance of %s.', $value, $class);
    }

    /**
     * Ensures that a value is a string.
     *
     * @param string $value
     *   The value to ensure is a string.
     *
     * @return value
     */
    public static function isString($value)
    {
        return is_string($value) ? $value : self::fail('%s is not a string.', $value);
    }

    /**
     * Ensures that a value is a valid PHP variable name.
     *
     * @param string $value
     *   The string to check.
     *
     * @return $value
     */
    public static function isValidVariableName($value)
    {
        $value = self::isString($value);

        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $value) ? $value : self::fail('%s is not a valid variable name.', $value);
    }

    /**
     * Throws an exception because an ensure test failed.
     *
     * @param string $message
     *   The message to pass to vsprintf then throw as an exception. Use %s for
     *   substitutions as all $args will be JSON encoded before substitution.
     *
     * @param splat  $args
     *   Any args to JSON encode and pass into vsprintf. This should include the
     *   original value.
     *
     * @return void
     */
    public static function fail($message, ...$args)
    {
        // Convert the value and all extra args into JSON for legibility in the
        // exception message.
        $jsonArgs = array_map('json_encode', $args);

        throw new \Exception(vsprintf($message, $jsonArgs));
    }
}
