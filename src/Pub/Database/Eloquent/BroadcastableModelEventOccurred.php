<?php

namespace PodPoint\AwsPubSub\Pub\Database\Eloquent;

use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred as EloquentBroadcastableModelEventOccurred;

class BroadcastableModelEventOccurred extends EloquentBroadcastableModelEventOccurred
{
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return method_exists($this->model, 'broadcastAs')
            ? $this->model->broadcastAs($this->event)
            : parent::broadcastAs();
    }

    /**
     * Get and format the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return method_exists($this->model, 'broadcastWith')
            ? $this->model->broadcastWith($this->event)
            : [
                'model' => $this->model->toArray(),
                'queue' => $this->queue,
                'connection' => $this->connection,
            ];
    }
}
