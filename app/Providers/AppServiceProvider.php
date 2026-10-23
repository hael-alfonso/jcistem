<?php

namespace App\Providers;

use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Events\DiagnosingHealth;
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
        Event::listen(DiagnosingHealth::class, function (): void {
            DB::select('select 1');
            if (!is_writable(storage_path('app/private'))) {
                throw new \RuntimeException('Private storage is not writable.');
            }
        });

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
