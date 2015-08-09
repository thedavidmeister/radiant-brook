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
     * @param mixed  $value
     *   Any value that may or may not be null.
     *
     * @param string $message
     *
     * @return mixed $value
     */
    public static function notNull($value, $message = '%s is not set.')
    {
        return (null !== $value) ? $value : self::fail($message, $value);
    }

    /**
     * Ensures that a value is empty.
     *
     * @param  mixed  $value
     *   The value to ensure is empty.
     *
     * @param  string $message
     *   The exception message to throw when value is not empty.
     *
     * @return mixed  $value
     */
    public static function isEmpty($value, $message = '%s is not empty.')
    {
        return empty($value) ? $value : self::fail($message, $value);
    }

    /**
     * Ensures that a value is not empty.
     *
     * @param mixed  $value
     *   The value to ensure is not empty.
     *
     * @param string $message
     *   The exception message to throw when value is empty.
     *
     * @return mixed $value
     */
    public static function notEmpty($value, $message = '%s is empty.')
    {
        return !empty($value) ? $value : self::fail($message, $value);
    }

    /**
     * Ensures that a value is an integer.
     *
     * FILTER_VALIDATE_INT is used. If the value is an integer string, it will
     * be cast to an integer primitive.
     *
     * @param mixed  $value
     *   The value to ensure is an integer.
     *
     * @param string $message
     *   The exception message to throw if value is not an integer.
     *
     * @return int $value
     */
    public static function isInt($value, $message = '%s is not an int.')
    {
        // Cast the value to an int, if it's int-y.
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) ? (int) $value : self::fail($message, $value);
    }

    /**
     * Ensures that a value is a float.
     *
     * Value must be numeric. If it is, it will be cast to a float, otherwise,
     * fail.
     *
     * @param number $value
     *   The value to ensure is a float.
     *
     * @param  string  $message [description]
     * @return boolean          [description]
     */
    public static function isFloat($value, $message = '%s is not numeric.')
    {
        // Cast the value to a float, if it's numeric.
        return is_numeric($value) ? (float) $value : self::fail($message, $value);
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
     * @param  string $message
     *   The message to throw when $value is out of bounds.
     *
     * @return number $value
     */
    public static function inRange($value, $boundOne, $boundTwo, $message = '%s is not in the range of %s and %s.')
    {
        $min = min([$boundOne, $boundTwo]);
        $max = max([$boundOne, $boundTwo]);

        return ($min <= $value && $value <= $max) ? $value : self::fail($message, $value, $min, $max);
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
     * @param string $message
     *   The message to throw when $value is not an instance of $class.
     *
     * @return object
     *   $value
     */
    public static function isInstanceOf($value, $class, $message = '%s is not an instance of %s.')
    {
        return ($value instanceof $class) ? $value : self::fail($message, $value, $class);
    }

    /**
     * Ensures that a value is a string.
     *
     * @param string $value
     *   The value to ensure is a string.
     *
     * @param string $message
     *   The message to throw when $value is not a string.
     *
     * @return value
     */
    public static function isString($value, $message = '%s is not a string.')
    {
        return (is_string($value)) ? $value : self::fail($message, $value);
    }

    /**
     * Ensures that a value is a valid PHP variable name.
     *
     * @param string $value
     *   The string to check.
     *
     * @param string $message
     *   The message to throw when $value is not a valid variable name.
     *
     * @return $value
     */
    public static function isValidVariableName($value, $message = '%s is not a valid variable name.')
    {
        $value = self::isString($value);

        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $value) ? $value : self::fail($message, $value);
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
