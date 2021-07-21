<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
