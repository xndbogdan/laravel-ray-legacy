<?php

namespace xndbogdan\LaravelRayLegacy\Tests\Unit;

use xndbogdan\LaravelRayLegacy\Tests\TestCase;

class DumpTest extends TestCase
{
    /** @test */
    public function it_can_log_dumps()
    {
        dump('test');

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_log_dumps_with_a_specified_dumper_format()
    {
        ob_start();
        $_SERVER['VAR_DUMPER_FORMAT'] = 'html';
        dump('test 1');
        ob_end_clean();

        $this->assertCount(1, $this->client->sentRequests());

        $_SERVER['VAR_DUMPER_FORMAT'] = 'cli';
        dump('test 2');

        $this->assertCount(2, $this->client->sentRequests());
    }
}
