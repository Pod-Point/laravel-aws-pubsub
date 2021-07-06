<?php

namespace PodPoint\SnsBroadcaster;

use Illuminate\Database\Eloquent\BroadcastsEvents as EloquentBroadcastsEvents;

trait BroadcastsEvents
{
    use EloquentBroadcastsEvents {
        broadcastIfBroadcastChannelsExistForEvent as eloquentBroadcastIfBroadcastChannelsExistForEvent;
    }

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

    /**
     * Broadcast the given event instance if channels are configured for the model event.
     *
     * @param  mixed  $instance
     * @param  string  $event
     * @param  mixed  $channels
     * @return \Illuminate\Broadcasting\PendingBroadcast|null|void
     */
    protected function broadcastIfBroadcastChannelsExistForEvent($instance, $event, $channels = null)
    {
        if (in_array($event, $this->broadcastEvents())) {
            return $this->eloquentBroadcastIfBroadcastChannelsExistForEvent($instance, $event, $channels);
        }
    }

    /**
     * Get the events that should be broadcasted.
     *
     * @return array
     */
    public function broadcastEvents()
    {
        return ['created', 'updated', 'trashed', 'restored', 'deleted'];
    }
}
