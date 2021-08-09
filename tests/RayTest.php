<?php

namespace xndbogdan\LaravelRayLegacy\Tests;

use Illuminate\Support\Arr;
use Log;
use xndbogdan\LaravelRayLegacy\Tests\Concerns\MatchesOsSafeSnapshots;
use xndbogdan\LaravelRayLegacy\Tests\TestClasses\TestMailable;
use xndbogdan\LaravelRayLegacy\Tests\TestClasses\User;
use Spatie\Ray\Settings\Settings;

class RayTest extends TestCase
{
    use MatchesOsSafeSnapshots;

    /** @test */
    public function when_disabled_nothing_will_be_sent_to_ray()
    {
        app(Settings::class)->enable = false;

        ray('test');

        // re-enable for next tests
        ray()->enable();

        $this->assertCount(0, $this->client->sentRequests());
    }

    /** @test */
    public function it_will_send_logs_to_ray_by_default()
    {
        Log::info('hey');

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_will_not_send_dumps_to_ray_when_disabled()
    {
        app(Settings::class)->send_dumps_to_ray = false;

        dump('');

        $this->assertCount(0, $this->client->sentRequests());
    }

    /** @test */
    public function it_will_send_dumps_to_ray_by_default()
    {
        dump('spatie');

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
    public function it_will_not_blow_up_when_not_passing_anything()
    {
        ray();

        $this->assertCount(0, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_be_disabled()
    {
        ray()->disable();
        ray('test');
        $this->assertCount(0, $this->client->sentRequests());

        ray()->enable();
        ray('not test');
        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_check_enabled_status()
    {
        ray()->disable();
        $this->assertEquals(false, ray()->enabled());

        ray()->enable();
        $this->assertEquals(true, ray()->enabled());
    }

    /** @test */
    public function it_can_check_disabled_status()
    {
        ray()->disable();
        $this->assertEquals(true, ray()->disabled());

        ray()->enable();
        $this->assertEquals(false, ray()->disabled());
    }

    /** @test */
    public function it_can_replace_the_remote_path_with_the_local_one()
    {
        $settings = app(Settings::class);

        $settings->remote_path = __DIR__;
        $settings->local_path = 'local_tests';

        ray('test');

        $this->assertStringContainsString(
            'local_tests',
            Arr::get($this->client->sentRequests(), '0.payloads.0.origin.file')
        );
    }

    /** @test */
    public function it_will_automatically_use_specialized_payloads()
    {
        ray(new TestMailable(), new User());

        $payloads = $this->client->sentRequests();

        $this->assertEquals('mailable', $payloads[0]['payloads'][0]['type']);
        $this->assertEquals('eloquent_model', $payloads[0]['payloads'][1]['type']);
    }

    /** @test */
    public function it_sends_an_environment_payload()
    {
        ray()->env([], __DIR__ . '/stubs/dotenv.env');

        $payloads = $this->client->sentRequests();

        $this->assertEquals('table', $payloads[0]['payloads'][0]['type']);
        $this->assertEquals('.env', $payloads[0]['payloads'][0]['content']['label']);
        $this->assertEquals('local', $payloads[0]['payloads'][0]['content']['values']['APP_ENV']);
        $this->assertEquals('ray_test', $payloads[0]['payloads'][0]['content']['values']['DB_DATABASE']);
        $this->assertEquals('120', $payloads[0]['payloads'][0]['content']['values']['SESSION_LIFETIME']);
        $this->assertGreaterThanOrEqual(17, count($payloads[0]['payloads'][0]['content']['values']));
    }

    /** @test */
    public function it_sends_a_filtered_environment_payload()
    {
        ray()->env(['APP_ENV', 'DB_DATABASE'], __DIR__ . '/stubs/dotenv.env');

        $payloads = $this->client->sentRequests();

        $this->assertEquals('table', $payloads[0]['payloads'][0]['type']);
        $this->assertEquals('.env', $payloads[0]['payloads'][0]['content']['label']);
        $this->assertEquals('local', $payloads[0]['payloads'][0]['content']['values']['APP_ENV']);
        $this->assertEquals('ray_test', $payloads[0]['payloads'][0]['content']['values']['DB_DATABASE']);
        $this->assertCount(2, $payloads[0]['payloads'][0]['content']['values']);
    }
}
