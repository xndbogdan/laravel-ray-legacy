<?php

namespace xndbogdan\LaravelRayLegacy\Tests\Unit;

use xndbogdan\LaravelRayLegacy\Tests\TestCase;

class MarkdownTest extends TestCase
{
    /** @test */
    public function it_can_render_and_send_markdown()
    {
        ray()->markdown('## Hello World!');

        $this->assertMatchesOsSafeSnapshot($this->client->sentRequests());
    }
}
