<?php

declare(strict_types=1);

namespace hb\test;

class Test {

    function hello(string $str): string {
        return 'Hello '.$str;
    }

    function sum(int $a, int $b): int {
        return $a + $b;
    }
}
