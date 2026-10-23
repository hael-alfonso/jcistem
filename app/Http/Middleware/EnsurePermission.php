<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! Permissions::allows($user, $permission)) {
            Log::warning('Authorization denied', [
                'user_id' => $user?->id,
                'permission' => $permission,
                'route' => $request->route()?->getName(),
            ]);

            abort(403, 'This action is not available for your account.');
        }

        return $next($request);
    }
}
