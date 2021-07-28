<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEventsWhenUpdatedOnly extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        switch ($event) {
            case 'created':
            case 'trashed':
            case 'restored':
            case 'deleted':
                return [];
            case 'updated':
                return ['users'];
        }
    }
}
