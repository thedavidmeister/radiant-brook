<?php

namespace AppBundle;

use Respect\Validation\Validator as v;

/**
 * Casts variables to another data type, if it makes sense and is not lossy.
 *
 * Lossy or unsafe cast attempts throw exceptions.
 */
final class CastUtil
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
        v::intVal()->check($value);

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
        v::numeric()->check($value);
        $value = (string) $value;

        // No empty strings.
        v::not(v::equals('', true))->check($value);

        return (float) $value;
    }
}
