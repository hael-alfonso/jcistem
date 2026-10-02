<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Auth::extend('workspace', function ($app, $name, $config) {
            $guard = new \App\Support\WorkspaceGuard($name, $app['auth']->createUserProvider($config['provider']), $app['session.store']);
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app['request']);
            $app->refresh('request', $guard, 'setRequest');
            return $guard;
        });
    }
}
