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
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) ? $value : self::fail($value, $message);
    }

    public static function isInstanceOf($value, $class, $message = '%s is not an instance of %s.') {
        return ($value instanceof $class) ? $value : self::fail($value, $class, $message);
    }

    public static function fail($value, $message) {
        throw new \Exception(vsprintf($message, var_export($value, true)));
    }
}
