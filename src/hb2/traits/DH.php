<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hb2\traits;

/*
 * Homebase-Specific Array Access interface
 * - no null values allowed
 * - assigning null means delete item
 *
 * implements:
 *   \ArrayAccess           : $this[$x]    (get,set,insert,unset,exists)
 *   \IteratorAggregate     : foreach($this as $k => $v)
 *   \Countable             : count($this)
 *   $this()                : underlying array
 *   "$this"                : json(data)
 *   $this->iDH()           : iDeepHash instance (initialized by reference)
 *   $this->iDH()->Method() : call any DH method on existing data
 *
 * //  $this->getW("deep.field1 field2 ..") : extract specific fields (wildcard and options are supported) -- @see DH::getMany
 * //  $this->set(["key" => value"])       : replace existing data @see DH::setMany
 *
 * //  $this->replace($data)  : replace existing data
 * //  $this->merge($data)                     : ADD new keys
 * //  $this->merge($data, $merge_callback)    : Merge existing data
 *
 *   $this->reset()         : reset existing data
 *
 * $object[""]  = []; // reset existing data
 * $object[""];       // get existing data
 *
 */

use hb2\DH as H;
use hbc\deephash\iDeepHash;

trait DH
{
    /** @var mixed[]|object data */
    protected array|object $D = [];

    /**
     * @return mixed[]|object
     */
    function __invoke(): array|object
    {
        return $this->D;
    }

    // "$object"
    function __toString(): string
    {
        return \hb2\json($this->D);
    }

    /**
     * @return mixed[]
     */
    function __toArray(): array
    {
        return (array) $this->D;
    }

    // DH: iDeepHash - access to iDeepHash methods
    // Usage: $x->iDH()->push("path", $value);;
    function iDH(): iDeepHash
    {
        return H::ref($this->D); // initialize by REFerence
    }

    /**
     * clean up DH
     * optionally set DH content to specified
     *
     * @param mixed $D
     */
    function reset($D = []): void
    {
        $this->D = $D;
    }

    // \ArrayAccess

    public function offsetGet($offset)
    {
        if ($offset && \is_string($offset) && $offset[0] === '?') { // support for "?path"
            return H::_get($this->D, $offset, null); // return null for not-found nodes
        }

        return H::_get($this->D, $offset);
    }

    // set / unset
    public function offsetSet($offset, $value): void
    {
        H::set($this->D, $offset, $value);
    }

    final public function offsetExists($offset)
    {
        return null === $this->offsetGet($offset) ? false : true;
    }

    public function offsetUnset($k): void
    {
        H::remove($this->D, $k);
    }

    // \Countable
    public function count()
    {
        return \count((array) $this->D);
    }

    // \IteratorAggregate
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator((array) $this->D);
    }
}
