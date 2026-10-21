<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Setting
{
    protected static ?array $cache = null;

    public static function all(): array
    {
        return self::$cache ??= DB::table('settings')->pluck('value', 'key')->all();
    }

    public static function get(string $key, $default = null)
    {
        $v = self::all()[$key] ?? null;

        return ($v === null || $v === '') ? $default : $v;
    }

    public static function set(string $key, $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }
}
