<?php

namespace xndbogdan\LaravelRayLegacy\Tests\Unit;

use Illuminate\Support\Facades\Log;
use xndbogdan\LaravelRayLegacy\Tests\TestCase;
use Spatie\Ray\Settings\Settings;

class LogTest extends TestCase
{
    /** @test */
    public function it_will_send_logs_to_ray_by_default()
    {
        Log::info('hey');

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_will_not_send_logs_to_ray_when_disabled()
    {
        app(Settings::class)->send_log_calls_to_ray = false;

        Log::info('hey');

        $this->assertCount(0, $this->client->sentRequests());
    }

    /** @test */
    public function it_will_not_send_logs_to_ray_when_log_is_null()
    {
        Log::info(null);

        $this->assertCount(0, $this->client->sentRequests());
    }
}
