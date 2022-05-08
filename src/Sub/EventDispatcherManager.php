<?php

namespace PodPoint\AwsPubSub\Sub;

use Illuminate\Support\Manager;
use PodPoint\AwsPubSub\Sub\EventDispatchers\SnsEventDispatcher;

class EventDispatcherManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'sns';
    }

    public function createSnsDriver()
    {
        return $this->container->make(SnsEventDispatcher::class);
    }
}
