<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Tidies phone, CNIC, passport and email values before they are validated. */
class NormalizeInput
{
    public function handle(Request $request, Closure $next)
    {
        $request->merge($this->clean($request->all()));

        return $next($request);
    }

    protected function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->clean($value);
            } elseif (is_string($value)) {
                $data[$key] = match ($key) {
                    'email' => strtolower(trim($value)),
                    'passport_no' => strtoupper(preg_replace('/\s+/', '', $value)),
                    'phone', 'whatsapp' => $this->phones($value),
                    'cnic' => $this->cnic($value),
                    default => $value,
                };
            }
        }

        return $data;
    }

    /** Accepts one number, or two separated by a comma (agency phone). */
    protected function phones(string $v): string
    {
        return collect(explode(',', $v))->map(function ($p) {
            $d = preg_replace('/\D/', '', $p);

            return strlen($d) === 11 && str_starts_with($d, '03') ? substr($d, 0, 4).'-'.substr($d, 4) : trim($p);
        })->filter(fn ($p) => $p !== '')->implode(', ');
    }

    protected function cnic(string $v): string
    {
        $d = preg_replace('/\D/', '', $v);

        return strlen($d) === 13 ? substr($d, 0, 5).'-'.substr($d, 5, 7).'-'.substr($d, 12) : trim($v);
    }
}
