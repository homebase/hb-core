<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hbc\deephash;

/**
 * (DH) Deep Hash 0 - BASE CLASS!! - MOSTLY LOW LEVEL METHODS ONLY
 *
 * Access to nested structures using dot notation
 * "Dot.Notation" getters and setters for Deep Arrays, Objects, \Closures
 *
 * @see  https://github.com/homebase/hb-core/blob/main/DH-DeepHash.md
 */

/**
 * IMPORTANT !! always use \DH::$method to access this class
 */

use function hb2\error_if;

abstract class DeepHash0
{
    /**
     * take care of get errors and exceptions
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     */
    static function _get(array|object $dh, array|string $path, mixed ...$default): mixed
    {
        if (\is_string($path) && $path && $path[0] === '?') { # "?path" == _get("path", null)
            $path = substr($path, 1);
            if (!$default) {
                $default = [null];
            }
        }
        $v = self::q($dh, $path);
        error_if($v === null, 'DH structure error');
        if (!$v) {
            if ($default) {
                return $default[0];
            }

            throw new \OutOfBoundsException('DH key not found: '.\hb2\x2s($path));
        }

        return $v;
    }

    /**
     * low level no-exception DH query
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     *
     * @return null|array{0?: mixed} [value] | [] (no-value) | null (structural error)
     */
    static function q(array|object $dh, array|string $path): ?array
    {
        if (\is_string($path)) {
            $path = explode('.', $path);
        }

        return ['todo'];
    }

    /**
     * Create FLAT structure from Deep Hash
     * node => node => v    to   "node.node" => v
     *
     * same as Laravel:array_dot()
     * same as self::getP($dh, "**")
     *
     * @see getDot($dh, $path)
     *
     * @param mixed[]|object $dh
     * @param string         $prefix - internal - prefix for retuned keys
     *
     * @return mixed[] flattened array
     */
    static function flatten(array|object $dh, /* internal */ string $prefix = ''): array
    {
        $r = [];
        foreach ($dh as $k => $v) {
            if (\is_array($v) || (\is_object($v) && is_iterable($v))) {
                /** @psalm-suppress InvalidArgument */
                $r += self::flatten($v, $prefix.$k.'.');
            } else {
                $r[$prefix.$k] = $v;
            }
        }

        return $r;
    }
}
