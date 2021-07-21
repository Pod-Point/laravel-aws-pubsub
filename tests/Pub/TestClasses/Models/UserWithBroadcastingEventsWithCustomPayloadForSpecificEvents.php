<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use PodPoint\AwsPubSub\Pub\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEventsWithCustomPayloadForSpecificEvents extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return match ($event) {
            'created' => [],
            'updated' => ['users'],
            'trashed' => [],
            'restored' => [],
            'deleted' => [],
        };
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
