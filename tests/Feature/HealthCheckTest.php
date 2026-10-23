<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
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
}
