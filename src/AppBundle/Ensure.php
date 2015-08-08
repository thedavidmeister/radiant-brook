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

    public static function fail($value, $message) {
        throw new \Exception(vsprintf($message, var_export($value, true)));
    }
}
