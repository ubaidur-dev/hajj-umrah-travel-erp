<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * One search engine for every list in the system.
 *  - ignores dashes, spaces, dots, + and brackets  (0300-1234567 = 03001234567 = 0300 1234567)
 *  - ignores upper/lower case                       (ab1234567 = AB1234567)
 *  - every word must match somewhere                ("ali khan karachi")
 *  - words may be typed without gaps                ("ahmedali")
 *  - best matches come first: exact, then starts-with, then word-start, then the rest.
 */
class Search
{
    protected const SEPARATORS = ['-', ' ', '+', '.', '/', '(', ')', '_'];

    public static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($s));
    }

    /** SQL that lower-cases a column and removes the separators above. */
    public static function sqlNorm(string $col): string
    {
        $e = "LOWER($col)";
        foreach (self::SEPARATORS as $c) {
            $e = "REPLACE($e, '$c', '')";
        }

        return $e;
    }

    protected static function like(string $s): string
    {
        return '%'.addcslashes($s, '%_\\').'%';
    }

    /** Forms a person might type for the same phone number: 3001234567, 923001234567 -> 03001234567 */
    protected static function variants(string $token): array
    {
        $n = self::norm($token);
        $v = $n === '' ? [] : [$n];
        if (preg_match('/^92(3\d{2,})$/', $n, $m)) { $v[] = '0'.$m[1]; }
        if (preg_match('/^(3\d{4,})$/', $n)) { $v[] = '0'.$n; }

        return array_values(array_unique($v));
    }

    /** "(col matches token)" for a list of columns: returns [sql, bindings] */
    protected static function match(array $cols, string $token): array
    {
        $parts = []; $b = [];
        $norms = self::variants($token);
        foreach ($cols as $col) {
            $parts[] = "LOWER($col) LIKE ?"; $b[] = self::like(strtolower($token));
            foreach ($norms as $n) {
                $parts[] = self::sqlNorm($col).' LIKE ?'; $b[] = self::like($n);
            }
        }

        return ['('.implode(' OR ', $parts).')', $b];
    }

    protected static function matchWhole(array $cols, string $whole): array
    {
        $parts = []; $b = [];
        foreach ($cols as $col) { $parts[] = self::sqlNorm($col).' LIKE ?'; $b[] = self::like($whole); }

        return ['('.implode(' OR ', $parts).')', $b];
    }

    /**
     * @param  array<string>  $cols       columns of the main table to search
     * @param  array<string, array<string>>  $relations  relation => columns (dot notation allowed: 'booking.customer')
     * @param  array<string>  $rank       SQL expressions used to put the best matches first
     */
    public static function apply(Builder $q, ?string $term, array $cols, array $relations = [], array $rank = []): Builder
    {
        $term = trim(preg_replace('/\s+/', ' ', (string) $term));
        if ($term === '') {
            return $q;
        }
        $tokens = explode(' ', $term);
        $whole = self::norm($term);

        $q->where(function ($outer) use ($tokens, $cols, $relations, $whole) {
            // A) every word has to match one of the columns (or a related record)
            $outer->where(function ($all) use ($tokens, $cols, $relations) {
                foreach ($tokens as $t) {
                    $all->where(function ($one) use ($t, $cols, $relations) {
                        [$sql, $b] = self::match($cols, $t);
                        $one->orWhereRaw($sql, $b);
                        foreach ($relations as $rel => $rcols) {
                            $one->orWhereHas($rel, function ($r) use ($rcols, $t) {
                                [$s, $bb] = self::match($rcols, $t);
                                $r->whereRaw($s, $bb);
                            });
                        }
                    });
                }
            });
            // B) the whole text typed without gaps, e.g. "ahmedali"
            if (count($tokens) > 1 && $whole !== '') {
                $outer->orWhere(function ($w) use ($cols, $relations, $whole) {
                    [$sql, $b] = self::matchWhole($cols, $whole);
                    $w->orWhereRaw($sql, $b);
                    foreach ($relations as $rel => $rcols) {
                        $w->orWhereHas($rel, function ($r) use ($rcols, $whole) {
                            [$s, $bb] = self::matchWhole($rcols, $whole);
                            $r->whereRaw($s, $bb);
                        });
                    }
                });
            }
        });

        // best match first
        if ($rank && $whole !== '') {
            $exact = $prefix = $word = []; $b = [];
            foreach ($rank as $e) { $exact[] = self::sqlNorm($e).' = ?'; }
            foreach ($rank as $e) { $prefix[] = self::sqlNorm($e).' LIKE ?'; }
            foreach ($rank as $e) { $word[] = "LOWER($e) LIKE ?"; }
            $bind = array_merge(
                array_fill(0, count($rank), $whole),
                array_fill(0, count($rank), addcslashes($whole, '%_\\').'%'),
                array_fill(0, count($rank), '% '.addcslashes(strtolower($term), '%_\\').'%'),
            );
            $q->reorder()->orderByRaw(
                'CASE WHEN ('.implode(' OR ', $exact).') THEN 0 WHEN ('.implode(' OR ', $prefix).') THEN 1 WHEN ('.implode(' OR ', $word).') THEN 2 ELSE 3 END',
                $bind
            );
        }

        return $q;
    }
}
