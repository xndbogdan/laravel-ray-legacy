<?php

namespace Spatie\LaravelRayLegacy\Tests\Unit;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Spatie\LaravelRayLegacy\Tests\TestCase;

class StringableTest extends TestCase
{
    /** @test */
    public function it_has_a_chainable_stringable_macro_to_send_things_to_ray()
    {
        $str = new Stringable('Lorem');

        $str = $str->append(' Ipsum')->ray()->append(' Dolor Sit Amen');

        $this->assertInstanceOf(Stringable::class, $str);
        $this->assertSame('Lorem Ipsum Dolor Sit Amen', (string) $str);

        $this->assertCount(1, $this->client->sentRequests());
    }

    /** @test */
    public function it_has_a_chainable_str_macro_to_send_things_to_ray()
    {
        $str = Str::of('Lorem')->append(' Ipsum')->ray()->append(' Dolor Sit Amen');

        $this->assertInstanceOf(Stringable::class, $str);
        $this->assertSame('Lorem Ipsum Dolor Sit Amen', (string) $str);

        $this->assertCount(1, $this->client->sentRequests());
    }
}
