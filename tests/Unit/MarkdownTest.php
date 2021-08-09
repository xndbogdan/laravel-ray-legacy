<?php

namespace Spatie\LaravelRayLegacy\Tests\Unit;

use Spatie\LaravelRayLegacy\Tests\TestCase;

class MarkdownTest extends TestCase
{
    /** @test */
    public function it_can_render_and_send_markdown()
    {
        ray()->markdown('## Hello World!');

        $this->assertMatchesOsSafeSnapshot($this->client->sentRequests());
    }
}
