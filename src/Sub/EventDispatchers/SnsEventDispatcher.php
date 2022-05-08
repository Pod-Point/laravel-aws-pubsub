<?php

namespace PodPoint\AwsPubSub\Sub\EventDispatchers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Jobs\SqsJob;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SnsEventDispatcher implements EventDispatcher
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function dispatch(SqsJob $job, Dispatcher $dispatcher): void
    {
        if ($this->isRawPayload($job)) {
            $this->logger->error('PubSubSqsQueue: Invalid SNS payload. '.
                'Make sure your JSON is a valid JSON object and raw '.
                'message delivery is disabled for your SQS subscription.', $job->getSqsJob());

            return;
        }

        if ($eventName = $this->resolveName($job)) {
            $dispatcher->dispatch($eventName, [
                'payload' => json_decode($this->snsMessage($job), true),
                'subject' => $this->snsSubject($job),
            ]);
        }
    }

    /**
     * Verifies that the SNS message sent to the queue can be processed.
     *
     * @return bool
     */
    private function isRawPayload(SqsJob $job)
    {
        return is_null($job->payload()['Type'] ?? null);
    }

    public function resolveName(SqsJob $job)
    {
        return $this->snsSubject($job) ?: $this->snsTopicArn($job);
    }

    /**
     * Get the job SNS Topic identifier it was sent from.
     *
     * @return string
     */
    public function snsTopicArn(SqsJob $job)
    {
        return $job->payload()['TopicArn'] ?? '';
    }

    /**
     * Get the job SNS subject.
     *
     * @return string
     */
    public function snsSubject(SqsJob $job)
    {
        return $job->payload()['Subject'] ?? '';
    }

    /**
     * Get the job SNS message.
     *
     * @return string
     */
    public function snsMessage(SqsJob $job)
    {
        return $job->payload()['Message'] ?? '[]';
    }
}
