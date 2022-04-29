<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

use PodPoint\AwsPubSub\Sub\Queue\ValidationResult;

class SnsEventResolver extends EventResolver
{
    public function validate(): ValidationResult
    {
        if ($this->isRawPayload()) {
            return new ValidationResult(
                false,
                'Invalid SNS payload. '.
                'Make sure your JSON is a valid JSON object and raw '.
                'message delivery is disabled for your SQS subscription.'
            );
        }

        return new ValidationResult(true);
    }

    public function resolveName(): string
    {
        return $this->snsSubject() ?: $this->snsTopicArn();
    }

    public function resolvePayload(): array
    {
        return [
            'payload' => json_decode($this->snsMessage(), true),
            'subject' => $this->snsSubject(),
        ];
    }

    /**
     * Verifies that the SNS message sent to the queue can be processed.
     *
     * @return bool
     */
    private function isRawPayload(): bool
    {
        return is_null($this->job->payload()['Type'] ?? null);
    }

    /**
     * Get the job SNS Topic identifier it was sent from.
     *
     * @return string
     */
    private function snsTopicArn(): string
    {
        return $this->job->payload()['TopicArn'] ?? '';
    }

    /**
     * Get the job SNS subject.
     *
     * @return string
     */
    private function snsSubject(): string
    {
        return $this->job->payload()['Subject'] ?? '';
    }

    /**
     * Get the job SNS message.
     *
     * @return string
     */
    private function snsMessage(): string
    {
        return $this->job->payload()['Message'] ?? '[]';
    }
}
