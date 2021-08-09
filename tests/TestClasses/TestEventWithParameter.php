<?php

namespace xndbogdan\LaravelRayLegacy\Tests\TestClasses;

class TestEventWithParameter
{
    /** @var string */
    protected $parameter;

    public function __construct(string $parameter)
    {
        $this->parameter = $parameter;
    }
}
