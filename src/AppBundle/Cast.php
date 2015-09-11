<?php

namespace AppBundle;

use AppBundle\Ensure;
use Respect\Validation\Validator as v;

/**
 * Casts variables to another data type, if it makes sense and is not lossy.
 *
 * Lossy or unsafe cast attempts throw exceptions.
 */
final class Cast
{
    /**
     * Ensures that a value is an integer and casts it.
     *
     * @param mixed $value
     *   The value to ensure is an integer.
     *
     * @return int $value
     */
    public static function toInt($value)
    {
        // Cast the value to an int, if it's int-y.
        v::int()->check($value);

        return (int) $value;
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

    /**
     * Ensures that a value is a boolean.
     *
     * Value must be boolean-y. If it is, it will be cast to a float, otherwise,
     * fail.
     *
     * @param boolean-y $value
     *   The value to ensure is a float.
     *
     * @return bool $value
     */
    public static function toBoolean($value)
    {
        return filter_var(Ensure::isBooleany($value), FILTER_VALIDATE_BOOLEAN);
    }
}
