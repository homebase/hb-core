<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hb2;

/**
 * Helper Functions for Arr/AH/DH/ Classes
 */
class ArrX
{
    /**
     * convert [field => regexp, ...] to callback closure
     *
     * @param string[] $field2re
     */
    static function callbackRE(array $field2re): \Closure
    {
        return static fn (array $r) => Arr::all($field2re, static fn ($f, $re) => preg_match($re, $r[$f]));
    }

    /**
     * convert [field => regexp, ...] to callback closure
     *
     * @param string[] $field2re
     */
    static function callbackNotRE(array $field2re): \Closure
    {
        return static fn (array $r) => Arr::all($field2re, static fn ($f, $re) => !preg_match($re, $r[$f]));
    }

    /**
     * convert [field => [...list-of-values]] to callback closure
     *
     * @param mixed[] $field2vals
     */
    static function callbackIsIn(array $field2vals): \Closure
    {
        $f2v1 = Arr::map($field2vals, static fn ($a) => Arr::flip1($a)); // [field => [value => 1]

        return static fn ($r) => Arr::all($f2v1, static fn ($f, $vals1) => $vals1[$r[$f]] ?? 0);
    }

    /**
     * convert [field => [...list-of-values]] to callback closure
     *
     * @param mixed[] $field2vals
     */
    static function callbackIsNotIn(array $field2vals): \Closure
    {
        $f2v1 = Arr::map($field2vals, static fn ($a) => Arr::flip1($a)); // [field => [value => 1]

        return static fn ($r) => Arr::all($f2v1, static fn ($f, $vals1) => $vals1[$r[$f]] ? 0 : 1);
    }

    /**
     * convert [field => [from, to]] to callback closure
     *
     * @param array{int, int}[] $field2ft
     */
    static function callbackIsBetween(array $field2ft): \Closure
    {
        return static fn (array $r) => Arr::all($field2ft, static fn ($f, $ft) => \hb2\between($r[$f], reset($ft), end($ft)));
    }

    /**
     * convert [field => [from, to]] to callback closure
     *
     * @param array{int, int}[] $field2ft
     */
    static function callbackIsNotBetween(array $field2ft): \Closure
    {
        return static fn (array $r) => Arr::all($field2ft, static fn ($f, $ft) => !\hb2\between($r[$f], reset($ft), end($ft)));
    }
}
