<?php

namespace App\Support\Reports;

/** Puts each major clause of a generated query on its own line so it reads well in the "show SQL" panel. */
final class SqlFormatter
{
    private const BREAK_BEFORE = ['from', 'inner join', 'left join', 'where', 'group by', 'having', 'order by', 'limit'];

    public static function format(string $sql): string
    {
        $pattern = '/\s+('.implode('|', array_map(fn ($k) => str_replace(' ', '\s+', $k), self::BREAK_BEFORE)).')\s+/i';

        $sql = preg_replace('/\bselect\b/i', 'SELECT', $sql);

        return trim(preg_replace_callback($pattern, fn ($m) => "\n".strtoupper(preg_replace('/\s+/', ' ', $m[1])).' ', $sql));
    }
}
