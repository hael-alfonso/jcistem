<?php

namespace App\Providers;

use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
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
        foreach ([
            Permissions::AUDIT_VIEW,
            Permissions::MEMBERS_MANAGE,
            Permissions::FINANCE_MANAGE,
            Permissions::CONCEPT_REVIEW,
            Permissions::PROPOSAL_REVIEW,
            Permissions::PROJECT_COMPLETE,
            Permissions::PROJECT_ARCHIVE,
            Permissions::CALENDAR_GLOBAL,
            Permissions::RECORDS_REVIEW,
            Permissions::INQUIRIES_MANAGE,
        ] as $permission) {
            Gate::define($permission, fn ($user) => Permissions::allows($user, $permission));
        }

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
