<?php

namespace AppBundle;

use AppBundle\Ensure;

/**
 * Casts variables to another data type, if it makes sense and is not lossy.
 *
 * Lossy or unsafe cast attempts throw exceptions.
 */
final class Cast
{
    /**
     * Ensures that a value is an integer.
     *
     * FILTER_VALIDATE_INT is used. If the value is an integer string, it will
     * be cast to an integer primitive.
     *
     * @param mixed $value
     *   The value to ensure is an integer.
     *
     * @return int $value
     */
    public static function toInt($value)
    {
        // Cast the value to an int, if it's int-y.
        return (int) Ensure::isInt($value);
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
     * @return float $value
     */
    public static function toFloat($value)
    {
        // Cast the value to a float, if it's numeric.
        return (float) Ensure::isNumeric($value);
    }

    public static function toBoolean($value) {
        return (bool) Ensure::isBooleany($value);
    }
}
