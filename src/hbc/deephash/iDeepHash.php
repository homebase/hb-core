<?php

namespace hbc\deephash;

use hb\DH;

/**
 * ArrayLike DH Object
 *
 * DH trait with cherry on top
 *
 * Allows DH-style array access
 *
 * - DH::i()                   - empty iDeepHash
 * - DH::i(["new-array"])      - iDeepHash from array
 * - DH::ref(&$existing_array) - iDeepHash from array reference
 *
 *  support *ALL* methods from DH class
 *
 *  @see  DH trait for some methods
 *
 * NEVER instantiate directly
 */
class iDeepHash implements \ArrayAccess, \IteratorAggregate, \Countable
{
    use \hb\traits\DH;

    function __construct(&$dh) {
        $this->D = &$dh;
    }

    /**
     * All DH methods are here
     *
     * @param mixed $method
     * @param mixed $args
     */
    public function __call($method, $args) {
        return DH::$method($this->D, ...$args);
    }

    static function i($dh) {
        if ($dh instanceof self) {
            return $dh;
        }

        return new static($dh);
    }

    // simplify Trait Method
    function iDH(): self {
        return $this;
    }
}
