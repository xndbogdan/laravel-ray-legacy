<?php

namespace Spatie\LaravelRay\Tests\TestClasses;

use Illuminate\Support\Str;
use Spatie\Ray\Client;
use Spatie\Ray\Request;

class FakeClient extends Client
{
    /** @var array */
    protected $sentRequests = [];

    /** @var array */
    protected $sentPayloads = [];

    public function send(Request $request): void
    {
        $requestProperties = $request->toArray();

        foreach ($requestProperties['payloads'] as &$payload) {
            $payload['origin']['file'] = $payload['origin']['file'] = str_replace($this->baseDirectory(), '', $payload['origin']['file']);

            if (Str::contains($payload['origin']['file'], 'helpers.php')) {
                $payload['origin']['file'] = 'helpers.php';
            }

            if (isset($payload['content']['values'])) {
                $payload['content']['values'] = preg_replace('/sf-dump-[0-9]{1,10}/', 'sf-dump-xxxxxxxxxx', $payload['content']['values']);
            }

            if (isset($payload['content']['attributes'])) {
                $payload['content']['attributes'] = preg_replace('/sf-dump-[0-9]{1,10}/', 'sf-dump-xxxxxxxxxx', $payload['content']['attributes']);
            }

            $this->sentPayloads[] = $payload;
        }

        $requestProperties['meta'] = [];

        $this->sentRequests[] = $requestProperties;
    }

    public function sentPayloads(): array
    {
        return $this->sentPayloads;
    }

    public function sentRequests(): array
    {
        return $this->sentRequests;
    }

    protected function baseDirectory(): string
    {
        return str_replace("/tests/TestClasses", '', __DIR__);
    }
}
