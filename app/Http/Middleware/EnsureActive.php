<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Contact the chapter administrator.']);
        }
        return $next($request);
    }
}
