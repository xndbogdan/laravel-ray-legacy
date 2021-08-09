<?php

namespace Spatie\LaravelRayLegacy\Tests\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;

class TestJob implements ShouldQueue
{
    public function handle()
    {
    }
}
