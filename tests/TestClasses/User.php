<?php

namespace Spatie\LaravelRayLegacy\Tests\TestClasses;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $guarded = [];

    public $timestamps = false;
}
