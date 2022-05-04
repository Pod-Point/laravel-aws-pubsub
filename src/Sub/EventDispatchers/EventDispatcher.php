<?php

namespace PodPoint\AwsPubSub\Sub\EventDispatchers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Jobs\SqsJob;

interface EventDispatcher
{
    public function dispatch(SqsJob $job, Dispatcher $dispatcher): void;
}
