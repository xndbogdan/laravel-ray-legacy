<?php

namespace Spatie\LaravelRayLegacy\Watchers;

use Spatie\LaravelRayLegacy\RayProxy;

abstract class Watcher
{
    /** @var bool */
    protected $enabled = false;

    /** @var \Spatie\LaravelRayLegacy\RayProxy|null */
    protected $rayProxy;

    abstract public function register(): void;

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): Watcher
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): Watcher
    {
        $this->enabled = false;

        return $this;
    }

    public function setRayProxy(RayProxy $rayProxy): Watcher
    {
        $this->rayProxy = $rayProxy;

        return $this;
    }
}
