<?php

namespace Spatie\LaravelRayLegacy\Tests\Payloads;

use Spatie\LaravelRayLegacy\Payloads\MailablePayload;
use Spatie\LaravelRayLegacy\Tests\TestCase;
use Spatie\LaravelRayLegacy\Tests\TestClasses\TestMailable;

class MailablePayloadTest extends TestCase
{
    /** @test */
    public function it_can_render_a_mailable()
    {
        $mailable = new TestMailable();

        $payload = MailablePayload::forMailable($mailable);

        $this->assertTrue(is_string($payload->getContent()['html']));
    }
}
