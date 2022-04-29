<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use Illuminate\Queue\Jobs\SqsJob;
use PodPoint\AwsPubSub\Sub\Queue\ValidationResult;

class SnsEventResolver implements EventResolver
{
    public function validate(SqsJob $job): ValidationResult
    {
        if ($this->isRawPayload($job->payload())) {
            return new ValidationResult(
                false,
                'Invalid SNS payload. '.
                'Make sure your JSON is a valid JSON object and raw '.
                'message delivery is disabled for your SQS subscription.'
            );
        }

        return new ValidationResult(true);
    }

    public function resolveName(SqsJob $job): string
    {
        $jobPayload = $job->payload();

        return $this->snsSubject($jobPayload) ?: $this->snsTopicArn($jobPayload);
    }

    public function resolvePayload(SqsJob $job): array
    {
        $jobPayload = $job->payload();

        return [
            'payload' => json_decode($this->snsMessage($jobPayload), true),
            'subject' => $this->snsSubject($jobPayload),
        ];
    }

    public function resolveSubject(SqsJob $job): string
    {
        $jobPayload = $job->payload();

        return $this->snsSubject($jobPayload);
    }

    /**
     * Verifies that the SNS message sent to the queue can be processed.
     *
     * @return bool
     */
    private function isRawPayload($jobPayload): bool
    {
        return is_null($jobPayload['Type'] ?? null);
    }

    /**
     * Get the job SNS Topic identifier it was sent from.
     *
     * @return string
     */
    private function snsTopicArn(array $jobPayload): array
    {
        return $jobPayload['TopicArn'] ?? '';
    }

    /**
     * Get the job SNS subject.
     *
     * @return string
     */
    private function snsSubject(array $jobPayload): array
    {
        return $jobPayload['Subject'] ?? '';
    }

    /**
     * Get the job SNS message.
     *
     * @return string
     */
    private function snsMessage(array $jobPayload): array
    {
        return $jobPayload['Message'] ?? '[]';
    }
}
