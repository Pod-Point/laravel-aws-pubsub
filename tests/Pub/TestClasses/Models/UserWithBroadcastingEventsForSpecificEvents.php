<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use PodPoint\AwsPubSub\Pub\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEventsForSpecificEvents extends User
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
}
