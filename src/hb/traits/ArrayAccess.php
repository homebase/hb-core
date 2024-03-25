<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hb\traits;

/**
 * Homebase-Specific Array Access interface
 * - no null values allowed
 * - assigning null means delete item
 *
 * implements:
 *   \ArrayAccess           : $this[$x]    (get,set,insert,unset,exists)
 *   \IteratorAggregate     : foreach($this as $k => $v)
 *   \Countable             : count($this)
 */
trait ArrayAccess
{
    protected $D = [];

    // \ArrayAccess

    public function offsetGet($k)
    {
        return $this->D[$k] ?? null;
    }

    // set / insert
    public function offsetSet($k, $v): void
    {
        if (null === $v) {
            if (null === $k && !\hb\isSuppressed()) {
                error_if(1, 'array[] = null disallowed'); // to suppress use "@$node[] = null"
            }
            unset($this->D[$k]);

            return;
        }
        if (null === $k) {
            $this->D[] = $v;

            return;
        }
        $this->D[$k] = $v;
    }

    final public function offsetExists($k)
    {
        return null === $this->offsetGet($k) ? false : true;
    }

    final public function offsetUnset($k): void
    {
        $this->offsetSet($k, null);
    }

    // \Countable

    public function count()
    {
        return \count($this->D);
    }

    // \IteratorAggregate
    public function getIterator()
    {
        return new \ArrayIterator($this->D);
    }
}
