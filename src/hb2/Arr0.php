<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

/**
 * @todo All methods here MUST NOT modify $arr
 *       move modifying methods to `Arf` class  - so far one method only - ::forget
 *
 *       create iArr class. copy all methods there, all methods there modify an original array, return $this
 *       two constructors:
 *              \hb\arr($arr = []);
 *              \hb\arf(&$arr) << via reference
 */

namespace hb2;

/**
 * Generic Array Helper - BASE CLASS
 *
 * @see  AH::* - Array of Hashes = Array of Associative Arrays ~ sql database
 * @see  DH::* - DeepHash = DOT-sepatated/Deep Array Access
 * @see  ADH::* - Array of DH, ~= mongodb database. DOT-sepatated access
 *
 * we are trying to use Laravel function naming where possible
 *
 * Ideology:
 *     Preserve array keys, unless explicitly asked to remove them,
 *     All array functions receive working iterable|array as first parameter
 *     No side effects
 */

/**
 * base class for Arr
 *
 * contains all base methods.
 * no aliases, no compatibility methods
 */
abstract class Arr0
{
    /**
     * create hash [value => $set, ..] from list of values
     *
     * @param mixed[]   $arr
     * @param int|mixed $set
     *
     * @return mixed[]
     */
    static function flipTo(array $arr, mixed $set = 1): array
    {
        return Arr::map($arr, static fn ($k, $v) => [$v => $set]);
    }

    /**
     * create hash [value => 1, ..] from list of values
     *
     * @param mixed[] $arr
     *
     * @return int[]
     */
    static function flip1(array $arr): array
    {
        return self::flipTo($arr, 1);
    }

    /**
     * iterate over all elements,
     *   if all callbacks not empty? = return count
     *   of at least one callback failed - return 0
     *   if no callbacks done - return -1
     *
     * callback is:
     *     fn($value)
     *     fn($key, $value)
     *
     * @param mixed[] $arr
     *
     * return
     *    (int) nn - all nn tests were successful
     *     0 - at least one test failed
     *    -1 - no tests were performed, $arr empty
     *
     * @throws \ReflectionException
     */
    static function all(array $arr, \Closure $cb): int
    {
        if (!$arr) {
            return -1;
        }
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        $cnt = 0;
        if (1 === $np) {
            foreach ($arr as $v) {
                if (!$cb($v)) {
                    return 0;
                }
                $cnt++;
            }
        } elseif (2 === $np) {
            foreach ($arr as $k => $v) {
                if (!$cb($k, $v)) {
                    return 0;
                }
                $cnt++;
            }
        } else {
            error("Arr::all callback requires 1 or 2 arguments. got $np");
        }

        return $cnt;
    }

    /**
     * if any non-empty callback, return first non-empty callback [key=>value], [] otherwise
     *
     * callback is:
     *     fn($value)
     *     fn($key, $value)
     *
     * @param mixed[] $arr
     *
     * @return mixed[] [$successful_key => $successful_return] | []
     *
     * @throws \ReflectionException
     */
    static function any(array $arr, \Closure $cb): array
    {
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        if (1 === $np) {
            foreach ($arr as $k => $v) {
                if ($r = $cb($v)) {
                    return [$k => $r];
                }
            }
        } elseif (2 === $np) {
            foreach ($arr as $k => $v) {
                if ($r = $cb($k, $v)) {
                    return [$k => $r];
                }
            }
        } else {
            error("Arr::any callback requires 1 or 2 arguments. got $np");
        }

        return [];
    }

    /**
     * Map Array to new Array
     *
     * Suggested usage (use php8 named arguments):
     *   Arr::map($arr, where: fn($a) => $a % 2, skip: 10, while: fn($a) => $a > 19, reverse: 1)
     *
     * Order of callbacks:
     *
     *  0. reverse   - false, true
     *  0. fromEnd   - reverse, apply all methods, reverse again (double reverse)
     *  1. where     - fn($v) | fn($k, $v) | "fieldname" | ["field", "f" => v, f => null, ...]
     *  2. skip      - fn($v) | fn($k, $v) | items-to-skip | "fieldname" | ["field", "f" => v, f => null, ...]
     *  3. while     - fn($v) | fn($k, $v) | items-to-get | "fieldname" | ["field", "f" => v, f => null, ...]
     *  4. map       - fn($v) => $v | fn($k, $v) => [$k, $k2, $old_k => $new_k, ...] | "field" | ["f1", "f2", $old_k => $new_k, ...]
     *
     * Important - map is executed LAST
     *
     * when $map is (string|int) $field:
     *   we return column $field when it is not null. ($arr is array of arrays)
     *   App:map($table, "field") >> [rowkey => $fieldvalue, ...]  == AH::column($ah, "field")
     *
     * when $map is array (fieldlist) - ($arr is array of arrays) - @see only()
     *   we return NON-NULL columns from fieldlist when at least one field is not null
     *
     * when $map is array (old_field_name=>new_field+name) - ($arr is array of arrays) - @see only()
     *   we return NON-NULL columns from fieldlist when at least one field is not null PLUS we rename field
     *
     * @param iterable<mixed>              $arr
     * @param \Closure|int|string|string[] $where
     * @param \Closure|int|string|string[] $skip
     * @param \Closure|int|string|string[] $while
     * @param \Closure|int|string|string[] $map
     *
     * @return mixed[]
     */
    static function map(
        iterable $arr,
        null|array|\Closure|int|string $map = null,
        null|array|\Closure|int|string $where = null,
        null|array|\Closure|int|string $skip = null,
        null|array|\Closure|int|string $while = null,
        bool $reverse = false,
        bool $fromEnd = false
    ): array {
        if ($fromEnd) {
            $reverse = true;
        }
        if ($where || $skip || $while || $reverse) {
            $arr = self::iter($arr, $where, $skip, $while, $reverse);
        }
        if (!$map) {
            error_if(\is_array($arr), 'you need at least one callback for map method');

            /** @psalm-suppress PossiblyInvalidArgument */
            $r = iterator_to_array($arr);

            return $fromEnd ? array_reverse($r, true) : $r;
        }
        if (\is_string($map) || \is_int($map)) {
            $map = static fn ($k, array $v): array => isset($v[$map]) ? [$k => $v[$map]] : [];
        }
        if (\is_array($map)) {
            $map = /** @return mixed[] */
            static fn ($k, array $v): array => ($r = self::only($v, $map)) ? [$k => $r] : [];
        }

        $r = [];

        switch ((new \ReflectionFunction($map))->getNumberOfParameters()) {
            case 1: // callback($value) => $value
                if (\is_array($arr)) {
                    /** @psalm-suppress all */
                    $r = array_map($map, $arr);
                } else { // generator
                    foreach ($arr as $k => $v) {
                        /** @psalm-suppress TooFewArguments */
                        $r[$k] = $map($v);
                    }
                }

                break;

            case 2: // callback($key, $value) => [$k=>v, ...]
                foreach ($arr as $k => $v) {
                    foreach ($map($k, $v) as $new_k => $new_v) {
                        /** @psalm-suppress RedundantCondition */
                        if ($new_k !== null) {
                            $r[$new_k] = $new_v;
                        }
                        // v([$k, $v, $new_k, $new_v]);
                    }
                }

                break;

            default:
                error('Arr::map callback must accept one or two arguments: fn($value) or fn($key, $value)');
                // break;
        }

        return $fromEnd ? array_reverse($r, true) : $r;
    }

    /**
     * Map List (non associative arrays) => List
     *  Can do all: chop leading/trailing items, expand item into several or none, converting, filtering, while
     *
     * Suggested usage (use php8 named arguments):
     *   Arr::mapList($arr, $callback, where: fn($a) => $a % 2, skip: 10, while: fn($a) => $a > 19,  reverse: 1)
     *
     * Order of callbacks:
     *  0. reverse   - false|0, 1|true - reverse, 2 - reverse, apply callbacks, reverse again
     *  0. fromEnd   - reverse, apply all methods, reverse again  (double reverse)
     *  1. where     - fn($v) | fn($k, $v)
     *  2. skip      - fn($v) | fn($k, $v)
     *  3. while     - fn($v) | fn($k, $v)
     *  4. map       - fn($k, $v) => [$value1, $value2, ...] | []  -  expand item into many or none
     *
     *  Example: duplicate even numbers in list:
     *    A::mapList([1, 2, 3, 4], fn($v) => $v & 1 ? [] : [$v, $v]);
     *
     * @param iterable<mixed>              $arr
     * @param \Closure|int|string|string[] $where
     * @param \Closure|int|string|string[] $skip
     * @param \Closure|int|string|string[] $while
     *
     * @return mixed[]
     *
     * @throws \ReflectionException
     */
    static function mapList(
        iterable $arr,
        \Closure $map,
        null|array|\Closure|int|string $where = null,
        null|array|\Closure|int|string $skip = null,
        null|array|\Closure|int|string $while = null,
        bool $reverse = false,
        bool $fromEnd = false
    ): array {
        if ($fromEnd) {
            $reverse = true;
        }
        if ($where || $skip || $while || $reverse) {
            $arr = self::iter($arr, $where, $skip, $while, $reverse);
        }
        $r = [];
        $np = (new \ReflectionFunction($map))->getNumberOfParameters();
        if (1 === $np) {
            foreach ($arr as $v) {
                foreach ($map($v) as $v) {
                    $r[] = $v;
                }
            }
        } elseif (2 === $np) {
            foreach ($arr as $k => $v) {
                foreach ($map($k, $v) as $v) {
                    $r[] = $v;
                }
            }
        } else {
            error('Arr::mapList callback must accept one or two arguments: fn($value) or fn($key, $value)');
        }

        return $fromEnd ? array_reverse($r) : $r;
    }

    /**
     * Iterate over Array
     *
     * Suggested usage (use php8 named arguments):
     *   Arr::each($arr, where: fn($a) => $a % 2, skip: 10, while: fn($a) => $a > 19,  reverse: 1)
     *
     * Order of callbacks:
     *
     *  0. reverse   - false|0, 1|true - reverse
     *  1. where     - fn($v) | fn($k, $v) | "fieldname" | ["field", "f" => v, f => null, ...]
     *  2. skip      - fn($v) | fn($k, $v) | items-to-skip | "fieldname" | ["field", "f" => v, f => null, ...]
     *  3. while     - fn($v) | fn($k, $v) | items-to-get | "fieldname" | ["field", "f" => v, f => null, ...]
     *  4. cb        - fn($v) => $v | fn($k, $v)
     *
     * @param iterable<mixed> $arr
     * @param null|mixed      $where
     * @param null|mixed      $skip
     * @param null|mixed      $while
     *
     * @return int - number of iterations where result is not empty
     */
    static function each(
        iterable $arr,
        \Closure $cb,
        $where = null,
        $skip = null,
        $while = null,
        bool $reverse = false,
    ): int {
        if ($where || $skip || $while || $reverse) {
            $arr = self::iter($arr, $where, $skip, $while, $reverse);
        }
        $cnt = 0;
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        if (1 === $np) {
            foreach ($arr as $v) {
                if ($cb($v)) {
                    $cnt++;
                }
            }
        } elseif (2 === $np) {
            foreach ($arr as $k => $v) {
                if ($cb($k, $v)) {
                    $cnt++;
                }
            }
        } else {
            error('Arr::each callback must accept one or two arguments: fn($value) or fn($key, $value)');
        }

        return $cnt;
    }

    /**
     * Fold an array @see haskel / APL
     *
     * Order of callbacks:
     *
     *  0. reverse   - false|0, 1|true - reverse
     *  1. where     - fn($v) | fn($k, $v) | "fieldname" | ["field", "f" => v, f => null, ...]
     *  2. skip      - fn($v) | fn($k, $v) | items-to-skip | "fieldname" | ["field", "f" => v, f => null, ...]
     *  3. while     - fn($v) | fn($k, $v) | items-to-get | "fieldname" | ["field", "f" => v, f => null, ...]
     *  4. cb        - fn($v) => $v | fn($k, $v)
     *
     * @param iterable<mixed> $arr
     * @param null|mixed      $carry
     * @param null|mixed      $where
     * @param null|mixed      $skip
     * @param null|mixed      $while
     *
     * NB: fold is fast - sometimes faster than map
     *     'μs' => 0.7 : php init.php --bench '\hb\Arr::fold(range(2,10), fn($c, $k, $v) => \\hb\\then($c[$k] = $v, $c), [])'
     *     'μs' => 0.8 : php init.php --bench '\hb\Arr::map(range(2,10), fn($v) => $v)'
     *
     * @return mixed $fold
     */
    static function fold(
        iterable $arr,
        \Closure $cb,
        $carry = null,    // initial value
        $where = null,
        $skip = null,
        $while = null,
        bool $reverse = false
    ): mixed {
        if ($where || $skip || $while || $reverse) {
            $arr = self::iter($arr, $where, $skip, $while, $reverse);
        }
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        if (2 === $np) {
            foreach ($arr as $k => $v) {
                $carry = $cb($carry, $v);
            }
        } elseif (3 === $np) {
            foreach ($arr as $k => $v) {
                $carry = $cb($carry, $k, $v);
            }
        } else {
            error('Arr::fold requires two/three arguments: fn($carry, $value) or fn($carry, $key, $value)');
        }

        return $carry;
    }

    /**
     * Most versatile Generator
     *
     * Order of callbacks:
     *  0. reverse
     *  1. where
     *  2. skip
     *  3. while
     *
     * @param iterable<mixed>              $arr
     * @param \Closure|int|string|string[] $where
     * @param \Closure|int|string|string[] $skip
     * @param \Closure|int|string|string[] $while
     */
    static function iter(
        iterable $arr,
        null|array|\Closure|int|string $where = null,
        null|array|\Closure|int|string $skip = null,
        null|array|\Closure|int|string $while = null,
        bool $reverse = false
    ): \Generator {
        if ($reverse) {
            if (!\is_array($arr)) {
                $arr = iterator_to_array($arr, true);
            }
            $arr = array_reverse($arr, true);
        }
        if ($where) {
            $arr = self::_where($arr, $where);
        }
        if ($skip) {
            $arr = self::_skip($arr, $skip);
        }
        if ($while) {
            $arr = self::_while($arr, $while);
        }
        foreach ($arr as $k => $v) {
            yield $k => $v;
        }
    }

    /**
     * Most versatile Generator
     *
     * Order of callbacks:
     *  0. reverse
     *  1. where
     *  2. skip
     *  3. while
     *
     * if callback accept 1 arguments:
     *     returns $key => [value, callback($value)]
     * if callback accept 2 arguments:
     *     returns $key => [value, callback($key, $value)]
     *
     * @param iterable<mixed> $arr
     */
    static function iterCB(
        iterable $arr,
        \Closure $cb,
        mixed $where = null,
        mixed $skip = null,
        mixed $while = null,
        mixed $reverse = false
    ): \Generator {
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        $iter = self::iter($arr, $where, $skip, $while, $reverse);
        if ($np == 1) {
            foreach ($iter as $k => $v) {
                yield $k => [$v, $cb($v)];
            }

            return;
        }
        foreach ($iter as $k => $v) {
            yield $k => [$v, $cb($k, $v)];
        }
    }

    /**
     * recursive iterator, returns only leaf values
     * yields [(array)$path =>  $value]
     *
     * @param string[]        $path
     * @param iterable<mixed> $arr
     */
    static function iterRecursive(iterable $arr, array $path = []): \Generator
    {
        foreach ($arr as $k => $v) {
            // $p = array_merge($path, [$k]);
            $p = $path;
            $p[] = $k;
            if (\is_array($v)) {
                yield from self::iterRecursive($v, $p);
            } else {
                // yield $v;
                yield $p => $v;
            }
        }
    }

    /**
     * @param iterable<mixed> $arr
     */
    static function iterRecursiveDot(iterable $arr, int|string $path = ''): \Generator
    {
        foreach ($arr as $k => $v) {
            $p = $path ? "$path.$k" : $k;
            if (\is_array($v)) {
                yield from self::iterRecursiveDot($v, $p);
            } else {
                yield $p => $v;
            }
        }
    }

    // not all iterators can be converted to arrays
    /**
     * @psalm-return list<array{0: mixed, 1: mixed}>
     *
     * @param mixed $iter
     *
     * @return array[]
     */
    static function dumpIter($iter): array
    {
        $r = [];
        foreach ($iter as $key => $value) {
            $r[] = [$key, $value];
        }

        return $r;
    }

    /**
     * sum items / callbacks
     *
     * $cb = null              : just sum (where, skip, while, ...)
     * $cb = \Closure          : sum callbacks
     * $cb = "fieldName"       : sum of $row["fieldName"]
     * $cb = [list of fields]  : count not null $row["fieldName"] for every field  >> ['fieldName' => sum, ..]
     *
     * @param iterable<mixed>          $arr
     * @param \Closure|string|string[] $cb
     *
     * @return float|int|mixed[]
     */
    static function sum(
        iterable $arr,
        $cb = null,
        mixed $where = null,
        mixed $skip = null,
        mixed $while = null,
        bool $reverse = false
    ): array|float|int {
        $sum = 0;
        if (\is_array($cb)) { // array of fields
            $sum = self::flipTo($cb, 0); // [field => 0]
            $cb = static function (array $r) use (&$sum): void {
                Arr::each(
                    $sum,
                    static function ($k, $v) use (&$sum, $r): void {
                        $sum[$k] += $r[$k] ?? 0;
                    }
                );
            };
        } elseif (\is_string($cb)) { // one field
            $cb = static function (array $r) use (&$sum, $cb): void { $sum += $r[$cb] ?? 0; };
        } elseif ($cb instanceof \Closure) { // callback
            $cb = static function ($r) use (&$sum, $cb): void { $sum += $cb($r); };
        } else {
            $cb = static function ($r) use (&$sum): void { $sum += $r; };
        }
        Arr::each($arr, $cb, $where, $skip, $while, $reverse);

        return $sum;
    }

    /**
     * min ($callback | $field | $fields)
     * $cb = \Closure          : min callbacks
     * $cb = "fieldName"       : min of $row["fieldName"]
     * $cb = [list of fields]  : min $row["fieldName"] for every field  >> ['fieldName' => min, ..]
     *
     * @param iterable<mixed>          $arr
     * @param \Closure|string|string[] $cb
     * @param null|mixed               $where
     * @param null|mixed               $skip
     * @param null|mixed               $while
     */
    static function min(
        iterable $arr,
        array|\Closure|string $cb,
        $where = null,
        $skip = null,
        $while = null
    ): mixed {
        error_unless($arr, 'non empty array expected');
        ($where || $skip || $while) && $arr = self::iter($arr, $where, $skip, $while);
        if (\is_string($cb)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return min(self::map($arr, $cb));
        }
        if ($cb instanceof \Closure) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return min(Arr::mapList($arr, $cb));
        }

        return Arr::map($cb, static fn ($k, $field) => [$field, Arr::min($arr, $field)]); // fieldname => min_Value
    }

    /**
     * max ($callback | $field | $fields)
     * $cb = \Closure          : max(callbacks)
     * $cb = "fieldName"       : max of $row["fieldName"]
     * $cb = [list of fields]  : max $row["fieldName"] for every field  >> ['fieldName' => max, ..]
     *
     * @param iterable<mixed>          $arr
     * @param \Closure|string|string[] $cb
     * @param null|mixed               $where
     * @param null|mixed               $skip
     * @param null|mixed               $while
     */
    static function max(
        iterable $arr,
        array|\Closure|string $cb,
        $where = null,
        $skip = null,
        $while = null
    ): mixed {
        error_unless($arr, 'non empty array expected');
        ($where || $skip || $while) && $arr = self::iter($arr, $where, $skip, $while);
        if ($cb instanceof \Closure) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return max(Arr::mapList($arr, $cb));
        }
        if (\is_string($cb)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return max(self::map($arr, $cb));
        }

        return Arr::map($cb, static fn ($k, $field) => [$field, Arr::max($arr, $field)]); // fieldname => max_Value
    }

    /**
     * extract elements with given keys from array (optionally with renaming OLD_KEY:NEW_KEY)
     *
     * note:  keys with NULL value are not transferred
     *
     * @param \Closure|string|string[] $keys - list of keys or space delimited list of keys (@see \hb\qw)
     *                                       or mapping: source_array_key => dest_array_key
     * @param mixed[]                  $a
     *
     * @return mixed[]
     *                 Example:
     *                 - Arr::only($_POST,"age name address:location");
     *                 - Arr::only($_POST, ["age", "name", "address" => location"]);  // same as above
     */
    static function only(array $a, array|\Closure|string $keys): array
    {
        if ($keys instanceof \Closure) {
            return self::where($a, $keys);
        }
        $r = [];
        foreach (\is_array($keys) ? $keys : \hb2\qw($keys) as $k => $v) {
            if (\is_int($k)) {
                $k = $v;
            }
            $vl = $a[$k] ?? null;
            if ($vl !== null) {
                $r[$v] = $vl;
            }
        }

        return $r;
    }

    /**
     * remove Keys from array INPLACE, return removed items
     *
     * @see Arr::except non-destructive  method
     *
     * keys = space delimited keys | array of keys | [key => new_key] | callback
     *
     * @param mixed[]                     $arr
     * @param \Closure|int|mixed[]|string $keys - space delimited list of keys or "key:new_key" or array of keys / key=>new_key or a \Closure
     *
     * @return mixed[]
     */
    static function forget(array &$arr, array|\Closure|int|string $keys): array // removed items
    {
        $r = [];
        if ($keys instanceof \Closure) {
            $cb = $keys;
            $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
            foreach ($arr as $k => $v) {
                if (($np == 1 && $cb($v)) || ($np > 1 && $cb($k, $v))) {
                    $r[$k] = $arr[$k];
                    unset($arr[$k]);
                }
            }

            return $r;
        }
        $keys = \hb2\qw($keys);
        foreach ($keys as $k => $nk) {
            if (\is_int($k)) {
                $k = $nk;
            }
            $r[$nk] = $arr[$k];
            unset($arr[$k]);
        }

        return $r;
    }

    /**
     * filter array via callback
     *
     * @param iterable<mixed> $arr
     *
     * @return mixed[]
     */
    static function filter(iterable $arr, \Closure $callback): array
    {
        return self::where($arr, $callback);
    }

    /**
     * filter array via callback into Two arrays (and optionally filter out some elements)
     *
     * @see partition
     * items where callback returned null are not returned - remove items from resulting arrays
     * same as partition (with null-result - remove)
     *     [$less_than_2, $more_than_2] = Arr::filter2($arr, fn($v) => $v == 2 ? null : $v > 2);
     *
     * @param iterable<mixed> $arr
     *
     * @return array{0: mixed, 1: mixed} [false_condition, true_condition]
     */
    static function filter2(iterable $arr, \Closure $cb): array
    {
        $f = $t = []; // false, true
        // iterate over $k => [value, arity($cb) == 1 ? callback($v) : callback($k, $v)]
        foreach (self::iterCB($arr, $cb) as $k => [$v, $c]) {
            if ($c === null) {
                continue;
            }
            if ($c) {
                $t[$k] = $v;
            } else {
                $f[$k] = $v;
            }
        }

        return [$f, $t];
    }

    //
    // partition - null support

    /**
     * count items / not-empty callbacks
     *
     * $cb = null              : just count  (where, skip, while, ...)
     * $cb = \Closure          : count non-empty
     * $cb = "fieldName"       : count where field is not null. $row["fieldName"]
     * $cb = [list of fields]  : count where field not null $row["fieldName"] for every field  >> ['fieldName' => count, ..]
     *
     * @param iterable<mixed> $arr
     * @param null|mixed      $cb
     * @param null|mixed      $where
     * @param null|mixed      $skip
     * @param null|mixed      $while
     *
     * @return int|mixed[]
     */
    static function count(
        iterable $arr,
        $cb = null,
        $where = null,
        $skip = null,
        $while = null,
        bool $reverse = false
    ): array|int {
        if (\is_array($cb)) { // count multiple keys
            $count = self::flipTo($cb, 0); // [field => 0]
            $C = static function ($k, $v) use (&$count): void {
                if ($v) {
                    $count[$k]++;
                }
            };
            $cb = static fn (array $r): int => self::each($count, static fn ($k, $v) => $C($k, (int) isset($r[$k])));
            Arr::each($arr, $cb, $where, $skip, $while, $reverse);

            return $count;
        }
        if (\is_string($cb)) {
            $cb = static fn ($r): int => (int) isset($r[$cb]);
        }
        if (!$cb) {
            $cb = static fn ($v): int => 1;
        }

        return Arr::each($arr, $cb, $where, $skip, $while, $reverse);
    }

    /**
     * value => nn_occurences
     * ~= https://laravel.com/docs/8.x/collections#method-countBy
     *
     * @param iterable<mixed> $arr
     * @param null|mixed      $cb
     * @param null|mixed      $where
     * @param null|mixed      $skip
     * @param null|mixed      $while
     *
     * @return mixed[]
     */
    static function countBy(iterable $arr, $cb = null, $where = null, $skip = null, $while = null): array
    {
        if (!$cb) {
            $cb = static fn ($r, $v) => \hb2\then($r[$v] = ($r[$v] ?? 0) + 1, $r);
        } else {
            $cb = static function ($r, $v) use ($cb) {
                $v = $cb($v);
                $r[$v] = ($r[$v] ?? 0) + 1;

                return $r;
            };
        }

        return self::fold($arr, $cb, [], $where, $skip, $while);
    }

    /**
     * group array by callback's return value or field
     * => [ group_field => [original_key =>  item, ...] ]
     *
     * callback must return
     *     true|false - treated as 1|0
     *     int or string
     *     null       - item ignored
     *
     * @see partition
     *
     * @param iterable<mixed> $arr
     *
     * @return array<mixed>
     */
    static function groupBy(iterable $arr, \Closure|int|string $cb): array
    {
        if (!$cb instanceof \Closure) {
            $cb = static fn ($a) => $a[$cb] ?? null;
        }
        $r = [];
        $np = (new \ReflectionFunction($cb))->getNumberOfParameters();
        self::each($arr, static function ($ok, $v) use (&$r, $cb, $np): void {  // $ok - original key
            /** @psalm-suppress TooManyArguments */
            $t = $np == 1 ? $cb($v) : $cb($ok, $v);
            if ($t !== null) {
                /** @psalm-suppress all */
                $g = match (1) { // g - (int) group
                    true => 1, // @phpstan-ignore-line
                    false => 0, // @phpstan-ignore-line
                    // null => item excluded,
                    default => $t
                };

                $r[$g][$ok] = $v;
            }
        });

        return $r;
    }

    /**
     * @param mixed[] $arr
     *
     * @return mixed[]
     */
    static function chunk(array $arr, int $size): array
    {
        return array_chunk($arr, $size, true);
    }

    /**
     * @param iterable<mixed> $arr
     *
     * @return mixed[]
     */
    static function where(iterable $arr, \Closure $callback): array
    {
        // @todo - array_filter
        return iterator_to_array(self::_where($arr, $callback));
    }

    /**
     * @param iterable<mixed> $arr
     *
     * @return mixed[]
     */
    static function whereNot(iterable $arr, \Closure $callback): array
    {
        return iterator_to_array(self::_whereNot($arr, $callback));
    }

    /**
     * @param iterable<mixed> $arr
     *
     * @return mixed[]
     */
    static function while(iterable $arr, \Closure $callback): array
    {
        return iterator_to_array(self::_while($arr, $callback));
    }

    /**
     * _where generator
     *
     * @param iterable<mixed> $arr
     */
    static function _where(iterable $arr, mixed $where): \Generator
    {
        error_if(\is_int($where), 'inefficient. use while=>(int) instead');
        $where = self::callback($where);

        switch ((new \ReflectionFunction($where))->getNumberOfParameters()) {
            case 1: // callback($value)
                foreach ($arr as $k => $v) {
                    if ($where($v)) {
                        yield $k => $v;
                    }
                }

                return;

            case 2: // callback($key, $value)
                foreach ($arr as $k => $v) {
                    if ($where($k, $v)) {
                        yield $k => $v;
                    }
                }

                return;

            default:
                error('where callback must accept one or two arguments: func($value) or func($key, $value)');
        }
    }

    /**
     * @param iterable<mixed> $arr
     */
    static function _whereNot(iterable $arr, mixed $where): \Generator
    {
        error_if(\is_int($where), 'inefficient. use while=>(int) instead');
        $where = self::callback($where);

        switch ((new \ReflectionFunction($where))->getNumberOfParameters()) {
            case 1: // callback($value)
                foreach ($arr as $k => $v) {
                    if (!$where($v)) {
                        yield $k => $v;
                    }
                }

                return;

            case 2: // callback($key, $value)
                foreach ($arr as $k => $v) {
                    if (!$where($k, $v)) {
                        yield $k => $v;
                    }
                }

                return;

            default:
                error('where callback must accept one or two arguments: func($value) or func($key, $value)');
        }
    }

    /**
     * _while generator
     *
     * @param iterable<mixed> $arr
     */
    static function _while(iterable $arr, mixed $while): \Generator
    {
        $while = self::callback($while);
        $np = (new \ReflectionFunction($while))->getNumberOfParameters();

        switch ($np) {
            case 1: // callback($value)
                foreach ($arr as $k => $v) {
                    if ($while($v)) {
                        yield $k => $v;

                        continue;
                    }

                    return;
                }

                return;

            case 2: // callback($key, $value)
                foreach ($arr as $k => $v) {
                    if ($while($k, $v)) {
                        yield $k => $v;

                        continue;
                    }

                    return;
                }

                return;

            default:
                error('while callback must accept one or two arguments: func($value) or func($key, $value).'." $np given");
        }
    }

    /**
     * _skip aka skipWhile generator
     *
     * @param iterable<mixed> $arr
     */
    static function _skip(iterable $arr, mixed $skip): \Generator
    {
        $skip = self::callback($skip);
        $skipping = 1;
        $np = (new \ReflectionFunction($skip))->getNumberOfParameters();

        switch ($np) {
            case 1: // callback($value)
                foreach ($arr as $k => $v) {
                    if ($skipping) {
                        if ($skip($v)) {
                            continue;
                        }
                        $skipping = 0;
                    }

                    yield $k => $v;
                }

                return;

            case 2: // callback($key, $value)
                foreach ($arr as $k => $v) {
                    if ($skipping) {
                        if ($skip($k, $v)) {
                            continue;
                        }
                        $skipping = 0;
                    }

                    yield $k => $v;
                }

                return;

            default:
                error('skip callback must accept one or two arguments: func($value) or func($key, $value).'." $np given");
        }
    }

    /**
     * convert something arrayable to array
     *
     * @param iterable<mixed>|object $iterable
     *
     * @return mixed[]
     */
    static function value(iterable|object $iterable): array
    {
        if (\is_array($iterable)) {
            return $iterable;
        }
        if (is_iterable($iterable)) {
            return iterator_to_array($iterable);
        }
        // if (\is_object($iterable)) {
        if (method_exists($iterable, 'toArray')) {
            return $iterable->toArray();
        }
        if (method_exists($iterable, '__toArray')) {
            return $iterable->__toArray();
        }
        // }
        \hb2\error("Can't cast ".get_debug_type($iterable).' to array');
    }

    /**
     * semi-internal: create callback
     * $cb is closure - use as is
     * $cb is int    = Counter : [$start_value, ..., 1, 0, 0 .....]
     * $cb is string => space delimited list of fieldnames
     * $cb is array  => [int_key => fieldName, string_fieldName => fieldValue]
     *                  if key is int - check that field is not null
     *                  if key is string & value != null - check that field have specified value (strict comparison)
     *                  if key is string & value is null - field absent or have value of null
     *
     * @param \Closure|int|mixed[]|string $cb
     */
    static function callback(array|\Closure|int|string $cb): \Closure
    {
        // todo - switch to match(true)
        // if (null === $cb) {
        //    return null;
        // }
        if ($cb instanceof \Closure) {
            return $cb;
        }
        if (\is_int($cb) && $cb) { // just a countdown 3,2,1,0,0,0...
            return static function (mixed $a) use (&$cb) { return $cb > 0 ? $cb-- : 0; };
        }
        if (\is_string($cb) && $cb) {
            $cb = qw($cb);
        }
        if (\is_array($cb)) { // [int_key => fieldName, string_fieldName => fieldValue, fieldName => null]
            return static function (array $r) use ($cb) { // we expect array of array
                foreach ($cb as $k => $v) {
                    if (\is_int($k)) {
                        if (!isset($r[$v])) {
                            return 0;
                        }

                        continue;
                    }
                    if (($r[$k] ?? null) !== $v) {
                        return 0;
                    }
                }

                return 1;
            };
        }
        error("can't create callback from ".get_debug_type($cb));
    }

    /**
     * if all callbacks are not empty? => int  where $keys value is not null
     *
     * @param mixed[]    $arr
     * @param callable[] $key2cb : $key => $callback($value) => eval_value
     *
     * @return int -  ">0": NN tests ok, 0 - at least one test failed; -1 - no tests performed
     */
    static function allKCB(array $arr, array $key2cb): int
    {
        return Arr::all($key2cb, static fn ($k, $cb) => \hb2\then($t = $arr[$k] ?? null, $t !== null ? $cb($t) : 0));
    }

    /**
     * if any non-empty callback on non-null value
     *
     * @param mixed[]    $arr
     * @param callable[] $key2cb : $key => $callback($value) => $value
     *
     * @return mixed[] - [$successful_key => $successful_return] | []
     */
    static function anyKCB(array $arr, array $key2cb): array
    {
        return Arr::any($key2cb, static fn ($k, $cb) => \hb2\then($t = $arr[$k] ?? null, $t !== null ? $cb($t) : 0));
    }

    /**
     * return $arr where specific keys mapped, keys with null callback-result removed
     *
     * @param mixed[]    $arr
     * @param callable[] $key2cb : $key => $callback($value) => $value
     *
     * @return mixed[]
     */
    static function mapKCB(array $arr, array $key2cb): array
    {
        foreach ($key2cb as $key => $cb) {
            $t = $arr[$key] ?? null;
            if ($t === null) {
                continue;
            }
            $v = $cb($t);
            if ($v === null) {
                unset($arr[$key]);

                continue;
            }
            $arr[$key] = $v;
        }

        return $arr;
    }
}

include_once __DIR__.'/../hb-functions.inc.php';
