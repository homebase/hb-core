<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hbc\deephash;

/**
 * (DH) Deep Hash
 *
 * Access to nested structures using dot notation
 * "Dot.Notation" getters and setters for Deep Arrays, Objects, \Closures
 *
 * @see https://github.com/homebase/hb-core/blob/main/DH-DeepHash.md
 */

/**
 * IMPORTANT !! always use \DH::$method to access this class
 */
abstract class DeepHash extends DeepHash0 {
    /**
     * iDeepHash instatiation BY value of $DH array. non-array-dh are not modified
     *
     * @param mixed[]|object $dh
     */
    static function i(array|object $dh = []): iDeepHash {
        return new iDeepHash($dh);
    }

    /**
     * iDeepHash instatiation by REFerence to array/whatever-DH-supports  !!!
     *
     * @param mixed[]|object $dh
     */
    static function ref(array|object &$dh): iDeepHash {
        return new iDeepHash($dh);
    }

    /**
     * Create DH from ["path" => value] array
     *
     * @param mixed[] $nv
     * @param bool    $recursion - parse deep constucts ['a' => ['b.c' => 1]] as ['a.b.c' => 1]
     */
    static function create(array $nv, bool $recursion = false): iDeepHash {
        // $dh
        if ($recursion) {
            $nv = self::flatten($nv);
        }
        $dh = [];
        self::set($dh, $nv);

        return new iDeepHash($dh);
    }

    /**
     * get
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     */
    static function get(array|object $dh, string|array $path, mixed ...$default): mixed {
        if (\is_string($path)) {
            # if (once()) {
            return self::_get($dh, $path, ...$default);
        }
        $r = [];
        foreach ($path as $k => $p) {
            $r[$k] = self::get($dh, (string) $p, ...$default);
        }

        return $r;
    }

    /**
     * first - first EXISTING item or NULL
     *
     * @param mixed[]|object $dh
     * @param string         $pathList - space delimited list of pathes
     */
    static function first(array|object $dh, string $pathList): mixed {
        foreach (explode(' ', $pathList) as $p) {
            $v = self::q($dh, $p);
            \hb\error_if($v === null, 'DH structure error');
            if ($v) {
                return $v[0] ?? null;
            }
        }

        return null;
    }

    /**
     * any - first NON-empty item or NULL
     *
     * @param mixed[]|object $dh
     * @param string         $pathList - space delimited list of pathes
     */
    static function any(array|object $dh, string $pathList): mixed {
        foreach (explode(' ', $pathList) as $p) {
            $v = self::q($dh, $p);
            \hb\error_if($v === null, 'DH structure error');
            if ($v && ($v[0] ?? 0)) {
                return $v[0] ?? null;
            }
        }

        return null;
    }

    /**
     * getW - extract DH subset as DH
     *
     * @param mixed[]|object $dh
     * @param string         $wpath - wildcard path
     *
     * @return mixed[]
     */
    static function getW(array|object $dh, string $wpath): ?array {
        return [];
    }

    /**
     * getQ - extract items
     *
     * @param mixed[]|object $dh
     * @param string         $qpath - query path
     *
     * @return mixed[]
     */
    static function getQ(array|object $dh, string $qpath): ?array {
        return [];
    }

    /**
     * getV - extract items using View Path Syntax
     *
     * @param mixed[]|object $dh
     * @param string         $vpath - view path
     *
     * @return mixed[]
     */
    static function getV(array|object $dh, string $vpath): ?array {
        return [];
    }

    /**
     * dot-notation presentation of dh[$path]
     * ["dot.path" => $value]
     * ~same as getW($dh, "*")
     *
     * @see flatten, getW
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     *
     * @return array<string,mixed>
     */
    static function getDot(array|object $dh, string|array $path = ''): array {
        return self::flatten(self::get($dh, $path));
    }

    /**
     * set(dh, path, value)
     * set(dh, [path => value, ...])
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     */
    static function set(array|object &$dh, string|array $path, mixed $value = null): void {
        if (\is_array($path)) {
            foreach ($path as $k => $v) {
                self::set($dh, $k, $v);
            }
        }
    }

    /**
     * remove item(s) from DH
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     */
    static function remove(array|object &$dh, string|array $path): void {
        if (\is_array($path)) {
            self::set($dh, $path, null);

            return;
        }
        self::setW($dh, $path, null);
    }

    /**
     * @param mixed[]|object $dh
     */
    static function setW(array|object &$dh, string $wpath, mixed $value): void {
        \hb\todo();
    }

    /**
     * removes NULLS and "" and [] from array RECURSIVELY
     * we'll keep false and (int)0 and "0" !!!
     *
     * @param array<mixed> $dh
     *
     * @return array<mixed>
     */
    static function cleanUp(array $dh): array {
        return \hb\Arr::cleanUp($dh);
    }
}
