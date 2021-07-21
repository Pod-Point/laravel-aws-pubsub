<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use PodPoint\AwsPubSub\Pub\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEvents extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['users'];
    }
}
