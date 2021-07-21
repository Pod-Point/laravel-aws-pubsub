<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use PodPoint\SnsBroadcaster\BroadcastsEvents;

class UserWithBroadcastingEventsWithMultipleChannels extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['users', 'customers'];
    }
}
