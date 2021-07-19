<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use Illuminate\Database\Eloquent\Model;
use PodPoint\SnsBroadcaster\BroadcastsEvents;

class UserWithBroadcastingEventsWithMultipleChannels extends Model
{
    use BroadcastsEvents;

    protected $table = 'users';

    protected $guarded = [];

    public function broadcastOn($event)
    {
        return ['users', 'customers'];
    }
}
