<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use Illuminate\Queue\Jobs\SqsJob;
use PodPoint\AwsPubSub\Sub\Queue\ValidationResult;

interface EventResolver
{
    public function validate(SqsJob $job): ValidationResult;

    public function resolveName(SqsJob $job): string;

    public function resolvePayload(SqsJob $job): array;

    public function resolveSubject(SqsJob $param): string;
}
