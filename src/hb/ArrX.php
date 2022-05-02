<?php

namespace hb;

/**
 * Helper Functions for Arr/AH/DH/ Classes
 */
class ArrX
{
    /**
     * convert [field => regexp, ...] to callback closure
     */
    static function callbackRE(array $field2re): \Closure {
        return fn (array $r) => Arr::allKV($field2re, fn ($f, $re) => preg_match($re, $r[$f]));
    }

    /**
     * convert [field => regexp, ...] to callback closure
     */
    static function callbackNotRE(array $field2re): \Closure {
        return fn (array $r) => Arr::allKV($field2re, fn ($f, $re) => !preg_match($re, $r[$f]));
    }

    /**
     * convert [field => [...list-of-values]] to callback closure
     */
    static function callbackIsIn(array $field2vals): \Closure {
        $f2v1 = Arr::map($field2vals, fn ($a) => Arr::flip1($a)); // [field => [value => 1]

        return fn ($r) => Arr::allKV($f2v1, fn ($f, $vals1) => $vals1[$r[$f]] ?? 0);
    }

    /**
     * convert [field => [from, to]] to callback closure
     */
    static function callbackIsBetween(array $field2ft): \Closure {
        return fn (array $r) => Arr::allKV($field2ft, fn ($f, $ft) => \hb\between($r[$f], reset($ft), end($ft)));
    }

    /**
     * convert [field => [...list-of-values]] to callback closure
     */
    static function callbackIsNotIn(array $field2vals): \Closure {
        $f2v1 = Arr::map($field2vals, fn ($a) => Arr::flip1($a)); // [field => [value => 1]

        return fn ($r) => Arr::allKV($f2v1, fn ($f, $vals1) => $vals1[$r[$f]] ? 0 : 1);
    }

    /**
     * convert [field => [from, to]] to callback closure
     */
    static function callbackIsNotBetween(array $field2ft): \Closure {
        return fn (array $r) => Arr::allKV($field2ft, fn ($f, $ft) => !\hb\between($r[$f], reset($ft), end($ft)));
    }
}
