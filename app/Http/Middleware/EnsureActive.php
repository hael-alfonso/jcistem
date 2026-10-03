<?php
namespace App\Http\Middleware;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->status !== 'active') {
            $token = $request->session()->get('active_login_token');
            if ($request->user() && $token) User::whereKey($request->user()->id)
                ->where('active_login_token', $token)
                ->update(['active_login_token' => null, 'active_login_seen_at' => null]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Contact the chapter administrator.']);
        }
        $user = $request->user();
        if ($user->active_login_token) {
            $token = $request->session()->get('active_login_token');
            $existingSessionToken = hash('sha256', $request->session()->getId());
            if (!$token && hash_equals($user->active_login_token, $existingSessionToken)) {
                // Adopt an active session that predates the one-login rule.
                $token = $existingSessionToken;
                $request->session()->put('active_login_token', $token);
            }
            if ($token && hash_equals($user->active_login_token, $token)) {
                // Keep the claim alive while this account is being used.
                if (!$user->active_login_seen_at || $user->active_login_seen_at->lt(now()->subMinute())) {
                    $updated = User::whereKey($user->id)->where('active_login_token', $token)
                        ->update(['active_login_seen_at' => now()]);
                    if (!$updated) return $this->rejectDuplicateSession($request);
                }
            } elseif (!$token && $user->active_login_seen_at?->lte(now()->subMinutes(config('session.lifetime')))) {
                // A remembered device may return after its old session expired.
                $replacement = Str::random(64);
                $claimed = User::whereKey($user->id)
                    ->where('active_login_token', $user->active_login_token)
                    ->where('active_login_seen_at', '<=', now()->subMinutes(config('session.lifetime')))
                    ->update(['active_login_token' => $replacement, 'active_login_seen_at' => now()]);
                if ($claimed) $request->session()->put('active_login_token', $replacement);
                else return $this->rejectDuplicateSession($request);
            } else {
                return $this->rejectDuplicateSession($request);
            }
        }
        return $next($request);
    }

    private function rejectDuplicateSession(Request $request)
    {
        Auth::guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->withErrors(['email' => 'This account is already open in another session. Sign in there or wait for it to expire.']);
    }
}
