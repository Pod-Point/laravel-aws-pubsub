<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventDispatchers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Facades\Log;

class SnsEventDispatcher implements EventDispatcher
{
    public function dispatch(SqsJob $job, Dispatcher $dispatcher): void
    {
        if (! $this->validate($job)) {
            $this->failedValidation($job);
        }

        $dispatcher->dispatch($this->resolveName($job), $this->resolvePayload($job));
    }

    public function validate(SqsJob $job): bool
    {
        return ! $this->isRawPayload($job);
    }

    public function failedValidation(SqsJob $job): void
    {
        if ($job->getContainer()->bound('log')) {
            Log::error(
                'Invalid SNS payload. '.
                'Make sure your JSON is a valid JSON object and raw '.
                'message delivery is disabled for your SQS subscription.',
                $job->getSqsJob()
            );
        }
    }

    private function resolveName(SqsJob $job)
    {
        return $this->snsSubject($job) ?: $this->snsTopicArn($job);
    }

    public function resolvePayload(SqsJob $job): array
    {
        return json_decode($this->snsMessage($job), true);
    }

    private function isRawPayload(SqsJob $job): bool
    {
        return is_null($job->payload()['Type'] ?? null);
    }

    private function snsTopicArn(SqsJob $job): string
    {
        return $job->payload()['TopicArn'] ?? '';
    }

    private function snsSubject(SqsJob $job): string
    {
        return $job->payload()['Subject'] ?? '';
    }

    private function snsMessage(SqsJob $job): string
    {
        return $job->payload()['Message'] ?? '[]';
    }
}
