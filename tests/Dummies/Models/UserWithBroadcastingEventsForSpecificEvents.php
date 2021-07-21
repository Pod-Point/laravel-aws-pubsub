<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use PodPoint\SnsBroadcaster\BroadcastsEvents;

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
