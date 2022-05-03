<?php

declare(strict_types=1);

// This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core

namespace hbc\deephash;

/*
 * PHP Class Reflection as Deephash
 *
 * reflection.$class.methods
 * reflection.$class.method.$method.arguments
 * reflection.$class.method.$method.documentation
 *
 */

/*
// DHNodeCallBack
class Reflection implements \hb\contracts\DHNode {

    public $callback;

    function __construct(string $callback) {
        $this->callback = $callback;
    }

    function _get(array $path) {
        if (count($path) == 1) {  # "reflection.$class"

        }

        $cb = $this->callback;
        return $cb($path);
    }

}

*/
