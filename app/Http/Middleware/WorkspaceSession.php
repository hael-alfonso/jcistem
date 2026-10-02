<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class WorkspaceSession
{
    public function handle(Request $request, Closure $next)
    {
        $workspace = $request->route('workspace');
        $cookie = config('session.cookie');
        if ($workspace) {
            $request->route()->forgetParameter('workspace');
            $action = $request->route()->getAction();
            $action['as'] = preg_replace('/^scoped\./', '', $action['as'] ?? '');
            $request->route()->setAction($action);
            $request->attributes->set('workspace', $workspace);
            config(['session.cookie' => $cookie.'_'.$workspace]);
        }
        // Route URLs keep the current workspace, including form actions and pagination.
        URL::formatPathUsing(function ($path) use ($workspace) {
            if (!$workspace || preg_match('#^/workspaces/[a-f0-9-]{36}(?:/|$)#', $path)) return $path;
            return '/workspaces/'.$workspace.'/'.ltrim($path, '/');
        });
        try {
            $response = $next($request);
            if ($request->user()) $response->headers->set('Cache-Control', 'no-store, private');
            return $response;
        } finally {
            config(['session.cookie' => $cookie]);
            URL::formatPathUsing(fn ($path) => $path);
        }
    }
}
