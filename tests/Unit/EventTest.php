<?php

namespace Spatie\LaravelRay\Tests\Unit;

use Illuminate\Support\Arr;
use Spatie\LaravelRay\Ray;
use Spatie\LaravelRay\Tests\TestCase;
use Spatie\LaravelRay\Tests\TestClasses\TestEvent;

class EventTest extends TestCase
{
    /** @test */
    public function it_can_send_a_class_based_event_to_ray()
    {
        ray()->showEvents();

        event(new TestEvent());

        ray()->stopShowingEvents();

        event('not showing this event');

        $this->assertCount(1, $this->client->sentRequests());
        $this->assertEquals(TestEvent::class, Arr::get($this->client->sentRequests(), '0.payloads.0.content.name'));
        $this->assertTrue(Arr::get($this->client->sentRequests(), '0.payloads.0.content.class_based_event'));
    }

    /** @test */
    public function it_can_send_a_string_based_event_to_ray()
    {
        ray()->showEvents();

        $eventName = 'this is my event';

        event($eventName);

        ray()->stopShowingEvents();

        event('not showing this event');

        $this->assertCount(1, $this->client->sentRequests());
        $this->assertEquals($eventName, Arr::get($this->client->sentRequests(), '0.payloads.0.content.name'));
        $this->assertFalse(Arr::get($this->client->sentRequests(), '0.payloads.0.content.class_based_event'));
    }

    /** @test */
    public function it_will_not_send_any_events_if_it_is_not_enabled()
    {
        event('test event');

        $this->assertCount(0, $this->client->sentRequests());
    }

    /** @test */
    public function the_show_events_function_accepts_a_callable()
    {
        event('start event');

        ray()->showEvents(function () {
            event('event in callable');
        });

        event('end event');

        $this->assertCount(1, $this->client->sentRequests());
        $this->assertEquals('event in callable', Arr::get($this->client->sentRequests(), '0.payloads.0.content.name'));
    }

    /** @test */
    public function show_events_can_be_colorized()
    {
        $this->useRealUuid();

        ray()->showEvents()->green();

        event('my event');

        $sentPayloads = $this->client->sentRequests();

        $this->assertCount(2, $sentPayloads);
        $this->assertEquals($sentPayloads[0]['uuid'], $sentPayloads[1]['uuid']);
        $this->assertNotEquals('fakeUuid', $sentPayloads[0]['uuid']);
    }
}
