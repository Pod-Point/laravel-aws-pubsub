<?php

namespace PodPoint\AwsPubSub\Sub\Queue;

use Illuminate\Support\Manager;
use PodPoint\AwsPubSub\Sub\Queue\EventDispatchers\SnsEventDispatcher;

class EventDispatcherManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'sns';
    }

    public function getSnsDriver()
    {
        return new SnsEventDispatcher();
    }
}
