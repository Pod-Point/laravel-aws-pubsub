<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models;

use Illuminate\Database\Eloquent\BroadcastsEvents;

class UserWithBroadcastingEventsWithCustomName extends User
{
    use BroadcastsEvents;

    public function broadcastOn($event)
    {
        return ['users'];
    }

    public function broadcastAs($event)
    {
        return "user.{$event}";
    }
}
