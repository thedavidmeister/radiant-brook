<?php

namespace AppBundle;

final class Ensure
{

    private function __construct() 
    {
        // Do nothing.
    }

    public static function set($value, $message = '%s is not set.') 
    {
        return isset($value) ? $value : self::fail($message, $value);
    }

    public static function isEmpty($value, $message = '%s is not empty.') 
    {
        return empty($value) ? $value : self::fail($message, $value);
    }

    public static function notEmpty($value, $message = '%s is empty.') 
    {
        return !empty($value) ? $value : self::fail($message, $value);
    }

    public static function isInt($value, $message = '%s is not an int.') 
    {
        // Cast the value to an int, if it's int-y.
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) ? (int) $value : self::fail($message, $value);
    }

    public static function inRange($value, $bound_one, $bound_two, $message = '%s is not in the range of %s and %s.') 
    {
        $min = min([$bound_one, $bound_two]);
        $max = max([$bound_one, $bound_two]);
        if ($min <= $value && $value <= $max) {
            return $value;
        } else {
            self::fail($message, $value, $min, $max);
        }
    }

    public static function isInstanceOf($value, $class, $message = '%s is not an instance of %s.') 
    {
        return ($value instanceof $class) ? $value : self::fail($message, $value, $class);
    }

    public static function fail($message, ...$args) 
    {
        // Convert the value and all extra args into JSON for legibility in the
        // exception message.
        $json_args = array_map(function($arg) { return json_encode($arg, true); 
        }, $args);

        throw new \Exception(vsprintf($message, $json_args));
    }
}
