<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEventsWithMultipleChannels extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['users', 'customers'];
    }
}
