<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Support\Arr;

class SqsSnsJob extends SqsJob
{
    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        $this->job = $this->resolveSnsSubscription($this->job);
    }

    /**
     * Resolves SNS queue messages.
     *
     * @param  array  $job
     * @return array
     */
    protected function resolveSnsSubscription(array $job): array
    {
        $body = json_decode($job['Body'], true);

        $job['Body'] = json_encode([
            'uuid' => $body['MessageId'],
            'displayName' => $job['ListenerName'],
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => CallQueuedListener::class,
                'command' => $this->makeCommand($job['ListenerName'], $body),
            ],
        ]);

        return $job;
    }

    /**
     * Make the serialized command.
     *
     * @param  string  $listenerName
     * @param  array  $body
     * @return string
     */
    protected function makeCommand(string $listenerName, array $body): string
    {
        $payload = json_decode($body['Message'], true);
        $subject = Arr::get($body, 'Subject', '');

        $instance = $this->container->make(CallQueuedListener::class, [
            'class' => $listenerName,
            'method' => 'handle',
            'data' => compact('payload', 'subject'),
        ]);

        return serialize($instance);
    }

    /**
     * Get the underlying raw SQS job.
     *
     * @return array
     */
    public function getSqsSnsJob()
    {
        return $this->job;
    }
}
