<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_disabled_without_a_configured_secret(): void
    {
        config(['services.cron.secret' => null]);

        $this->getJson('/api/v1/cron/schedule', ['Authorization' => 'Bearer '])->assertUnauthorized();
    }

    public function test_it_rejects_a_wrong_secret(): void
    {
        config(['services.cron.secret' => 'expected-secret']);

        $this->getJson('/api/v1/cron/schedule')->assertUnauthorized();
        $this->getJson('/api/v1/cron/schedule', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
    }

    public function test_it_runs_the_scheduler_with_the_secret(): void
    {
        config(['services.cron.secret' => 'expected-secret']);

        $this->getJson('/api/v1/cron/schedule', ['Authorization' => 'Bearer expected-secret'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
