<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventDispatchers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Arr;

class PayloadPathEventDispatcher implements EventDispatcher
{
    private $namePath;
    private $payloadPath;

    public function __construct(string $namePath, string $payloadPath)
    {
        $this->namePath = $namePath;
        $this->payloadPath = $payloadPath;
    }

    public function dispatch(SqsJob $job, Dispatcher $dispatcher): void
    {
        $dispatcher->dispatch(
            Arr::get($job->payload(), $this->namePath, ''),
            Arr::get($job->payload(), $this->payloadPath, ''),
        );
    }
}
