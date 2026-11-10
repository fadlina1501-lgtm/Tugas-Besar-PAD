<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $role = $credentials['role'];
        unset($credentials['role']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai.'])
                ->onlyInput('email', 'role');
        }

        if (Auth::user()->role !== $role) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['role' => 'Role yang dipilih tidak sesuai dengan akun ini.'])
                ->onlyInput('email', 'role');
        }

        $request->session()->regenerate();

        $landingRoute = match (Auth::user()->role) {
            'pengguna', 'sarpras' => 'reports.index',
            'teknisi' => 'work-orders.index',
            'manajemen' => 'dashboard',
            default => 'login',
        };

        return redirect()->route($landingRoute)
            ->with('success', 'Berhasil masuk ke sistem.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu telah keluar dari sistem.');
    }
}
