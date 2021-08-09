<?php

namespace xndbogdan\LaravelRayLegacy\Payloads;

use Illuminate\Database\Events\QueryExecuted;
use Spatie\Ray\Payloads\Payload;

class ExecutedQueryPayload extends Payload
{
    /** @var \Illuminate\Database\Events\QueryExecuted */
    protected $query;

    public function __construct(QueryExecuted $query)
    {
        $this->query = $query;
    }

    public function getType(): string
    {
        return 'executed_query';
    }

    public function getContent(): array
    {
        $properties = [
            'sql' => $this->query->sql,
            'bindings' => $this->query->bindings,
        ];

        if ($this->hasAllProperties()) {
            $properties = array_merge($properties, [
                'connection_name' => $this->query->connectionName,
                'time' => $this->query->time,
            ]);
        }

        return $properties;
    }

    protected function hasAllProperties(): bool
    {
        return ! is_null($this->query->time);
    }
}
