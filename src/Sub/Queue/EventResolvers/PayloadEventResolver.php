<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Arr;

class PayloadEventResolver extends EventResolver
{
    private $namePath;
    private $payloadPath;

    public function __construct(string $namePath, string $payloadPath)
    {
        $this->namePath = $namePath;
        $this->payloadPath = $payloadPath;
    }

    public function resolve(SqsJob $job): Event
    {
        return new Event(
            Arr::get($job->payload(), $this->namePath, ''),
            Arr::get($job->payload(), $this->payloadPath, ''),
        );
    }
}
