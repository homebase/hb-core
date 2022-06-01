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
     * getRef - get Reference to an Item
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     * @param bool                  $autocreate - create path if not found (default)
     */
    static function &getRef(array|object $dh, string|array $path, bool $autocreate = true): mixed {
        return ['todo'];
    }

    /**
     * getArrayRef - get Reference to an Array Item (create item if needed)
     *
     * @param mixed[]|object        $dh
     * @param int[]|string|string[] $path
     * @param bool                  $autocreate - create path if not found (default)
     *
     * @return mixed[]
     */
    static function &getArrayRef(array|object $dh, string|array $path, bool $autocreate = true): array {
        $r = &self::getRef($dh, $path, $autocreate);
        if ($r === null) {
            $r = [];
        }
        if (\is_array($r)) {
            return $r;
        }
        \hb\error('DH structure error - array node expected. non array found');
    }

    /**
     * getP - extract ["path" => value, ...] items froom DH
     *
     * @param mixed[]|object $dh
     * @param string         $wpath - wildcard path
     *
     * @return mixed[]
     */
    static function getP(array|object $dh, string $wpath): array {
        return [];
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
                $r = $v[0] ?? 0;
            }
        }

        return null;
    }

    /**
     * dot-notation presentation of dh[$path]
     * ["dot.path" => $value]
     * ~same as getP($dh, "*")
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
     * @param mixed[]|object $dh
     */
    static function setW(array|object &$dh, string $wpath, mixed $value): void {
        \hb\todo();
    }

    /**
     * @param mixed[]|object $dh
     */
    static function setQ(array|object &$dh, string $qpath, mixed $value): void {
        \hb\todo();
    }

    /**
     * @param mixed[]|object $dh
     */
    static function setCB(array|object &$dh, string $wpath, \Closure $cb): void {
        \hb\todo();
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

    # # Merging datasets

    /**
     * Merge
     *
     * universal merge method, developers can implement any logic there
     * null callback value treated as remove item
     *
     *  internal details:
     *     two pass system:
     *         first iterate over $dh
     *         then iterate over $dh2 (ONLY missing elements)
     *
     * TODO: idea - ADD BIT_FIELD what to iterate (sometimes we need only one iteration)
     *
     * @param mixed[]|object $dh
     * @param mixed[]|object $dh2
     * @param \Closure       $cb  function(original_value | null, merged_value | null) => result_value | null
     *
     * @return mixed[]
     */
    static function merge(array|object $dh, array|object $dh2, \Closure $cb): array {
        \hb\todo();

        return [];
    }

    /**
     * Merge - override ALL nodes (existing and new) - array_recursive_replace
     *
     * @param mixed[]|object $dh
     * @param mixed[]|object $dh2
     *
     * @return mixed[]
     */
    static function update(array|object $dh, array|object $dh2): array {
        return self::merge($dh, $dh2, fn ($a, $b) => $b ?? $a);
    }

    /**
     * Merge - override ONLY existing nodes
     *
     * @param mixed[]|object $dh
     * @param mixed[]|object $dh2
     *
     * @return mixed[]
     */
    static function updateExisting(array|object $dh, array|object $dh2): array {
        return self::merge($dh, $dh2, fn ($a, $b) => $a !== null ? $b : $a);
    }

    /**
     * MergeNew - import new nodes ONLY, keep old values intact
     *
     * @param mixed[]|object $dh
     * @param mixed[]|object $dh2
     *
     * @return mixed[]
     */
    static function mergeNew(array|object $dh, array|object $dh2): array {
        return self::merge($dh, $dh2, fn ($a, $b) => $a === null ? $b : $a);
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

    /**
     * shift value from array node ; NULL if no item
     *
     * @param mixed[]|object $dh
     */
    static function shift(array|object &$dh, string $path): mixed {
        $d = &self::getArrayRef($dh, $path, false);
        if ($d) {
            return array_shift($d);
        }

        return null;
    }

    /**
     * pop value from array node ; NULL if no item
     *
     * @param mixed[]|object $dh
     */
    static function pop(array|object &$dh, string $path): mixed {
        $d = &self::getArrayRef($dh, $path, false);
        if ($d) {
            return array_pop($d);
        }

        return null;
    }

    /**
     * unshift value into array node
     *
     * @param mixed[]|object $dh
     */
    static function unshift(array|object &$dh, string $path, mixed $value): void {
        $d = &self::getArrayRef($dh, $path);
        array_unshift($d, $value);
    }

    /**
     * push value into array node
     *
     * @param mixed[]|object $dh
     */
    static function push(array|object &$dh, string $path, mixed $value): void {
        $d = &self::getArrayRef($dh, $path);
        $d[] = $value;
    }
}
