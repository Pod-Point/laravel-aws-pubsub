<?php

namespace PodPoint\AwsPubSub\Pub\Database\Eloquent;

use Illuminate\Database\Eloquent\BroadcastsEvents as EloquentBroadcastsEvents;

trait BroadcastsEvents
{
    use EloquentBroadcastsEvents;

    /**
     * Create a new broadcastable model event using our own Event class.
     *
     * @param string $event
     * @return mixed
     */
    public function newBroadcastableModelEvent($event)
    {
        /** @var $this \Illuminate\Database\Eloquent\Model */
        return tap(new BroadcastableModelEventOccurred($this, $event), function ($event) {
            $event->connection = property_exists($this, 'broadcastConnection')
                ? $this->broadcastConnection
                : $this->broadcastConnection();

            $event->queue = property_exists($this, 'broadcastQueue')
                ? $this->broadcastQueue
                : $this->broadcastQueue();

            $event->afterCommit = property_exists($this, 'broadcastAfterCommit')
                ? $this->broadcastAfterCommit
                : $this->broadcastAfterCommit();
        });
    }
}
