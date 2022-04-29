<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use Illuminate\Queue\Jobs\SqsJob;
use PodPoint\AwsPubSub\Sub\Queue\ValidationResult;

abstract class EventResolver
{
    /** @var SqsJob */
    protected $job;

    public function __construct(SqsJob $job)
    {
        $this->job = $job;
    }

    public function validate(): ValidationResult
    {
        return new ValidationResult(true);
    }

    abstract public function resolveName(): string;

    abstract public function resolvePayload();
}
