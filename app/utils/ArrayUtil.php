<?php

namespace app\utils;

class ArrayUtil
{
    public static function safeGet($arr, $key, $defVal=null) {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }
        return $defVal;
    }

    public static function insert($arr, $pos, $value) {
        array_splice($arr, $pos, 0, $value);
        return $arr;
    }

    public static function sort($array, $key, $sort) {
        $paiKey = array_column($array, $key);
        array_multisort($paiKey, $sort, $array);
        return $array;
    }
}