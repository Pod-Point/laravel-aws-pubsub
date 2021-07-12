<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use Illuminate\Database\Eloquent\Model;
use PodPoint\SnsBroadcaster\BroadcastsEvents;

class UserWithBroadcastingEventsForSpecificEvents extends Model
{
    use BroadcastsEvents;

    protected $table = 'users';

    protected $guarded = [];

    public function broadcastOn($event)
    {
        return ['users'];
    }

    /**
     * Get the events to broadcast to.
     *
     * @return array
     */
    public function broadcastEvents()
    {
        return ['updated'];
    }
}
