<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hb;

class Exception extends \Exception {
    /** @var mixed[] */
    public array $payload;      // optional hash payload

    /**
     * @param mixed[] $payload
     */
    function __construct(string $msg, int $code = 0, array $payload = []) {
        $this->payload = $payload;
        parent::__construct($msg, $code);
    }
}

// Non recoverable Error
// thrown by error_if, error_unless
class Error extends \Error {
    /** @var mixed[] */
    public array $payload;      // optional hash payload

    /**
     * @param mixed[] $payload
     */
    function __construct(string $msg, int $code = 0, array $payload = []) {
        $this->payload = $payload;
        parent::__construct($msg, $code);
    }
}

// anything to ~ PHP string with unprintable characters replaced
// ATTENTION: may/will intentionally lose data !!
// will try to fit result in ~200 characters
function x2s(mixed $x, int $deep = 0, int $cut = 200): string {
    return \hbc\core\StrX::x2s($x, $deep, $cut);
}

// conditional sprintf   >>> USE Str::cs instead
// Ex: cs(", %s", $word) << WRONG ORDER
// function cs(string $fmt_true, /* mixed */ $val, string $fmt_false = '') {
//    // @TODO - DEPRECATE !!! >> or move to CS($str, $format_true, $format_false)
//    return $val ? sprintf($fmt_true, $val) : ($fmt_false ? sprintf($fmt_false, $val) : '');
// }

// caller file & line as string
function caller(int $level = 1): string {
    // "file:line"
    $t = debug_backtrace()[$level];

    return "{$t['file']}:{$t['line']}";
}

/**
 * return Number-of-Missed-Events once during $timeout (for all php processes)
 * statistics kept in APC.
 *
 * Useful for throtling error messages.
 * $key can be ommited, in this case "$filename:$line" will be used as a key
 *
 * Example 1:
 * while(1) {
 *   if(once("event-name", 5))
 *     echo "text will be printed once, every 5 seconds";
 *   usleep(100000);
 * }
 * Example 2:
 *  if ($c = once($key, 10, 5))
 *      I::Log()->error("$c events occured for key=$key in last 10 seconds"); // only cases with 6+ events !!
 * Example 3: - log once every 10+ seconds
 * if ($cnt = once())
 *   i('log')->error("$cnt errors in last 10 seconds");
 */
function once(string $key = '', int $timeout = 10, int $skip_events = 0): int|bool {
    if (!$key) {
        $key = caller();
    }
    $data = apcu_fetch($key);
    $now = time();
    // first time
    if (!$data) {
        return (int) apcu_add($key, [1, $now], $timeout) && !$skip_events; // return 1
    }
    // increment
    if ($data[1] + $timeout >= $now || ($skip_events && $skip_events > $data[0])) {
        return !apcu_store($key, [++$data[0], $data[1]], $timeout); // return false
    }
    // expired
    apcu_store($key, [1, $now], $timeout);

    return $data[0]; // return inc
}

/**
 * Perls qw ( Quote Words ) alike
 * non string input returned w/o processing
 * supports hash definition.
 *
 * entry_delimiter   - entry delimiter
 * key_value_delimiter   - key/value delimiter
 *
 * example: qw("a b c:Data") == ["a", "b" , "c" => "Data"]
 *
 * @param string|string[]  $data
 * @param non-empty-string $entry_delimiter
 * @param non-empty-string $key_value_delimiter
 *
 * @return mixed[]
 */
function qw(string|array $data, string $entry_delimiter = ' ', string $key_value_delimiter = ':'): array {
    if (!\is_string($data)) {
        return $data;
    }
    if (!$data) {
        return [];
    }
    $res = ' ' === $entry_delimiter ? preg_split('/\s+/', trim($data)) : explode($entry_delimiter, $data);
    if (!strpos($data, $key_value_delimiter)) {
        return $res;
    }
    $ret = [];
    foreach ($res as $r) {
        if ($p = strpos($r, $key_value_delimiter)) {
            $ret[substr($r, 0, $p)] = substr($r, $p + 1);
        } else {
            $ret[] = $r;
        }
    }

    return $ret;
}

/**
 * qw like function, Quote Keys
 * example: qk("a b c:Data") == array( "a" => true, "b"=> true , "c" => "Data").
 *
 * @param mixed[]|string   $data
 * @param non-empty-string $entry_delimiter
 * @param non-empty-string $key_value_delimiter
 *
 * @return mixed[]
 */
function qk(string|array $data, string $entry_delimiter = ' ', string $key_value_delimiter = ':'): array {
    if (!\is_string($data)) {
        return $data;
    }
    // hash
    if (!$data) {
        return [];
    }
    $res = ' ' === $entry_delimiter ? preg_split('/\s+/', trim($data)) : explode($entry_delimiter, $data);
    $ret = [];
    foreach ($res as $r) {
        if ($p = strpos($r, $key_value_delimiter)) {
            $ret[substr($r, 0, $p)] = substr($r, $p + 1);
        } else {
            $ret[$r] = true;
        }
    }

    return $ret;
}

/**
 * is_admin - is web-visitor is admin
 *            return "cli" for cli clients.
 *
 * use default method specified in
 *
 * In order to use admin methods -
 *  configure existing is_admin methods
 *  or provide your method
 *
 * @see config "is_admin" node
 *
 * TODO - PROVIDE SAMPLE IMPLEMENTATION FOR IS_ADMIN
 *   a) Specific IPs / IP blocks
 *   b) Cookie
 *   c) HTTP-HEADER
 *   d) client HTTPS certificate (recommended) - http://nategood.com/client-side-certificate-authentication-in-ngi
 */
function is_admin(string $name = ''): string {
    // "current-admin-name" | ""
    if ($name) {
        return $name === is_admin() ? $name : '';
    }

    \hb\todo();

    /*
    $a = &HB::$CONFIG['.is_admin'];
    if (null !== $a) {
        return $a; // 99%
    }
    // if (! @HB::$CONFIG['is_admin'])
    //    return; // still initializing
    if (\PHP_SAPI === 'cli') { // already set in HB::initCli
        return $a = 'cli';
    }
    // $m = (string) C("is_admin.method", ""); // "Class::method"
    $m = (string) @HB::$CONFIG['is_admin']['method']; // "Class::method"  - is_admin can be called before CONFIG init

    return $a = $m ? $m() : '';
    */
    return ''; // php-stan
}

class TODO_Exception extends \hb\Error {
}
function todo(string $str = ''): void {
    throw new TODO_Exception($str);
}

/**
 * COLORED sprintf for (mostly) for CLI mode
 *
 * @ see i('cli')
 *  Ex:
 * \hb\e("{red}{bold}Sample {bg_green}{white}$text{/}")      << as is, no sprintf
 * \hb\e("{red}{bold}Sample {bg_green}{white}%s{/}", $text)  << use sprintf
 *
 * @param mixed $args
 */
function e(string $format, ...$args): void {
    // @todo("implement stylish array presenation");
    if (\PHP_SAPI === 'cli') {
        \hb\todo();
        // i('cli')->e($format."\n", ...$args);
        return;
    }
    if (!is_admin()) {
        return;
    }
    $text = $args ? sprintf($format, ...$args) : $format;
    $text = preg_replace('!\{[\w\/]+\}!', ' ', $text);
    echo "\n<div class=admin>{$text}</div>\n";
}

/**
 * COLORED STDERR sprintf for CLI mode
 * i(CLI) wrapper.
 *
 * @see \hb\e(..), i('cli')
 * Ex:
 *  \hb\err("{red}{bold}Error Condition: $error{/}")    << as is, no sprintf
 *  \hb\err("{red}{bold}Error Condition: %s{/}", $a)    << use sprintf
 *
 * @param mixed $args
 */
function err(string $format, ...$args): void {
    // STDERR
    // @todo("implement stylish array presenation");
    if (\PHP_SAPI === 'cli') {
        \hb\todo();
        // i('cli')->err($format."\n", ...$args);
        return;
    }
    if (!is_admin()) {
        return;
    }
    // todo - add Profiler::error()
    $text = $args ? sprintf($format, ...$args) : $format;
    $text = preg_replace('!\{[\w\/]+\}!', ' ', $text);
    echo "\n<div class=admin style='background: #f00; color: #fff'>{$text}</div>\n";
}

/**
 *  HTML Escape
 *  if $text is array - join it
 *
 *  @param string|string[] $text
 */
function h(string|array $text): string {
    // escaped text
    return htmlspecialchars(\is_array($text) ? implode('', $text) : $text, ENT_QUOTES, 'utf-8', false);
}

/**
 * json_encode + default params
 */
function json(mixed $data): string|false {
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * if value is a closure - resolve it.
 *
 * @param \Closure|mixed $value
 *
 * @return mixed
 */
function value($value) {
    // resolved Closure
    return $value instanceof \Closure ? $value() : $value;
}

/**
 * short function helper
 *   usage: $m = fn($a,$b) \hb\then($acc+=$a, $b)
 *
 * @param mixed $a
 * @param mixed $b
 *
 * @return mixed
 */
function then($a, $b) {
    return $b;
}

/**
 * remove key from hash.
 *
 * @legacy
 *
 * @param mixed[] $hash
 *
 * @return mixed removed-value
 */
function hash_unset(array &$hash, string $key): mixed {
    $vl = $hash[$key] ?? null;
    unset($hash[$key]);

    return $vl;
}

/**
 * Time-to-Live calculation by different cache implementations
 * supported ttl: (int) seconds, [(int) seconds, (int) randomize-prc].
 *
 * @param array<int>|int $ttl
 */
function ttl(int|array $ttl = [3600, 33]): int {
    // ttl .. ttl+rnd(%)
    if (\is_array($ttl)) {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        error_unless(\is_int($ttl[0]), 'ttl-part must be int');

        return $ttl[0] + random_int(0, (int) ($ttl[0] * $ttl[1] / 100));
    }

    return $ttl;
}

/**
 * Build "<a href>" tag + escaping.
 *
 * @param string|string[] $args_or_text
 * @param string          $html         extra html
 *
 * @return string Ex: a("url", ['param' => 'value'], "text") Ex: a("url", "text")
 */
function a(string $url, string|array $args_or_text = '', string $text = '', string $html = ''): string {
    // "<a href=.."
    if (\is_array($args_or_text)) {
        $url = url($url, $args_or_text); // args
    } else {
        error_if($text, 'cant combine string-args and text');

        return a($url, [], $args_or_text, $html);
    }

    return "<a href=\"{$url}\"".($html ? ' '.$html : '').'>'.h($text).'</a>';
}

/**
 * Build URL (Safe)
 *
 * @param string[] $args
 */
function url(string $url, array $args = []): string {
    // @todo $url is [0=>url, "a-attribute" => 'value'] | ['url'=>url, "a-attribute" => 'value']
    // @todo $url is '@XXX' << use URL-aliaser
    return $args ? $url.'?'.http_build_query($args) : $url;
}

 /**
  *  DEPERACATED:
  *   use $a ?: "default" instead
  *  oracle NVL - first non empty value | null
  *  returns first-empty value or last-argument
  *  nvl($a, $b, "default");
  *  nvl($a, $b, "0")        // return $a ? $a : ($b ? $b : "0");
  *
  * @param mixed $args
  */
 function nvl(...$args): mixed {
     // non-empty-value | last-argument
     if (\count($args) < 2) {
         throw new Exception('NVL(...) - 2+ args expected');
     }
     foreach ($args as $a) {
         if ($a) {
             return $a;
         }
         $l = $a;
     }

     return $l;
 }

/**
 * is value between $from .. $to (inclusive)
 *
 * @param mixed $v
 * @param mixed $from
 * @param mixed $to
 */
function between($v, $from, $to): bool {
    return $v >= $from && $v <= $to;
}

/**
 * benchmark function
 *
 * @param \Closure $fn        [description]
 * @param int      $seconds   [description]
 * @param mixed    $fn_params
 *
 * @return (float|int)[]
 *
 * @psalm-return array{'μs': float, count: 0|positive-int}
 */
function benchmark(\Closure $fn, $seconds = 3, $fn_params = []): array { // [$time_per_iteration, iterations]
    $start = microtime(true);
    $end = $start + $seconds;
    $cnt = 0;
    while (microtime(true) < $end) {
        $fn($fn_params);
        $cnt++;
    }
    $end = microtime(true);

    return ['μs' => round(($end - $start) / $cnt * 1000000, 1), 'count' => $cnt];
}

// is @method called
function isSuppressed(): bool {
    $t = error_reporting();
    // as of php8 default suppressed reporting value is
    //   E_USER_NOTICE | E_ERROR | E_WARNING | E_PARSE |  E_CORE_ERROR | E_CORE_WARNING | E_USER_DEPRECATED
    return 0 === $t || 4437 === $t;
}

/**
 * non recoverable Error -  developer uses Code Incorrect Way
 * throw \hb\Error exception if ...
 */
function error_if(mixed $boolean, string $message): void {
    if ($boolean) {
        throw new \hb\Error($message);  // \Error descendant
    }
}

/**
 * non recoverable Error -  developer uses Code Incorrect Way
 * throw \hb\Error exception if ...
 */
function error_unless(mixed $boolean, string $message): void {
    if (!$boolean) {
        throw new \hb\Error($message);  // \Error descendant
    }
}

/**
 * @return never
 */
function error(string $message): void {
    throw new \hb\Error($message);  // \Error descendant
}
