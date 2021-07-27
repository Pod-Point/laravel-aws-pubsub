<?php

namespace App\Providers;

use PodPoint\AwsPubSub\EventServiceProvider as ServiceProvider;

class PubSubEventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for subscribing to PubSub events.
     *
     * @var array
     */
    protected $listen = [
        // ...
    ];
}
