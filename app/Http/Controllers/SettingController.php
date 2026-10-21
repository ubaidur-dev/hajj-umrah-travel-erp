<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pk;
use App\Support\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public const KEYS = ['agency_name', 'agency_tagline', 'owner_name', 'facebook', 'phone', 'whatsapp', 'email', 'address', 'tax_rate', 'invoice_terms', 'bank_details'];

    public function edit()
    {
        return view('settings.edit', ['s' => Setting::all(), 'users' => User::orderBy('name')->get(), 'logoMedia' => \App\Models\AgencyProfile::current()->getMedia('logo')]);
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'agency_name' => 'required|string|max:150', 'agency_tagline' => 'nullable|string|max:200', 'owner_name' => 'nullable|string|max:100', 'facebook' => 'nullable|url|max:255', 'phone' => ['nullable', 'string', 'max:40', function ($attr, $value, $fail) {
                $parts = array_filter(array_map('trim', explode(',', (string) $value)));
                if (count($parts) > 2 || collect($parts)->contains(fn ($p) => ! preg_match(Pk::PHONE, $p))) {
                    $fail('Enter up to two mobile numbers like 0341-2185055, separated by a comma.');
                }
            }],
            'whatsapp' => Pk::phone(), 'email' => Pk::email(), 'address' => 'nullable|string', 
            'tax_rate' => 'nullable|numeric|min:0|max:100', 'invoice_terms' => 'nullable|string', 'bank_details' => 'nullable|string',
        ], Pk::messages());
        foreach (self::KEYS as $k) {
            Setting::set($k, $data[$k] ?? null);
        }

        return back()->with('ok', 'Settings saved.');
    }
}
