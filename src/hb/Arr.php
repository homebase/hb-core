<?php

declare(strict_types=1);

// This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core

namespace hb;

/**
 * Array Helper Methods
 *
 * provides own methods and almost all Laravel's Arr class methods
 *
 * - Arr  -  generic Array methods
 * - AH   -  Array of Hashes: PrimaryKey => [Key => Value, ...]  ~= mysql table
 * - DH   -  Deep Hash / key => key => ... => value ~= deep json structure
 * - ADH  -  Array of Deep Hash: PrimaryKey => DH ~= mongodb collection
 *
 * All(almost) methods receive source array as first argument and returns new array as result
 * - No data modification - few (laravel compatibility) exceptions: forget, pull, set, pop, shift
 * - key value order is always: $key, $value
 * - All callbacks can be callback($value) or callback($key, $value)
 * - preserveKeys - keep original keys intact (unless explicitly specified: mapList())
 * - most methods support 'iterable' (generators)
 *
 * Usage hints:
 *   - we highly recommend to use PHP8 named args notation for 2nd+ arguments
 *     ex:
 *       Arr::map($arr, where: ... );
 *       Arr::compare($a, $b, strict:1);
 */
class Arr extends Arr0 {
    /**
     * @see \hb\qw("val1 val2 key3:val3")   >> [0=>val1, 1=>val2, key3=>val3]
     * @see \hb\qk("key1 key2 key3:val3")   >> [key1=>true, key2=>true, key3=>val3]
     */

    /**
     * Check out Arr0 class - all most important methods are there
     *
     * - all
     * - any
     * - map
     * - mapList
     * - each
     * - fold
     * - iter
     * - sum
     * - only
     * - forget
     * - count
     * - groupBy
     * - where
     * - whereNot
     * - while
     */

    /**
     * removes NULLS and "" from array RECURSIVELY
     * we'll keep false and (int)0 and "0" !!!
     *
     * @param array<mixed> $arr
     *
     * @return array<mixed>
     */
    static function cleanUp(array $arr): array {
        foreach ($arr as $k => &$d) {
            if (\is_array($d)) {
                $d = self::cleanUp($d);
            }
            if ('' === $d || null === $d) {
                unset($arr[$k]);
            }
        }

        return $arr;
    }

    /**
     * array_combine
     *
     * @param array<int|string> $keys
     * @param array<mixed>      $values
     *
     * @return array<mixed>
     */
    static function combine(array $keys, array $values): array {
        return array_combine($keys, $values);
    }

    // Divide an array into two arrays. One with keys and the other with values.
    static function divide(array $arr): array {  // [keys, values]
        return [array_keys($arr), array_values($arr)];
    }

    // range() as a generator
    // @test: iterator_to_array(Arr::range(1, 10)) == range(1, 10)
    /**
     * @psalm-return \Generator<int, int, mixed, void>
     */
    static function range(int $start, int $end, int $step = 1): \Generator {
        // generator
        for ($i = $start; $i <= $end; $i += $step) {
            yield $i;
        }
    }

    /**
     * is AssociativeArray
     */
    static function isAssoc(array $arr): bool {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, \count($arr) - 1);
    }

    /**
     * Compare two Assoc Arrays. must have same keys and values. Any level deep
     * - order of keys is irrelevant
     * - NULL value considered as NO KEY!!
     * - type of keys is important: values considered same as long as $a[$k] == $b[$k] or (===)
     *
     * Example: Arr::compare(["a" => 1, "b" => "2", 'd' => null], ["b" => 2, "a" => 1.0, 'f' => null])  == true
     *
     * @param mixed $strict = true : comparison-strictness:  true => strict (===) ; false => non-strict (==)
     */
    static function compare(array $a, array $b, $strict = true): bool {
        foreach ($a as $k => $av) {
            $bv = $b[$k] ?? null;
            if (\is_array($av) && \is_array($bv)) {
                if (self::compare($b, $bv)) {
                    continue;
                }

                return false;
            }
            if (!\is_array($av) && !\is_array($bv)) {
                if ($strict && $av === $bv) {
                    continue;
                }
                if (!$strict && $av == $bv) {
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Split array into two parts: [items_before_condition, items_after]
     *
     * Important - YOU CAN PASS ONLY ONE CONDITION at a time
     *
     * splitAt(cb: callback)     => [items_before_callback_success, remaining_items]
     *        callback: fn($value) | fn($key, $value)
     * splitAt(first: $nn_items)   => [nn_items, remaining_items]
     * splitAt(last: $nn_items)   => [remaining_items, nn_items]
     * splitAt(value:$val)   => [items(value-not-equal), [value-equal ... remaining_items] ]
     * splitAt(key:$key)   => [items(key-not-equal), [key-equal ... remaining_items] ]
     *
     * @see partition, groupBy
     *
     * @param null|mixed $cb
     * @param null|mixed $first
     * @param null|mixed $last
     * @param null|mixed $value
     * @param null|mixed $key
     */
    static function splitAt(array $arr, $cb = null, $first = null, $last = null, $value = null, $key = null): array {
        // error_if(func_num_args() > 2, "splitAt requires exactly two arguments");
        $cb = match (1) {
            $cb !== null => $cb,
            $value !== null => fn ($v) => $v === $value,
            $key !== null => fn ($k, $v) => $k === $key,
        };
        if ($first) { // php-cs fixer cant format function in match well
            $cb = function ($v) use ($first): bool {
                static $cnt = 0;
                $cnt++;

                return $cnt === $first;
            };
        }
        if ($last) {
            return array_reverse(self::splitAt(array_reverse($arr), first: $last));
        }
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        $a = [];  // part 1
        $b = [];  // part 2
        $p = 0;
        foreach ($arr as $k => $v) {
            if ($p) {
                $b[$k] = $v;

                continue;
            }
            if (($np == 1 && $cb($v)) || ($np > 1 && $cb($k, $v))) {
                $b[$k] = $v;
                $p = 1;

                continue;
            }
            $a[$k] = $v;
        }

        return [$a, $b];
    }

    /**
     * Split array into two or more arrays
     *
     * $cb = false      => part 0
     * $cb = true       => part 1
     * $cb = int|string => part $cb
     * $cb = null       => Data is filtered out
     *
     * if $cb is a string it treated as fieldName == group by fieldName
     *
     * if $cb is array - it treated as a list of keys: return [ 0 => keys-not-in-list, 1 => keys-in-list]
     *
     * ex: [$arr_false, $arr_true] = A::partition(range(1,10), fn ($v) => $v > 5);  // split into two groups
     *
     * ex: $groups = A::partition(range(1,10), fn ($v) => $v % 3);  // split into three groups: 0,1,2
     *
     * @param mixed $cb
     */
    static function partition(array $arr, \Closure|string $cb): array { // [false|0, true|1, ...]
        if (\is_array($cb)) { // [keys-not-in-list, keys-in-list]
            $isIn = self::flipTo($cb); // value => 1
            $cb = fn ($k, $v) => $isIn[$k] ?? 0;
        }
        $r = self::groupBy($arr, $cb);
        ksort($r);

        return $r;
    }

    /**
     * Build unique MD5-based hash from contents of an array (keys & values)
     * Array elements can be arrays too
     * default: array order is not important
     *
     * $orderless : array order is not important ::: ['a' => [1,2], 3, 4] == [4, 'a' => [2,1], 3]
     * $orderless = false : depends on array keys order  ::: ['a' => 1, 2, 3] != [3, 'a' => 1, 2]
     *
     * @param mixed $orderless
     */
    static function MD5(iterable $dh, $orderless = true): string {
        // unique MD5 hash
        $r = [];
        foreach ($dh as $key => $value) {
            if (\is_int($key) && $orderless) {
                $key = '';
            }
            if (\is_bool($value)) {
                $r[] = "$key-$value";
            } elseif (\is_scalar($value)) {
                $r[] = "$key:$value";
            } elseif (\is_array($value)) {
                $r[] = "$key:[".self::MD5($value, $orderless).']';
            } elseif (null === $value) {
                $r[] = "$key/";
            } else {
                trigger_error("only scalars, arrays and nulls supported. key: $key"); // need something else = add it
            }
        }
        if ($orderless) {
            ksort($r);
        }

        return md5(implode("\n", $r));
    }

    // return "-1" when not found
    static function keyOffset(array $arr, string|int $key): int {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k === $key) {
                return $i;
            }
            $i++;
        }

        return -1;
    }

    // insert items after specific key
    // no key found -  no-insert OR insert at the end (@see $insertWhenNotFound)
    // Values for keys that are already in array *may* be updated - avoid duplicate keys
    static function insertAfter(array $arr, string|int $key, array $items, bool $insertWhenNotFound = false): array {
        if (!self::keyExists($arr, $key)) {
            return $insertWhenNotFound ? array_merge($arr, $items) : $arr;
        }
        $pos = self::keyOffset($arr, $key) + 1;

        return array_merge(\array_slice($arr, 0, $pos, true), $items, \array_slice($arr, $pos, null, true));
    }

    // insert item(s) before specific key
    // no key found -  no-insert OR insert at the beginning (@see $insertWhenNotFound)//
    // Values for keys that are already in array *may* be updated - avoid duplicate keys
    static function insertBefore(array $arr, string|int $key, array $items, bool $insertWhenNotFound = false): array {
        if (!\array_key_exists($key, $arr)) {
            return $insertWhenNotFound ? array_merge($items, $arr) : $arr;
        }
        $pos = self::keyOffset($arr, $key);

        return array_merge(\array_slice($arr, 0, $pos, true), $items, \array_slice($arr, $pos, null, true));
    }

    // no support for laravel prepend($a, $v, $k) - because of incorrect arg order
    static function prepend($arr, array $kv): array {
        return $kv + $arr;
    }

    // correct args order
    static function keyExists(array $arr, string|int $key): bool {
        return \array_key_exists($key, $arr);
    }

    // https://www.geeksforgeeks.org/ruby-array-zip-function/
    // https://darraghenright.github.io/blog/2018/05/getting-zippy-with-php-arrays.html
    // zip([1,2], [3,4]) => [ [1,3], [2,4] ]
    static function zip(array $first, array ...$rest): array {
        return $rest ? array_map(null, $first, ...$rest) : array_chunk($first, 1);
    }

    // unzip array of array
    // opposite of zip
    // unzip([[1,3], [2,4]]) => [ [1,2], [3,4] ]
    static function unzip(array $arrays): array {
        $r = [];
        foreach ($arrays as $arr) {
            foreach ($arr as $k => $v) {
                $r[$k][] = $v;
            }
        }

        return $r;
    }

    // when $preserveKeys - your "keys" will be overwritten by nested array keys
    // flatten([1,2,[3,4]]) => [1,2,3,4]
    // flatten([age => 50, name => [first => Ser, last => Parf]], preserveKeys:false) >> [50, Ser, Parf]
    // flatten([age => 50, name => [first => Ser, last => Parf]]) >> [age => 50, first => Ser, last => Parf]
    static function flatten(array $arr, $preserveKeys = true): array {
        // @todo - change to flatten($arr, $depth)
        // rename this method to something else
        if ($preserveKeys) {
            return self::map($arr, fn ($k, $v) => \is_array($v) ? $v : [$k => $v]);
        }

        return self::mapList($arr, fn ($k, $v) => \is_array($v) ? $v : [$v]);
    }

    static function flattenList(array $arr): array {
        return self::mapList($arr, fn ($k, $v) => \is_array($v) ? $v : [$v]);
    }

    /**
     * @return array ["dot.path" => $value]
     */
    static function flattenRecursive(array $arr): array {
        return iterator_to_array(self::iterRecursiveDot($arr));
    }

    /**
     * @return array list of values
     */
    static function flattenListRecursive(array $arr): array {
        return self::mapList(self::iterRecursive($arr), fn ($v) => [$v]);
    }

    // drop keys with values
    // we use strict comparison, so 1 and "1" are different
    static function dropValues(array $arr, ...$values): array {
        $drop = self::flipTo($values);

        return self::map($arr, fn ($k, $v) => ($drop[$v] ?? 0) ? [] : [$k => $v]);
    }

    // key of minimal value
    static function minValueKey(iterable $arr): mixed {
        \is_array($arr) || $arr = iterator_to_array($arr);
        $min = min($arr);

        return array_search($min, $arr, true);
        // return key(self::minX($arr, 1));
    }

    // key of maximal value
    static function maxValueKey(iterable $arr): mixed {
        \is_array($arr) || $arr = iterator_to_array($arr);
        $max = max($arr);

        return array_search($max, $arr, true);
        // return key(self::maxX($arr, 1));
    }

    // top X minimal values from array, null values ignored
    // keys preserved
    // order or duplicate-value keys is random
    static function minX(iterable $arr, int $count = 1, $map = null, $where = null): array {
        $cnt = 0;
        $max_r = null;
        $r = [];
        ($where || $map) && $arr = self::map($arr, $map, where: $where);
        foreach ($arr as $k => $v) {
            if ($v === null) {
                continue;
            }
            if ($cnt < $count) { // filling up buffer
                $r[$k] = $v;
                ++$cnt === $count && $max_r = max($r);
                // v('init', $r);

                continue;
            }
            if ($v >= $max_r) {
                continue;
            }
            $max_key = array_search($max_r, $r, true);
            unset($r[$max_key]);
            $r[$k] = $v;
            // v(['add' => $v, 'remove' => $max_r]);
            $max_r = max($r);
        }
        asort($r);  // sort result

        return $r;
    }

    // top X maximal values from array, null values ignored
    // keys preserved
    // order or duplicate-value keys is random
    static function maxX(iterable $arr, int $count = 1, $map = null, $where = null): array {
        $cnt = 0;
        $min_r = null;
        $r = [];
        ($where || $map) && $arr = self::map($arr, $map, where: $where);
        foreach ($arr as $k => $v) {
            if ($v === null) {
                continue;
            }
            if ($cnt < $count) { // filling up buffer
                $r[$k] = $v;
                ++$cnt === $count && $min_r = min($r);

                continue;
            }
            if ($v <= $min_r) {
                continue;
            }
            $min_key = array_search($min_r, $r, true);
            unset($r[$min_key]);
            $r[$k] = $v;
            $min_r = min($r);
        }
        asort($r);  // sort result

        return $r;
    }

    // first value from array | null
    static function first(iterable $arr, $where = null, $default = null, $map = null): mixed {
        if (\is_array($arr) && !$map && !$where) {
            return $arr ? reset($arr) : $default; // obvious case
        }
        $r = self::map($arr, $map, where: $where, while: 1);

        return $r ? reset($r) : $default;
    }

    // first value from array | null
    static function firstKey(iterable $arr, $where = null, $default = null, $map = null): mixed {
        $r = self::map($arr, $map, where: $where, while: 1);

        return $r ? key($r) : $default;
    }

    // second value from array | null
    static function second(iterable $arr, $where = null, $default = null): mixed {
        $items = array_values(self::firstX($arr, 2, $where));

        return $items[1] ?? $default;
    }

    // third value from array | null
    static function third(iterable $arr, $where = null, $default = null): mixed {
        $items = array_values(self::firstX($arr, 3, $where));

        return $items[2] ?? $default;
    }

    // last value from array
    static function last(iterable $arr, $where = null, $default = null, $map = null): mixed {
        $r = self::map($arr, $map, where: $where, while: 1, reverse: 1);

        return $r ? reset($r) : $default;
    }

    // last key from array | null
    static function lastKey(iterable $arr, $where = null, $default = null): mixed {
        $kv = self::lastX($arr, 1, $where);

        return $kv ? key($kv) : $default;
    }

    // first X values (keys preserved)
    static function firstX(iterable $arr, $count = 1, $where = null): array {
        if (\is_array($arr) && !$where) {
            return \array_slice($arr, 0, $count, true);
        }

        return self::fold($arr, fn ($a, $k, $v) => \hb\then($a[$k] = $v, $a), [], where: $where, while: $count);
    }

    // last X values  (keys preserved)
    static function lastX(iterable $arr, $count = 1, $where = null): array {
        if (\is_array($arr) && !$where) {
            return \array_slice($arr, -$count, null, true);
        }
        $r = self::fold($arr, fn ($a, $k, $v) => \hb\then($a[$k] = $v, $a), [], where: $where, while: $count, reverse: 1);

        return array_reverse($r, true);
    }

    // $map "old_key:new_key" space delimited or array [old => new]
    static function renameKeys(array $arr, string|array $map): array {
        foreach (\hb\qw($map) as $from => $to) {
            if (!isset($arr[$from])) {
                continue;
            }
            $arr[$to] = $arr[$from];
            unset($arr[$from]);
        }

        return $arr;
    }

    /**
     * @todo -
     * add Laravel alike method's
     *
     * @see https://laravel.com/api/8.x/Illuminate/Support/Arr.html
     * @see https://github.com/laravel/framework/blob/8.x/src/Illuminate/Collections/Arr.php
     */

    // Add an element to an array using "dot" notation if it doesn't exist.
    static function add(array $arr, string $key, mixed $value): array {
        if (null === \hb\dhget($arr, $key, null)) {
            \hb\dhset($arr, $key, $value);
        }

        return $arr;
    }

    // Cross join the given arrays, returning all possible permutations.
    static function crossJoin(...$arrays): array {
        $r = [[]];
        foreach ($arrays as $k => $array) {
            $append = [];
            foreach ($r as $product) {
                foreach ($array as $item) {
                    $product[$k] = $item;
                    $append[] = $product;
                }
            }
            $r = $append;
        }

        return $r;
    }

    // * Flatten a multi-dimensional associative array with dots.
    static function dot(array $arr, string $path = ''): array { // dot => value
        return iterator_to_array(self::iterRecursiveDot($arr, $path));
    }

    // use DH::except for dot notation support
    static function except($arr, string|int|array|\Closure $keys): array {
        \is_array($arr) || $arr = self::value($arr);
        static::forget($arr, $keys);

        return $arr;
    }

    static function exceptFirst($arr, int $first): array {
        return self::mapList($arr, skip: $first);
    }

    static function exceptLast($arr, int $last): array {
        return self::mapList($arr, skip: $last, reverse: 2);
    }

    /** @compat */
    static function reject($arr, \Closure $cb): array {
        return self::except($arr, $cb);
    }

    /** @compat */
    static function reduce($arr, $cb, $inital = null): mixed {
        return self::fold($arr, $cb, $inital);
    }

    // get => DH.get => \hb\DHGET -- get($array, $key, $default = null)
    // has => DH.has -- has($array, $keys)

    // static function hasAny($array, $keys) => DH.hasAny

    // Pluck an array of values from an array.
    // static function pluck($array, $value, $key = null)
    // ^^ https://laravel.com/docs/8.x/collections#method-pluck
    // pluck(array $array, string|array $value, string|array|null $key = null)
    // https://laravel.com/docs/8.x/collections#method-pluck

    /**
     * Get a value from the array, and remove it.
     *
     * @param mixed      $array
     * @param mixed      $key
     * @param null|mixed $default
     * @param mixed      $arr
     */
    static function pull(&$arr, $key, $default = null): mixed {
        $kv = static::forget($arr, $key);

        return reset($kv) ?? $default;
    }

    // random item from array
    static function random($arr): mixed {
        return array_rand($arr);
    }

    /**
     * return up to $sampleSize items
     *
     * @param mixed $arr
     *
     * @return array [key => value | [value, ...]
     */
    static function randomSample($arr, int $sampleSize, bool $preserveKeys = true): array {
        $cnt = \count($arr);
        if ($cnt <= $sampleSize) {
            return $preserveKeys ? $arr : array_values($arr);
        }
        $keys = array_rand($arr, $sampleSize);
        if (1 == $sampleSize) {
            return $preserveKeys ? [$keys => $arr[$keys]] : $arr[$keys];
        }
        $kv = self::only($arr, $keys);

        return $preserveKeys ? $kv : array_values($kv);
    }

    static function set(&$array, $key, $value): array {
        // ~DH::set
    }

    // \data_set
    // data_set($data, 'products.*.price', 200);
    static function data_set(&$array, $key, $value): array {
        // data_set($data, 'products.desk.price', 200);
    }

    static function get($array, $key): array {
        // ~DH::get
    }

    // \data_get
    static function data_get($array, $key): array { // KEY
        // ~DH::get
        // data_get($data, '*.name');
    }

    /**
     * @psalm-return list<mixed>
     */
    public static function shuffle(array $arr, $seed = null): array {
        if (null === $seed) {
            shuffle($arr);
        } else {
            mt_srand($seed);
            shuffle($arr);
            mt_srand();
        }

        return $arr;
    }

    /**
     * sort by keys
     *
     * ksort($a)
     * ksort($a, fn($left, $right) => $left <=> $right)
     * ksort($a, fn($row) => $by)
     *
     * @param mixed      $arr
     * @param null|mixed $cb  - callback
     */
    static function ksort($arr, $cb = null, bool $descending = false): array {
        \is_array($arr) || $arr = self::value($arr);
        if (!$cb) {
            $descending ? krsort($arr) : ksort($arr);

            return $arr;
        }
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        if (1 === $np) {
            $cb = $descending ? fn ($a, $b): int => $cb($a) <=> $cb($b) : fn ($a, $b): int => $cb($b) <=> $cb($a);
            uksort($arr, $cb);

            return $arr;
        }
        if (2 === $np) {
            uksort($arr, $cb);

            return $descending ? array_reverse($arr, true) : $arr;
        }
        error('unsupported callback, 1 | 2 arguments expected');

        return [];  // just for phpstan/psalm
    }

    /**
     * sort, preserve keys
     *
     * sort($a)
     * sort($a, fn($left, $right) => $left <=> $right)
     * sort($a, fn($row) => $by)
     * sort($a, "field -field2")  @see DH::sort
     *
     * @param mixed      $arr
     * @param null|mixed $callback
     *
     * @return array<mixed>
     */
    static function sort($arr, $callback = null, bool $descending = false): array {
        \is_array($arr) || $arr = self::value($arr);
        if (!$callback) {
            $descending ? arsort($arr) : asort($arr);

            return $arr;
        }
        if (\is_string($callback) || \is_array($callback)) {
            $arr = DH::sort($arr, $callback);

            return $descending ? array_reverse($arr, true) : $arr;
        }
        $np = (new \ReflectionFunction($callback))->getNumberOfParameters();
        if (1 === $np) {
            return self::sortBy($arr, $callback, $descending);
        }
        if (2 === $np) {
            uasort($arr, $callback);

            return $descending ? array_reverse($arr, true) : $arr;
        }
        error('unsupported callback, 1 | 2 arguments expected');
    }

    static function sortBy($arr, $cb, $descending = false): array {
        \is_array($arr) || $arr = self::value($arr);
        $cb = $descending ? fn ($a, $b): int => $cb($a) <=> $cb($b) : fn ($a, $b): int => $cb($b) <=> $cb($a);
        uasort($arr, $cb);

        return $arr;
    }

    // Recursively sort an array by keys and values.
    static function sortRecursive(array $arr, $descending = false): array {
        foreach ($arr as &$value) {
            \is_array($value) && $value = static::sortRecursive($value, $descending);
        }
        if (static::isAssoc($arr)) {
            $descending ? krsort($arr) : ksort($arr);
        } else {
            $descending ? rsort($arr) : sort($arr);
        }

        return $arr;
    }

    static function query($arr): string {
        return http_build_query($arr, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param mixed $value
     */
    static function wrap($value): array {
        if (null === $value) {
            return [];
        }

        return \is_array($value) ? $value : [$value];
    }

    static function times($times, $cb): array {
        //  == for(range(1..$times) as $i) $r[$i] =$cb($i);
        return array_map($cb, range(1, $times));
    }

    /**
     * push if not empty
     */
    static function pushNE(array $arr, mixed $value): array {
        // todo
    }

    /**
     * insert at first position if not empty
     */
    static function unshiftNE(array $arr, mixed $value): array {
        // todo
    }

    /**
     * push
     */
    static function push(array $arr, mixed $value): array {
        // todo
    }

    /**
     * insert at first position
     */
    static function unshift(array $arr, mixed $value): array {
        // todo
    }

    /**
     * pop last value, return it, modifies array
     */
    static function pop(array &$arr, mixed $value): mixed {
        // todo
    }

    /**
     * return first value, modifies array
     */
    static function shift(array &$arr, mixed $value): mixed {
        // todo
    }

    // unique(null | $field | $cb) - remove duplicates
// uniqueStrict(... )  - same
// take($a, $nn | cb)
// takeUntil($a, $cb)
// takeWhile($a, $cb)
}
