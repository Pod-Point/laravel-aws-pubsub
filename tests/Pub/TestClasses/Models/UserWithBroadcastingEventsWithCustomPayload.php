<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use PodPoint\AwsPubSub\Pub\Database\Eloquent\BroadcastsEvents;

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
