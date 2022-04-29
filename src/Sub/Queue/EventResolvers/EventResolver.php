<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use Illuminate\Queue\Jobs\SqsJob;

abstract class EventResolver
{
    public function validate(SqsJob $job): bool
    {
        return true;
    }

    public function failedValidation(SqsJob $job): void
    {
    }

    abstract public function resolve(SqsJob $job): Event;
}
