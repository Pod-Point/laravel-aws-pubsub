<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use PodPoint\SnsBroadcaster\BroadcastsEvents;

class UserWithBroadcastingEventsWithCustomPayload extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['users'];
    }

    public function broadcastWith($event)
    {
        return [
            'action' => $event,
            'data' => [
                'user' => $this,
                'foo' => 'bar',
            ],
        ];
    }
}
