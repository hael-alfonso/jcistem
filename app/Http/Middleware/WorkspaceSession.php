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
        $sessionName = $cookie;
        if ($workspace) {
            $request->route()->forgetParameter('workspace');
            $action = $request->route()->getAction();
            $action['as'] = preg_replace('/^scoped\./', '', $action['as'] ?? '');
            $request->route()->setAction($action);
            $request->attributes->set('workspace', $workspace);
            $sessionName = $cookie.'_'.$workspace;
            config(['session.cookie' => $sessionName]);
        }
        // The session manager may already have created its store (for example in a
        // long-running worker). StartSession reads the store name, not this config key.
        $session = app('session')->driver();
        $previousSessionName = $session->getName();
        $session->setName($sessionName);
        // A reused store retains in-memory attributes between requests. Clear them
        // before StartSession loads the data for this workspace's cookie.
        $session->flush();
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
            $session->setName($previousSessionName);
            config(['session.cookie' => $cookie]);
            URL::formatPathUsing(fn ($path) => $path);
        }
    }
}
