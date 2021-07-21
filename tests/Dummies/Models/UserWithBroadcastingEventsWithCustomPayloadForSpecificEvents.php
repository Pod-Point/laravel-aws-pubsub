<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use PodPoint\SnsBroadcaster\BroadcastsEvents;

class UserWithBroadcastingEventsWithCustomPayloadForSpecificEvents extends User
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

    public function broadcastWith($event)
    {
        return [
            'action' => $event,
            'data' => [
                'user' => $this,
                'foo' => 'baz',
            ],
        ];
    }
}
