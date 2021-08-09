<?php

namespace xndbogdan\LaravelRayLegacy\Watchers;

use Illuminate\Support\Facades\Event;
use xndbogdan\LaravelRayLegacy\Payloads\EventPayload;
use xndbogdan\LaravelRayLegacy\Ray;

class EventWatcher extends Watcher
{
    public function register(): void
    {
        Event::listen('*', function (string $eventName, array $arguments) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new EventPayload($eventName, $arguments);

            $ray = app(Ray::class)->sendRequest($payload);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });
    }
}
