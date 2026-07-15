<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.index');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $validUser = hash_equals(
            config('app.admin_username'),
            $request->input('username')
        );
        $validPass = hash_equals(
            config('app.admin_password'),
            $request->input('password')
        );

        if ($validUser && $validPass) {
            $request->session()->regenerate();
            session(['admin_logged_in' => true]);
            return redirect()->route('admin.index');
        }

        return back()->withErrors(['credentials' => 'Invalid username or password.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
