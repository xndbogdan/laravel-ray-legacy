<?php

namespace xndbogdan\LaravelRayLegacy\Tests\Unit;

use xndbogdan\LaravelRayLegacy\Tests\TestCase;

class CollectionTest extends TestCase
{
    /** @test */
    public function it_has_a_chainable_collection_macro_to_send_things_to_ray()
    {
        $array = ['a', 'b', 'c'];

        $newArray = collect($array)->ray()->toArray();

        $this->assertEquals($newArray, $array);

        $this->assertCount(1, $this->client->sentRequests());
    }
}
