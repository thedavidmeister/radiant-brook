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
        return is_numeric($value) ? $value : self::fail('%s is not numeric', $value);
    }

    public static function isInt($value)
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) ? $value : self::fail('%s is not an int.', $value);
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
        return (is_string($value)) ? $value : self::fail('%s is not a string.', $value);
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
