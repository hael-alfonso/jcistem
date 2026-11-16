<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_checks_database_and_private_storage(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_health_endpoint_fails_when_database_is_unavailable(): void
    {
        config()->set('app.debug', false);
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', storage_path('framework/testing/missing-health-database.sqlite'));
        DB::purge('sqlite');

        $this->get('/up')->assertStatus(500);
    }

    public function test_database_connection_failure_shows_a_safe_retry_page(): void
    {
        Route::get('/simulate-database-outage', function () {
            throw new QueryException('mysql', 'select 1', [], new \PDOException('Connection refused', 2002));
        });

        $this->get('/simulate-database-outage')->assertStatus(503)
            ->assertHeader('Retry-After', '30')
            ->assertSee('temporarily unavailable')
            ->assertDontSee('Connection refused');

        $this->getJson('/simulate-database-outage')->assertStatus(503)
            ->assertHeader('Retry-After', '30')
            ->assertJsonPath('message', 'The database is temporarily unavailable. Please try again shortly.');
    }

    public function test_session_database_outage_is_handled_without_a_debug_trace(): void
    {
        config()->set('session.driver', 'database');
        app('session')->forgetDrivers();
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.host', '127.0.0.1');
        config()->set('database.connections.mysql.port', 65535);
        DB::purge('mysql');

        $this->get('/')->assertStatus(503)
            ->assertSee('temporarily unavailable')
            ->assertDontSee('Illuminate\\Database\\QueryException');
    }
}
