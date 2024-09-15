<?php

namespace BS\BtcPayProvider\Helpers;

class Data
{
    public static function get(array $arr, mixed $key, mixed $default = null): mixed
    {
        return $arr[$key] ?? $default;
    }
}
