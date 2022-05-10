<?php

declare(strict_types=1);

/*
 * This file is part of Homebase 2 PHP Framework - https://github.com/homebase/hb-core
 */

namespace hb;

/**
 * @todo  - create BinaryStr and/or ASCII Class - unicode methods are SLOW
 */

/**
 * UTF8-compatible string functions - part of Homebase 2 Framework
 * similar ~ https://laravel.com/api/master/Illuminate/Support/Str.html
 *
 * @todo  we'll copy/reimplement some functions from there (done for most important ones)
 * @todo  we'll forward unknown calls to illuminate framework or https://github.com/danielstjules/Stringy
 *
 * IMPORTANT !!!
 * $s - HAYSTACK  << ALL Str methods receive (string)haystack as first parameter
 */
class Str {
    // @todo: (methods to add)
    //  plural, singular
    //

    /**
     * is string starts with prefix? (prefixes)
     *
     * @param string|string[] $prefixes
     */
    static function startsWith(string $s, string|array $prefixes): bool {
        foreach ((array) $prefixes as $needle) {
            if ('' !== $needle && mb_substr($s, 0, mb_strlen($needle)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a prefix if it is not already there
     */
    static function start(string $s, string $prefix): string {
        if (self::startsWith($s, $prefix)) {
            return $s;
        }

        return $prefix.$s;
    }

    /**
     * Add a suffix if it is not already there
     */
    static function finish(string $s, string $suffix): string {
        if (self::endsWith($s, $suffix)) {
            return $s;
        }

        return $s.$suffix;
    }

    /**
     * is string ends with suffix? (suffixes)
     *
     * @param string|scalar[] $suffixes
     */
    static function endsWith(string $s, string|array $suffixes): bool {
        foreach ((array) $suffixes as $needle) {
            if (mb_substr($s, -mb_strlen((string)$needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * string before substring (first occurence)
     */
    static function before(string $s, string $search): string {
        $p = mb_strpos($s, $search);

        return $p ? mb_substr($s, 0, $p) : '';
    }

    /**
     * string after substring (first occurence)
     */
    static function after(string $s, string $search): string {
        $p = mb_strpos($s, $search);

        return $p ? mb_substr($s, $p + mb_strlen($search)) : '';
    }

    /**
     * string After last occurence of $search
     */
    static function afterLast(string $s, string $search): string {
        $p = mb_strrpos($s, $search);

        return $p ? mb_substr($s, $p + mb_strlen($search)) : '';
    }

    /**
     * string Before last occurence of $search
     */
    static function beforeLast(string $s, string $search): string {
        $p = mb_strrpos($s, $search);

        return $p ? mb_substr($s, 0, $p) : '';
    }

    /**
     * "...($from)(..extracted..)($to)..."
     * trailing spaces removed
     */
    static function between(string $s, string $from, string $to): string|bool|null {
        // "text", "", false (NO $from), null (NO $to)
        $f = mb_strpos($s, $from);
        if (false === $f) {
            return false;
        }
        $f += mb_strlen($from);
        $t = mb_strpos($s, $to, $f);
        if (false === $t) {
            return null;
        }

        return trim(mb_substr($s, $f, $t - $f));
    }

    /**
     * Convert the given string to lower-case.
     */
    static function lower(string $s): string {
        return mb_strtolower($s, 'UTF-8');
    }

    /**
     * Limit the number of characters in a string.
     *
     * alias of "cut"
     */
    static function limit(string $s, int $limit = 100, string $end = '...'): string {
        if (mb_strlen($s) <= $limit) {
            return $s;
        }
        error_if(mb_strlen($end) >= $limit, "useless Str::limit('', limit, end) combination");

        return mb_strimwidth($s, 0, $limit, $end);
    }

    /**
     * substring (unicode)
     */
    static function substr(string $string, int $start, int $length = null): string {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Convert the given string to upper-case.
     */
    static function upper(string $s): string {
        return mb_strtoupper($s, 'UTF-8');
    }

    /**
     * Convert the given string to title case.
     */
    static function title(string $s): string {
        return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
    }

    static function ucfirst(string $s): string {
        return static::upper(static::substr($s, 0, 1)).static::substr($s, 1);
    }

    /**
     * kebab case. "Word Word  Word" =>  "word-word-word"
     * multiple spaces converted to one
     */
    static function kebab(string $s): string {
        return static::snake($s, '-');
    }

    /**
     * snake case.  "word word  word" =>  "word_word_word"
     * multiple spaces converted to one
     */
    static function snake(string $s, string $delimiter = '_'): string {
        if (ctype_lower($s)) {
            return $s;
        }
        $s = preg_replace('/\s+/u', '', ucwords($s));

        return static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $s));
    }

    /**
     * studly case.  " aaa-bbb_ccc ddd" =>  "AaaBbbCccDdd"
     * multiple spaces converted to one
     */
    static function studly(string $s): string {
        $s = ucwords(str_replace(['-', '_'], ' ', $s));

        return str_replace(' ', '', $s);
    }

    /**
     * nn utf characters
     */
    static function len(string $s): int {
        return mb_strlen($s);
    }

    /**
     * Return the length of the given string.
     *
     * @param string $encoding
     */
    static function length(string $s, $encoding = null): int {
        if ($encoding) {
            return mb_strlen($s, $encoding);
        }

        return mb_strlen($s);
    }

    /**
     * Determine if a given string contains a given substring. (at least one of them)
     *
     * @param array<string>|string $needles - substring or array of substrings
     */
    static function contains(string $s, array|string $needles): bool {
        foreach ((array) $needles as $needle) {
            if ('' !== $needle && false !== mb_strpos($s, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string contains ALL given substrings
     *
     * @param array<string> $needles - substrings
     */
    static function containsAll(string $s, array $needles): bool {
        foreach ($needles as $needle) {
            if ('' !== $needle && false === mb_strpos($s, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * is string matches a given pattern(s).
     * NOTE: "*" converted to ".*"
     *
     * @param array<string>|string $patterns - substrings
     */
    // ~ laravel compatible, args order corrected
    static function is(string $s, string|array $patterns): bool {
        if (!$patterns) {
            return false;
        }
        foreach ((array) $patterns as $pattern) {
            if ($pattern === $s) {
                return true;
            }
            $pattern = preg_quote($pattern, '#');
            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (1 === preg_match('#^'.$pattern.'\z#u', $s)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limit the number of words in a string.
     *
     * @param int    $words
     * @param string $end
     *
     * @return string
     */
    static function words(string $s, $words = 100, $end = '...') {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $s, $matches);
        if (!isset($matches[0]) || static::length($s) === static::length($matches[0])) {
            return $s;
        }

        return rtrim($matches[0]).$end;
    }

    /**
     * Generate "random" alpha-numeric string.
     */
    static function random(int $length = 16): string {
        $string = '';
        while (($len = \strlen($string)) < $length) {
            /** @var positive-int $size */
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    //
    // ----- LARAVEL ALIKE - ARG ORDER is DIFFERENT
    //

    /**
     * Replace the first occurrence of a given value in the string.
     * !!! ORDER IS DIFFERENT than Laravel's
     */
    static function replaceFirst(string $s, string $search, string $replace): string {
        if ('' === $search) {
            return $s;
        }
        $position = mb_strpos($s, $search);
        if (false !== $position) {
            return mb_substr($s, 0, $position).$replace.mb_substr($s, $position + mb_strlen($search));
        }

        return $s;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     * !!! ORDER IS DIFFERENT than Laravel's*
     *
     * @return string
     */
    static function replaceLast(string $s, string $search, string $replace) {
        $position = mb_strrpos($s, $search);
        if (false !== $position) {
            return mb_substr($s, 0, $position).$replace.mb_substr($s, $position + mb_strlen($search));
        }

        return $s;
    }

    /**
     * Replace first occurence of $search with $replacements[0], next with $replacements[1] and so on
     * if we run out of replacements, $search kept as is
     * Ex: > hb\Str::replaceArray("start item_1 [] item_2 [] item_3 [] item_4 [] end", '[]', [1,2,3,4,5])
     *     "start item_1 1 item_2 2 item_3 3 item_4 4 end"
     *
     * @param non-empty-string $search
     * @param array<string>    $replacements
     */
    static function replaceArray(string $s, string $search, array $replacements): string {
        $rcnt = \count($replacements);
        $chunks = explode($search, $s, $rcnt + 1);
        $rcnt = min($rcnt, \count($chunks) - 1);
        $r = [];
        foreach ($chunks as $k => $c) {
            $r[] = $c;
            if ($k < $rcnt) {
                $r[] = $replacements[$k];
            }
        }

        return implode('', $r);
    }

    // OLD HB framework methods:
    //

    // First Match
    static function fm(string $s, string $regexp): string {
        // First Match !!! ORDER IS DIFFERENT than HB1
        preg_match($regexp, $s, $m);

        return $m[1] ?? '';
    }

    /**
     * conditional sprintf
     * cs($a,"A=%s")   is kinda the same as $a ? sprintf("A=%s", $a) : "";   (plus x2s is applied for non scalars)
     *
     * @param mixed $s
     */
    static function cs(mixed $s, string $fmt_true, string $fmt_false = ''): string {
        // !!! ORDER IS DIFFERENT than HB1
        if ($s) {
            return sprintf($fmt_true, \is_scalar($s) ? (string) $s : \hb\x2s($s));
        }

        return $fmt_false ? sprintf($fmt_false, \is_scalar($s) ? (string) $s : \hb\x2s($s)) : '';
    }

    /**
     * remove binary symbols from string
     */
    static function stripBinary(string $s): string {
        // printable characters
        return preg_replace('/[^[:print:]]/', '', $s);
    }

    /**
     * limit string size to $limit, replace exceess with $end="..."
     */
    public static function cut(string $s, int $limit = 100, string $end = '...'): string {
        if (mb_strlen($s) <= $limit) {
            return $s;
        }

        return mb_strimwidth($s, 0, $limit, $end);
    }

    /**
     * cut Long (lenght>$len) string into 3 pieces, remove non-printable characters
     * len(pre_cut) + len(post_cut) == $len
     * $cut part is limited to $cut_len
     * [pre_cut, cut, post_cut]  << [0..$at][$at...][remaning]
     *
     * @param int $at      start-cut-offset
     * @param int $len     cut-string-min-size, lines shorter than $len kept as is
     * @param int $cut_len middle-part max-length
     *
     * @return array<string>|string "original-string" | [pre_cut, cut, post_cut]
     *
     * Ex:
     *   $r = hb\Str::cutAt("123456790ABCD123456790BB12345670CCC123456790DDD1234567901234567890XXX1234", 30, 12, 25);
     *   echo is_array($r) ? "$r[0]<abbr title=\"$r[1]\">...</abbr>$r[2]" : $r;  // no escaping for clarity`s sake
     */
    static function cutAt(string $s, int $len = 60, int $at = 20, int $cut_len = 0): array|string {
        // "Original String" | [pre, cutted, post]
        error_if($at > $len, "CutAt prefix position can't exceed expected length");
        $s = preg_replace('/[^[:print:]]/', '.', $s); // replace non printable with "."
        $s = preg_replace('/\.\.+/', '..', $s); // multiple dots with 2 dots
        $l = \strlen($s);
        if ($l <= $len) {
            return $s;
        }
        $to_cut = $l - $len;
        if (0 === $cut_len) {
            $cut = '...';
        } else {
            $cut = substr($s, $at, $to_cut);
            if (\strlen($cut) > $cut_len) {
                $hcl = $cut_len >> 1; // half-cut-len
                $cut = substr($cut, 0, $hcl).'...'.substr($cut, -$hcl);
            }
            // $cut = Str::cut($cut, $cut_len); // cut cutted part even more if it is long
            $cut = mb_strimwidth($cut, 0, $cut_len, '...');
        }

        return [substr($s, 0, $at), $cut, substr($s, $at + $to_cut)];
    }

    /**
     * have full-word-substring(s) in string(s) - case sensitive (default)
     * ala: grep -wi
     * @param string|string[] $str
     * @param string|string[] $substring
     */
    static function haveSubstring(string|array $str, string|array $substring , bool $caseSensitive=true): bool {
        if (\is_array($str)) {
            foreach ($str as $s) {
                if (self::haveSubstring($s, $substring)) {
                    return true;
                }
            }

            return false;
        }
        if (\is_array($substring)) {
            foreach ($substring as $ss) {
                if (self::haveSubstring($str, $ss)) {
                    return true;
                }
            }

            return false;
        }

        return (bool) preg_match('!\\b\\Q'.$substring.'\\E\\b!'.($caseSensitive?"":"i"), $str);
    }

    /**
     * Split line into Lexemes - O(n) one pass line parser
     *
     * split line into array of items
     * - trailing spaces ignored
     * - quotes "x" & 'x' & `x` supported
     * - brackets: () {} [] supported
     *
     * Default delimiter is space(" ")
     * When delimiter is space:
     *   multiple spaces (delimiters) treated as one, trailing spaces ignored:
     *     parseLine("  a  b ' c ' ") >> ["a","b", "' c '"]
     * when delimiter is not a space:
     *   all lexeme trailing spaces are trimmed
     *   multiple delimiters treated as set of empty lexems:
     *     parseLine("a,,b", ",") >> ["a", "", "b"]
     *     parseLine(",a,,b,", ",") >> ["","a", "", "b",""]
     *
     * @test: core/ParseLine.stest   # see examples there
     *
     * @return array<string> *
     */
    static function parseLine(string $s, string $delimiter = ' ', int $keep_escape_character = 1): array {
        return \hbc\core\StrX::parseLine($s, $delimiter, $keep_escape_character);
    }

    /**
     * Generate a URL friendly "slug" from a given string.  ~ copied from laravel
     *
     */
    static function slug(string $s, string $separator = '-', bool $toAscii = true): string {
        if ($toAscii) {
            $s = static::ascii($s);
        }
        // Convert all dashes/underscores into separator
        $flip = '-' === $separator ? '_' : '-';
        $s = preg_replace('!['.preg_quote($flip).']+!u', $separator, $s);
        // Replace @ with the word 'at'
        $s = str_replace('@', $separator.'at'.$separator, $s);
        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $s = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', static::lower($s));
        // Replace all separator characters and whitespace by a single separator
        $s = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $s);

        return trim($s, $separator);
    }

    /**
     * Generate a URL from string == slug method
     */
    static function url(string $s, string $separator = '-', bool $toAscii = true): string {
        return self::slug($s, $separator, $toAscii);
    }

    /**
     * convert UTF8 / anything to ASCII
     * ~= laravel one = SLOW
     * > pe 'echo x2s( HB::benchmark( function() {\hb\Str::ASCII(\'Using strings like fòô bàř\');}) )'
     *   μs=3.3
     * > pe 'echo x2s( HB::benchmark( function() {\hb\Str::_ASCII_LARAVEL_ALIKE(\'Using strings like fòô bàř\');}) )'
     *   μs=19.2     << almost 6 times slower
     */
    static function _ascii_(string $s): string {
        $s2 = preg_replace('/[^\x20-\x7E]/u', '', $s);
        if ($s2 === $s) {
            return $s2;
        }
        // HORRIBLE overhead - find a way to speed up
        // try strtr (prepared map)
        foreach (\hbc\core\StrX::charsArray() as $k => $v) {
            $s = str_replace($v, $k, $s);
        }

        return preg_replace('/[^\x20-\x7E]/u', '', $s);
    }

    /**
     * convert UTF8 / anything to ASCII
     */
    static function ascii(string $s): string {
        $s2 = preg_replace('/[^\x20-\x7E]/u', '', $s);
        if ($s2 === $s) {
            return $s; // already ascii
        }
        static $map = [];
        if (!$map) {
            $map = self::_utf2AsciiMap();
        }
        $s = strtr($s, $map);

        return preg_replace('/[^\x20-\x7E]/u', '', $s);
    }

    /**
     * ATTN: NON CACHED - cache it in static php variable
     *
     * @return array<string> UTF8 => Ascii conversion map
     */
    static function _utf2AsciiMap(): array {
        $map = [];
        foreach (\hbc\core\StrX::charsArray() as $to => $from) {
            foreach ($from as $f) {
                $map[$f] = $to;
            }
        }

        return $map;
    }
}
