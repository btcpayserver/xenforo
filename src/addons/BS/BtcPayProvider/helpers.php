<?php

namespace BS\BtcPayProvider\Helpers;

function data_get(array $arr, mixed $key, mixed $default = null): mixed
{
    return $arr[$key] ?? $default;
}
