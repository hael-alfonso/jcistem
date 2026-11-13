<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $route = $request->route()?->getName();
        if (!$request->user() || !$route || $response->getStatusCode() >= 400
            || ($request->isMethod('GET') && $response->getStatusCode() >= 300)
            || $request->attributes->get('chapter_domain_audited')) return $response;

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $request->isMethod('GET') ? 'workspace_page_opened' : 'workspace_request_submitted',
            'object_type' => 'Workspace',
            'object_id' => null,
            'before' => [],
            'after' => [],
            'remarks' => ($request->isMethod('GET') ? 'Opened ' : 'Submitted ').Str::headline($route).'.',
        ]);

        return $response;
    }
}
