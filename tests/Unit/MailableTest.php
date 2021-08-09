<?php

namespace Spatie\LaravelRayLegacy\Tests\Unit;

use Illuminate\Support\Facades\Mail;
use Spatie\LaravelRayLegacy\Tests\TestCase;
use Spatie\LaravelRayLegacy\Tests\TestClasses\TestMailable;

class MailableTest extends TestCase
{
    /** @test */
    public function it_can_send_the_mailable_payload()
    {
        ray()->mailable(new TestMailable());

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_send_a_logged_mailable()
    {
        Mail::mailer('log')
            ->cc(['adriaan' => 'adriaan@spatie.be', 'seb@spatie.be'])
            ->bcc(['willem@spatie.be', 'jef@spatie.be'])
            ->to(['freek@spatie.be', 'ruben@spatie.be'])
            ->send(new TestMailable());

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_can_send_multiple_mailable_payloads()
    {
        ray()->mailable(new TestMailable(), new TestMailable());

        $this->assertCount(2, $this->client->sentPayloads());
        $this->assertCount(1, $this->client->sentRequests());
    }
}
