<?php

/**
 * Try to make this package compatible with older php versions
 * Add support for used features
 */


// php 7.2
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

