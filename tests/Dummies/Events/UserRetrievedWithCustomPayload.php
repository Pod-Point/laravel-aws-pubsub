<?php

namespace PodPoint\SnsBroadcaster\Tests\Dummies\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\User;

class UserRetrievedWithCustomPayload implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $action = 'RETRIEVED';

    /**
     * @var User
     */
    public User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['users-local'];
    }

    /**
     * Get and format the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'action' => $this->action,
            'data' => [
                'user' => $this->user,
                'foo' => 'baz',
            ],
        ];
    }
}
