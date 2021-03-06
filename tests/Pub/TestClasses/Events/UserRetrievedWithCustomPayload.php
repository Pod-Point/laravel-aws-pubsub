<?php

namespace PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\User;

class UserRetrievedWithCustomPayload implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action = 'retrieved';

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function broadcastOn()
    {
        return ['users'];
    }

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
