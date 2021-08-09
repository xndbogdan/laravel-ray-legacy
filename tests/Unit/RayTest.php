<?php

namespace Spatie\LaravelRay\Tests\Unit;

use Illuminate\Support\Arr;
use Spatie\LaravelRay\Tests\TestCase;
use Spatie\LaravelRay\Tests\TestClasses\TestMailable;
use Spatie\LaravelRay\Tests\TestClasses\User;
use Spatie\Ray\Settings\Settings;

class RayTest extends TestCase
{
    /** @test */
    public function when_disabled_nothing_will_be_sent_to_ray()
    {
        app(Settings::class)->enable = false;

        ray('test');

        ray()->enable();

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
    public function it_will_not_blow_up_when_not_passing_anything()
    {
        ray();

        $this->assertCount(0, $this->client->sentRequests());
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
        app(Settings::class)->remote_path = __DIR__;
        app(Settings::class)->local_path = 'local_tests';

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
}
