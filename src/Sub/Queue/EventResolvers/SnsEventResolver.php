<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Facades\Log;

class SnsEventResolver extends EventResolver
{
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

    public function resolve(SqsJob $job): Event
    {
        return new Event(
            $this->resolveName($job),
            $this->resolvePayload($job),
        );
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
