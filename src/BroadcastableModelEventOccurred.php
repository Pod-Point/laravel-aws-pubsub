<?php

namespace PodPoint\SnsBroadcaster;

use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred as EloquentBroadcastableModelEventOccurred;

class BroadcastableModelEventOccurred extends EloquentBroadcastableModelEventOccurred
{
    /**
     * Get and format the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return method_exists($this->model, 'broadcastWith') ? $this->model->broadcastWith($this->event) : [
            'model' => $this->model->toArray(),
            'queue' => $this->queue,
            'connection' => $this->connection,
        ];
    }
}
