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
            $this->getName($job),
            Arr::get($job->payload(), $this->payloadPath, ''),
        );
    }

    public function getName(SqsJob $job): string
    {
        return Arr::get($job->payload(), $this->namePath, '');
    }
}
