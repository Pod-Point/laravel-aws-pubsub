<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Models;

use Illuminate\Database\Eloquent\Model;
use PodPoint\SnsBroadcaster\BroadcastsEvents;

class UserWithBroadcastingEventsWithBroadcastWith extends Model
{
    use BroadcastsEvents;

    protected $table = 'users';

    protected $guarded = [];

    public function broadcastOn($event)
    {
        return ['users'];
    }

    /**
     * Get and format the data to broadcast.
     *
     * @return array
     */
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
