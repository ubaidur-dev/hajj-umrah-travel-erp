<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Num
{
    /** Next sequential number like BK-2026-0001 (per year). */
    public static function next(string $prefix, string $table, string $column): string
    {
        $year = now()->format('Y');
        $like = "$prefix-$year-%";
        $last = DB::table($table)->where($column, 'like', $like)->orderByDesc($column)->value($column);
        $n = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $n);
    }

    public static function money($v): string
    {
        return 'PKR '.number_format((float) $v, 0);
    }
}
