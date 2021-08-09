<?php

namespace xndbogdan\LaravelRayLegacy\Watchers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use xndbogdan\LaravelRayLegacy\Ray;

class LoggedMailWatcher extends Watcher
{
    public function register(): void
    {
        $this->enable();

        Event::listen(MessageLogged::class, function (MessageLogged $messageLogged) {
            if (! $this->enabled()) {
                return;
            }

            if (! $this->concernsLoggedMail($messageLogged)) {
                return;
            }

            /** @var Ray $ray */
            $ray = app(Ray::class);

            $ray->loggedMail($messageLogged->message);
        });
    }

    public function concernsLoggedMail(MessageLogged $messageLogged): bool
    {
        if (! Str::startsWith($messageLogged->message, 'Message-ID')) {
            return false;
        }

        if (! Str::contains($messageLogged->message, 'swift')) {
            return false;
        }

        return true;
    }
}
