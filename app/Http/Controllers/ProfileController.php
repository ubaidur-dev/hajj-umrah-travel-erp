<?php

namespace App\Http\Controllers;

use App\Support\Pk;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit() { return view('profile.edit', ['user' => auth()->user()]); }

    public function update(Request $r)
    {
        $user = $r->user();
        $d = $r->validate([
            'name' => 'required|string|max:100', 'phone' => Pk::phone(),
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => 'nullable|min:6|confirmed',
        ], Pk::messages());
        $user->name = $d['name'];
        $user->phone = $d['phone'] ?? null;
        if (! empty($d['password'])) {
            $user->password = $d['password'];
        }
        $user->save();

        return back()->with('ok', 'Profile saved.');
    }
}
