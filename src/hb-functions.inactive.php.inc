<?php

/**
 * Functions in HB namespace -- REFERENCES OUTSIDE of HB-CORE
 * 
 *  TEMP
 */

/**
 * Dependency Injection Container
 * I::service()             // Autogenerated class with return class Tips for Editors
 * I(service)
 * I(service, $spec)        // service instance spec scalar or [key => value]
 * Example: i('db', "ConnectionName"), i('log', ['host' => 'x', 'port' => 514])
 *
 * @see I::set($service, $spec, $instance)
 * @see I::reset($service, $spec)
 */
function I(string $name, /* array | string */ $spec = []) {
    // instance
    $key = '[]';
    if (!\is_array($spec)) {
        $spec = [$spec];
    }
    if ($spec) {
        if (\count($spec) > 1) {
            ksort($spec);   // key order is unimportant
        }
        $key = json_encode($spec);
    }
    if ($o = \hb2\HB::$I[$name][$key] ?? null) {
        return $o;
    }

    return \hb2\I::_get($name, $spec, $key);
}

/**
 * \hb\Object instances created using their own factory controller: method i(...$params)
 *
 * @param array ...$p
 *
 * @return Instance
 */
function iNew(string $className, ...$p) {
    // Instance
    error_unless($className, 'iNew(empty-string)');
    if (is_a($className, '\hb\contracts\IConfig', true)) {
        return $className::i(...$p);
    }
    // v("iNew: $className");
    return new $className(...$p);
}



/**
 * Decorator pattern:.
 *
 * 1.  Decorate Callable with Callable, return Callable
 *     options passed to decorator via $decorator_opts = []
 *
 *     $o         = Callable or "Class::method"
 *     $decorator = Callable or "Class::method" or "DecoratorName"
 *
 * 2.  Decorate Instance with Callable, return new Instance
 *     all methods in new Instance decorated.
 *     options passed to decorator via $decorator_opts = []
 *
 *     $o         = Instance or "Class"
 *     $decorator = Callable or "Class::method" or "DecoratorName"
 *
 * 3.  Decorate Instance($o) with Instance($d), return new Instance
 *     methods from $d replace $o-methods
 *     if decorator instance implements \hb\contracts\DecoratorInstance
 *     it can wrap replaced methods
 *
 *     $o         = Instance or "Class"
 *     $decorator = Instance (optionally implementing \hb\contracts\DecoratorInstance)
 *
 * 4. PHP-DOC decorator - get decorators+decorators_options from phpDoc comments, cache them in APC
 *    Usage: decorate($o)
 *    sample php-doc decorations:
 *    /**
 *     * @@DecoratorName
 *     * @@DecoratorName("option1", ...)
 *     *
 *     ...
 *    use decorate($o)->__phpDoc() to see decorated classes
 *
 * Named Decorators:
 *    Framework provides lots of build-in decorators.
 *    Profiling, Logging, Tracking, Caching,
 *    check \hbc\decorator\Bundle for complete list
 *
 * Decorator specification:
 *    function(callable $method, array $args, array $opts)
 *    - when decorating instance $method is [$instance, "methodName"]
 *    - when decorating closure/callable $method is that callable
 *
 * @param mixed $o
 * @param mixed $decorator
 */
function decorate(/* mixed */ $o, /* callable | "default" */ $decorator = '', array $decorator_opts = []) {
    if (!$decorator) {
        return i('decorator')->get($o, ...$decorator_opts);
    }

    return \hbc\decorator\Decorator::decorate($o, $decorator, $decorator_opts);
}

// same as decorate($o, $decorator) but with strict type check
function decorateInstance(object $o, object $decorator): object {
    return \hbc\decorator\Decorator::mix($o, $decorator);
}

/**
 * decorate object $o instance with several object decorators
 * !! different from nested decorateInstance calls,
 * all decorators decorate $o, not each other !!
 */
function decorateMany(object $o, array $decorators): object {
    return \hbc\decorator\Decorator::mixMany($o, $decorators);
}

/**
 * all external method calls are profiled.
 *
 * profileInstance($i)->method(...)
 */
function profileInstance(object $i): object {
    return decorate($i, 'profiler');
}

/**
 * Cache method/closure/direct - default adapter is APC (Shared Memory Cache).
 *
 * Basic Usage:
 *     cache([$key_prefix => $mixed], $options=[])
 *     cache($mixed, $options=[])
 *
 *     cache($instance | "ClassName")->method(...)
 *     cache()[$KEY]  <=> get/set specific keys
 *
 * Usage:
 *     cache(['key_prefix' => $instance])->method(...)
 *     cache($instance)->_update("method", ...)
 *     cache($instance)->_delete("method", ...)
 *
 *     cache("class::method")->call(...)
 *     cache(["key_prefix" => $closure])->call(...)     // key-prefix required for closures
 *
 *     cache()[$KEY]  <=> get/set specific keys
 *     cache(['key_prefix' => 0])[$KEY]  <=> get/set specific keys
 *
 * Advanced `cache()` Usage:
 *     cache()->ttl([$sec, $add_rnd_percent])[$KEY]
 *     ->add($key, $value): bool
 *     ->inc($key, $by): bool
 *     ->dec($key, $by): bool
 *     ->cas($key, $old, $new): bool
 *
 * Advanced*2
 *     Attention! this methods do not know anything about key_prefix and ttl
 *     cache()->nativeAdapterMethod(...)   << does not know about TTL and key_prefixes
 *
 * Options:
 *     (array)  ttl      - [timeout, timeout_randomize_prc] - default [3600, 30] -- 3600..3600+30%
 *     (string) adapter  - default 'cache/shm'
 *     (string) ds       - DS name (replace adapter)
 *
 * Limitations:
 *
 *   1. cached methods should not depend on object state.
 *      pass your object state in key_prefix
 *   2. cached method parameters should be JSON-able
 *   3. final KEY should have reasonable length (classname + state + arguments)
 *
 * @param mixed $o
 */
function cache(/* mixed */ $o = 0, array $opts = []) {
    // cached-value | false | \Error Exception
    return \hbc\cache\Wrapper::i($o, $opts);
}

/*
 * No-Cache cache() replacements.
 * used for debug.
 * Usage: "use function hb\noCache as cache"
 */
function noCache($o = 0, array $opts = []) {
    return \hbc\cache\Wrapper::noCache($o, $opts);
}

/**
 * Network Cache - i('cache') wrapper, default - memcache(host='memcache').
 *
 * @see cache for examples
 *
 * @param mixed $o
 */
function iCache(/* mixed */ $o = 0, array $opts = []) {
    // @todo - use \hbc\decorator\CacheWrapper($o, [cacher => iCacheNotNull, deleter => iCacheDelete])
    return \hbc\cache\Wrapper::i($o, ['adapter' => i('cache')] + $opts);
}

/**
 * Permanent Cache Storage in any DS - - default i("ds", "cache")
 * use mysql, riak, redis, json-file or whatever.
 *
 *     'ds'                   - DS (Data Storage Object or Name
 *                            - string $ds as in i("ds", $ds) or DS object
 *
 * @see cache for examples
 *
 * @param mixed $o
 */
function cacheDS(/* mixed */ $o = 0, string $ds = 'cache') {
    return \hbc\cache\Wrapper::i($o, ['ds' => $ds]);
}

/**
 * Project+App prefixed iCache.
 *
 * @param mixed $o
 */
function appCache(/* mixed */ $o = 0, array $opts = []) {
    return icache($o, ['key_prefix' => UNAME] + $opts);
}

/**
 * Git Revision Cache (SHM-Cache).
 *
 * @param mixed $o
 */
function gitCache($o, array $opts = []) {
    return cache($o, ['key_prefix' => HB::gitRevision()] + $opts);
}

/**
 * First Match
 *
 * @TODO - deprecate - use Str::fm()
 *
 * @param mixed $x
 */
// function fm(string $regexp, string $str) { // First Match
//    preg_match($regexp, $str, $m);
//
//    return @$m[1];
// }

/**
 * DataBase Engine.
 *
 * @see  config: dbe.$name
 */
function DB(string $name = ''): db\DB {
    return i('DB', $name);
}

function DS(string $name): contracts\DS {
    return i('DS', $name);
}

/**
 * @param string|object $class_or_instance "ClassName"|Instance
 */
function instance(string|object $class_or_instance, ...$args): object {
    return \is_object($class_or_instance) ? $class_or_instance : iNew($class_or_instance, ...$args);
}


/**
 * Dump debug data for Admins
 * This messages are hidden unless debug option specified:
 * CLI:
 *   --debug=$level    >> Debug messages in STDOUT
 *   --debug is the same as --debug=1
 * WEB:
 *   ?DEBUG=$level     >> Debug messages in Profiler
 *   ?DEBUG is the same as ?DEBUG=1.
 *
 * Level 1 is considered most important
 * messages level=$level and below are shown
 *
 * Example:
 *  $level=1  show ONLY level=1 messages
 *  $level=3  show level=1,2,3 messages
 *
 * @param mixed $data
 */
function debug(/* mixed */ $data, int $level = 1): void {
    if (!is_admin()) {
        return;
    }
    if (!(HB::$Q['DEBUG'] ?? 0)) {
        return;
    }
    $d = HB::$Q['DEBUG'] ?: 1;
    if ($level > $d) {
        return; // debug message is too low
    }
    if (\is_string($data)) {
        $data = x2s($data);
    }
    if (\PHP_SAPI === 'cli') {
        e("{grey}%s{/}\n", $data);
    } else {
        iprofiler()->info(Str::afterLast(caller(), '/'), [$data], ['tag' => 'debug', 'skip' => 3]);   // Profiler::info($filename, $message)
    }
}
