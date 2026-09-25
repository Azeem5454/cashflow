<?php

namespace App\Support;

/**
 * Builds LIKE patterns for case-insensitive partial search.
 *
 * The escape character is '!' and NOT a backslash, deliberately.
 *
 * PHP 8.3's PDO parser reads the `\'` inside `ESCAPE '\'` as an escaped
 * quote, concludes the string literal is still open, and then miscounts every
 * `?` placeholder that follows — the query dies with
 * SQLSTATE[HY093]: Invalid parameter number. PHP 8.4 rewrote that parser to
 * respect PostgreSQL's standard-conforming strings, so the same code works on
 * a modern local machine and fails on a production runtime pinned to 8.3.
 *
 * That combination cost an afternoon of 500s on the live API, so the escape
 * character lives here and every LIKE search uses it.
 */
final class LikeSearch
{
    /** Needs no escaping in any SQL string literal. */
    public const ESCAPE = '!';

    /** SQL fragment to append after a LIKE placeholder. */
    public const CLAUSE = "ESCAPE '" . self::ESCAPE . "'";

    /**
     * A %contains% pattern with LIKE wildcards in the user's term neutralised,
     * lower-cased to pair with LOWER(column).
     */
    public static function contains(string $term): string
    {
        return '%' . self::escape(mb_strtolower($term)) . '%';
    }

    /** Escape the wildcards and the escape character itself. */
    public static function escape(string $term): string
    {
        $e = self::ESCAPE;

        return str_replace([$e, '%', '_'], [$e . $e, $e . '%', $e . '_'], $term);
    }
}
