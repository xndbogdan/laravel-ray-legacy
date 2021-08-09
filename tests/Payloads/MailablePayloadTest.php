<?php

namespace xndbogdan\LaravelRayLegacy\Tests\Payloads;

use xndbogdan\LaravelRayLegacy\Payloads\MailablePayload;
use xndbogdan\LaravelRayLegacy\Tests\TestCase;
use xndbogdan\LaravelRayLegacy\Tests\TestClasses\TestMailable;

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
