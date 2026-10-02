<?php

use App\Http\Middleware\EnsureRole;
use App\Support\WorkspaceNav;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', \App\Http\Middleware\WorkspaceSession::class);
        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => \App\Http\Middleware\EnsureActive::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception, \Illuminate\Http\Request $request) {
            if ($exception->getStatusCode() !== 419 || $request->expectsJson()) return null;
            $workspace = $request->attributes->get('workspace');
            $login = $workspace ? '/workspaces/'.$workspace.'/login' : '/login';
            return redirect()->to($login)->withErrors(['email' => 'Your session expired. Please sign in again.']);
        });
    })->create();
