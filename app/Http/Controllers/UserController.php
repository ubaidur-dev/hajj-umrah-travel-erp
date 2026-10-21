<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => array_merge(Pk::email(true), ['unique:users']), 'password' => 'required|min:6', 'role' => 'required|in:admin,staff', 'phone' => Pk::phone()], Pk::messages());
        User::create($d);

        return back()->with('ok', 'User ban gaya.');
    }

    public function update(Request $r, User $user)
    {
        $d = $r->validate(['name' => 'required|string|max:100', 'role' => 'required|in:admin,staff', 'password' => 'nullable|min:6']);
        $user->fill(['name' => $d['name'], 'role' => $d['role'], 'is_active' => $r->boolean('is_active')]);
        if (! empty($d['password'])) {
            $user->password = $d['password'];
        }
        $user->save();

        return back()->with('ok', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('err', 'You cannot delete your own account.');
        }
        $user->delete();

        return back()->with('ok', 'User deleted.');
    }
}
