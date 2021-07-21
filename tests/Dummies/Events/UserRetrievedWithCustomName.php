<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\User;

class UserRetrievedWithCustomName implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return ['users'];
    }

    public function broadcastAs()
    {
        return 'user.retrieved';
    }
}
