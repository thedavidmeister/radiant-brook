<?php

namespace AppBundle;

final class Ensure {

    private function __construct() {
        // Do nothing.
    }

    public static function set($value, $message = '%s is not set.') {
        return isset($value) ? $value : self::fail($value, $message);
    }

    public static function isEmpty($value, $message = '%s is not empty.') {
        return empty($value) ? $value : self::fail($value, $message);
    }

    public static function notEmpty($value, $message = '%s is empty.') {
        return !empty($value) ? $value : self::fail($value, $message);
    }

    public static function isInt($value, $message = '%s is not an int.') {
        // Cast the value to an int, if it's int-y.
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) ? (int) $value : self::fail($value, $message);
    }

    public static function inRange($value, $min, $max, $message = '%s is not in range.') {
        return ($min <= $value && $value <= $max) ? $value : self::fail($value, $message);
    }

    public static function isInstanceOf($value, $class, $message = '%s is not an instance of %s.') {
        return ($value instanceof $class) ? $value : self::fail($value, $class, $message);
    }

    public static function fail($value, $message) {
        throw new \Exception(vsprintf($message, json_encode($value, true)));
    }
}
