<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View { return view('auth.login'); }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => 'required|email|max:200', 'password' => 'required|string']);
        $key = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) return back()->withErrors(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        if (!Auth::attempt($credentials + ['status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'The sign-in details are incorrect or this account is inactive.'])->onlyInput('email');
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        User::whereKey(Auth::id())->update(['last_login_at' => now()]);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
