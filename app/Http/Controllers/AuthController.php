<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, RateLimiter};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (!$request->attributes->get('workspace')) {
            return redirect()->route('scoped.login', ['workspace' => Str::uuid()]);
        }
        // A login link opened from an existing workspace is a request for a new
        // sign-in context, even when that workspace already has an account.
        if (Auth::check()) return redirect()->route('scoped.login', ['workspace' => Str::uuid()]);
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => 'required|email|max:200', 'password' => 'required|string']);
        $key = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) return back()->withErrors(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        if (!Auth::validate($credentials + ['status' => 'active'])) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'The sign-in details are incorrect or this account is inactive.'])->onlyInput('email');
        }
        $user = Auth::guard()->getLastAttempted();
        $token = Str::random(64);
        $claimed = DB::transaction(function () use ($user, $token, $credentials) {
            $account = User::whereKey($user->getAuthIdentifier())->lockForUpdate()->firstOrFail();
            $recentLogin = $account->active_login_seen_at?->gt(now()->subMinutes(config('session.lifetime')));
            if ($account->status !== 'active' || ($account->active_login_token && $recentLogin)) return null;

            $changes = [
                'active_login_token' => $token,
                'active_login_seen_at' => now(),
                'last_login_at' => now(),
            ];
            if (Hash::needsRehash($account->password)) $changes['password'] = Hash::make($credentials['password']);
            $account->forceFill($changes)->save();

            return $account;
        });
        if (!$claimed) {
            return back()->withErrors(['email' => 'This account is already signed in. Log out from its current session or wait for it to expire.'])->onlyInput('email');
        }
        RateLimiter::clear($key);
        Auth::login($claimed, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('active_login_token', $token);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->session()->get('active_login_token');
        if ($token) User::whereKey(Auth::id())->where('active_login_token', $token)
            ->update(['active_login_token' => null, 'active_login_seen_at' => null]);
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
