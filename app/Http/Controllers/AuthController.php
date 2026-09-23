<?php

namespace App\Http\Controllers;

use App\Support\WorkspaceNav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', [
            'accounts' => [
                ['role' => 'Admin', 'email' => 'admin@jcicarmona.org', 'password' => 'password', 'name' => 'Mich Alfonso'],
                ['role' => 'Treasurer', 'email' => 'treasurer@jcicarmona.org', 'password' => 'password', 'name' => 'Carlo Mendoza'],
                ['role' => 'Board of Directors', 'email' => 'bod@jcicarmona.org', 'password' => 'password', 'name' => 'Juan Dela Cruz'],
                ['role' => 'Member', 'email' => 'member@jcicarmona.org', 'password' => 'password', 'name' => 'Ana Cruz'],
            ],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'These credentials do not match a JCI Carmona test account.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route(WorkspaceNav::home(Auth::user()->role)));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
