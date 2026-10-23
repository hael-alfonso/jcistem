<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\JciPasswordRecoveryOtp;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\{Auth, DB, Hash, Password, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const RECOVERY_OTP_HASH = 'jci_recovery_otp_hash';
    private const RECOVERY_OTP_USER = 'jci_recovery_otp_user_id';
    private const RECOVERY_OTP_EMAIL = 'jci_recovery_otp_email';
    private const RECOVERY_OTP_SENT_AT = 'jci_recovery_otp_sent_at';
    private const RECOVERY_OTP_ATTEMPTS = 'jci_recovery_otp_attempts';
    private const RECOVERY_VERIFIED_USER = 'jci_recovery_verified_user_id';
    private const RECOVERY_VERIFIED_AT = 'jci_recovery_verified_at';

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (!$request->attributes->get('workspace')) return redirect()->route('scoped.login', ['workspace' => Str::uuid()]);
        if (Auth::check()) return redirect()->route('scoped.login', ['workspace' => Str::uuid()]);
        return view('auth.login');
    }

    public function showForgotPassword(Request $request): View|RedirectResponse
    {
        $this->clearRecovery($request);
        return view('auth.forgot-password');
    }

    public function sendRecoveryOtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:200']);
        $email = Str::lower(trim($data['email']));
        $request->session()->forget([self::RECOVERY_VERIFIED_USER, self::RECOVERY_VERIFIED_AT]);
        $user = User::query()->where('status', 'active')->whereRaw('LOWER(email) = ?', [$email])->first();
        $code = (string) random_int(100000, 999999);
        $request->session()->put([
            self::RECOVERY_OTP_HASH => Hash::make($code),
            self::RECOVERY_OTP_USER => $user?->id,
            self::RECOVERY_OTP_EMAIL => $data['email'],
            self::RECOVERY_OTP_SENT_AT => now()->timestamp,
            self::RECOVERY_OTP_ATTEMPTS => 0,
        ]);

        if ($user) {
            try { $user->notify(new JciPasswordRecoveryOtp($code)); }
            catch (\Throwable $exception) { report($exception); }
        }

        return redirect()->route('password.otp')->with('status', 'If the email matches an active account, a 6-digit code has been sent. The code expires in 10 minutes.');
    }

    public function showRecoveryOtp(Request $request): View|RedirectResponse
    {
        if (!$this->hasRecentOtp($request)) return redirect()->route('password.request')->withErrors(['email' => 'Start account recovery again to request a new code.']);
        return view('auth.forgot-password-otp', ['recoveryEmail' => $request->session()->get(self::RECOVERY_OTP_EMAIL)]);
    }

    public function verifyRecoveryOtp(Request $request): RedirectResponse
    {
        if (!$this->hasRecentOtp($request)) {
            $this->clearRecovery($request);
            return redirect()->route('password.request')->withErrors(['email' => 'This recovery code has expired. Start again to request a new one.']);
        }

        $data = $request->validate(['code' => 'required|digits:6']);
        $attempts = (int) $request->session()->get(self::RECOVERY_OTP_ATTEMPTS, 0);
        if ($attempts >= 5) {
            $this->clearRecovery($request);
            return redirect()->route('password.request')->withErrors(['email' => 'Too many code attempts. Start account recovery again.']);
        }
        $request->session()->put(self::RECOVERY_OTP_ATTEMPTS, $attempts + 1);
        $userId = $request->session()->get(self::RECOVERY_OTP_USER);
        $valid = Hash::check($data['code'], (string) $request->session()->get(self::RECOVERY_OTP_HASH, ''));
        if (!$valid || !$userId) return back()->withErrors(['code' => 'That code is incorrect or expired. Check your email or request a new code.']);

        $user = User::query()->whereKey($userId)->where('status', 'active')->first();
        if (!$user) {
            $this->clearRecovery($request);
            return redirect()->route('password.request')->withErrors(['email' => 'This account is unavailable. Contact your chapter administrator.']);
        }

        $request->session()->forget([self::RECOVERY_OTP_HASH, self::RECOVERY_OTP_USER, self::RECOVERY_OTP_EMAIL, self::RECOVERY_OTP_SENT_AT, self::RECOVERY_OTP_ATTEMPTS]);
        $request->session()->put([self::RECOVERY_VERIFIED_USER => $user->id, self::RECOVERY_VERIFIED_AT => now()->timestamp]);
        return redirect()->route('password.recovery.reset');
    }

    public function showRecoveryReset(Request $request): View|RedirectResponse
    {
        if (!$this->hasRecentOtpVerification($request)) {
            $this->clearRecovery($request);
            return redirect()->route('password.request')->withErrors(['email' => 'Verify a recovery code before choosing a new password.']);
        }
        return view('auth.forgot-password-reset');
    }

    public function updateRecoveryPassword(Request $request): RedirectResponse
    {
        if (!$this->hasRecentOtpVerification($request)) {
            $this->clearRecovery($request);
            return redirect()->route('password.request')->withErrors(['email' => 'Your recovery session expired. Start again to choose a new password.']);
        }

        $data = $request->validate(['password' => ['required', 'confirmed', PasswordRule::min(10)]]);
        $user = User::query()->whereKey($request->session()->get(self::RECOVERY_VERIFIED_USER))->where('status', 'active')->first();
        if (!$user) {
            $this->clearRecovery($request);
            return redirect()->route('password.request')->withErrors(['email' => 'This account is unavailable. Contact your chapter administrator.']);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
            'active_login_token' => null,
            'active_login_seen_at' => null,
        ])->save();
        event(new PasswordReset($user));
        $this->clearRecovery($request);
        $request->session()->regenerate();
        return redirect()->route('login')->with('status', 'Your password has been updated. Sign in using your username or email and new password.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => 'required|string', 'email' => 'required|email|max:200', 'password' => ['required', 'confirmed', PasswordRule::min(10)]]);
        $status = Password::reset($data + ['status' => 'active'], function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60), 'active_login_token' => null, 'active_login_seen_at' => null])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) return back()->withErrors(['email' => 'This reset link is invalid or expired. Request a new link.'])->onlyInput('email');
        return redirect()->route('login')->with('status', 'Your password has been updated. Sign in with your new password.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['login' => 'required|string|max:200', 'password' => 'required|string']);
        $identifier = Str::lower(trim($credentials['login']));
        $key = 'login:'.$identifier.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) return back()->withErrors(['login' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('login');

        $user = User::query()->where('status', 'active')->where(function ($query) use ($identifier) {
            $query->whereRaw('LOWER(email) = ?', [$identifier])->orWhereRaw('LOWER(username) = ?', [$identifier]);
        })->first();
        $valid = $user && Auth::validate(['email' => $user->email, 'password' => $credentials['password'], 'status' => 'active']);
        if (!$valid) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['login' => 'The username/email and password are incorrect or this account is inactive.'])->onlyInput('login');
        }

        $user = Auth::guard()->getLastAttempted();
        $token = Str::random(64);
        $claimed = DB::transaction(function () use ($user, $token, $credentials) {
            $account = User::whereKey($user->getAuthIdentifier())->lockForUpdate()->firstOrFail();
            $recentLogin = $account->active_login_seen_at?->gt(now()->subMinutes(config('session.lifetime')));
            if ($account->status !== 'active' || ($account->active_login_token && $recentLogin)) return null;
            $changes = ['active_login_token' => $token, 'active_login_seen_at' => now(), 'last_login_at' => now()];
            if (Hash::needsRehash($account->password)) $changes['password'] = Hash::make($credentials['password']);
            $account->forceFill($changes)->save();
            return $account;
        });
        if (!$claimed) return back()->withErrors(['login' => 'We could not sign you in on this device. Use Forgot password? to restore secure account access.'])->onlyInput('login');

        RateLimiter::clear($key);
        Auth::login($claimed, false);
        $request->session()->regenerate();
        $request->session()->put('active_login_token', $token);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = $request->session()->get('active_login_token');
        if ($token) User::whereKey(Auth::id())->where('active_login_token', $token)->update(['active_login_token' => null, 'active_login_seen_at' => null]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function hasRecentOtp(Request $request): bool
    {
        return $request->session()->has(self::RECOVERY_OTP_HASH)
            && (int) $request->session()->get(self::RECOVERY_OTP_SENT_AT, 0) >= now()->subMinutes(10)->timestamp;
    }

    private function hasRecentOtpVerification(Request $request): bool
    {
        return (bool) $request->session()->get(self::RECOVERY_VERIFIED_USER)
            && (int) $request->session()->get(self::RECOVERY_VERIFIED_AT, 0) >= now()->subMinutes(15)->timestamp;
    }

    private function clearRecovery(Request $request): void
    {
        $request->session()->forget([self::RECOVERY_OTP_HASH, self::RECOVERY_OTP_USER, self::RECOVERY_OTP_EMAIL, self::RECOVERY_OTP_SENT_AT, self::RECOVERY_OTP_ATTEMPTS, self::RECOVERY_VERIFIED_USER, self::RECOVERY_VERIFIED_AT]);
    }
}
