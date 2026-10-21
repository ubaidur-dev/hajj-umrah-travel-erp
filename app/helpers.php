<?php

if (! function_exists('money')) {
    function money($v): string
    {
        return \App\Support\Num::money($v);
    }
}

if (! function_exists('dt')) {
    function dt($d, string $f = 'd M Y'): string
    {
        return $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
    }
}

if (! function_exists('wa_link')) {
    /** WhatsApp click-to-chat link; Pakistani numbers (03xx...) become 923xx... */
    function wa_link(?string $phone, string $text = ''): ?string
    {
        $n = preg_replace('/\D/', '', (string) $phone);
        if ($n === '') {
            return null;
        }
        if (str_starts_with($n, '00')) {
            $n = substr($n, 2);
        } elseif (str_starts_with($n, '0')) {
            $n = '92'.substr($n, 1);
        }

        return 'https://wa.me/'.$n.($text ? '?text='.rawurlencode($text) : '');
    }
}

if (! function_exists('agency_logo_url')) {
    /** Logo uploaded in Settings (Spatie Media Library) or the default Gulshan-e-Harmain logo. */
    function agency_logo_url(): string
    {
        static $url;

        return $url ??= (\App\Models\AgencyProfile::current()->getFirstMediaUrl('logo') ?: asset('images/logo.png'));
    }
}

if (! function_exists('agency_logo_path')) {
    function agency_logo_path(): string
    {
        $m = \App\Models\AgencyProfile::current()->getFirstMedia('logo');

        return $m && is_file($m->getPath()) ? $m->getPath() : public_path('images/logo.png');
    }
}
