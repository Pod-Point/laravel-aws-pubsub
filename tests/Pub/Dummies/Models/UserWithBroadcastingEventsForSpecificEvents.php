<?php

namespace PodPoint\AwsPubSub\Tests\Pub\Dummies\Models;

use PodPoint\AwsPubSub\Pub\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEventsForSpecificEvents extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['users'];
    }

    public function broadcastEvents()
    {
        return ['updated'];
    }
}
