<?php

namespace PodPoint\AwsPubSub\Sub\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Log;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\EventDispatcherJob;
use PodPoint\AwsPubSub\Sub\Queue\EventResolvers\EventResolver;

class SqsPubSubQueue extends SqsQueue
{
    /** @var EventResolver */
    private $eventResolver;

    public function __construct(
        SqsClient $sqs,
        $default,
        $prefix,
        $suffix,
        $dispatchAfterCommit,
        EventResolver $eventResolver
    ) {
        parent::__construct($sqs, $default, $prefix, $suffix, $dispatchAfterCommit);

        $this->eventResolver = $eventResolver;
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if ($this->container->bound('log')) {
            Log::error('Unsupported: sqs-pubsub queue driver is read-only');
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($this->container->bound('log')) {
            Log::error('Unsupported: sqs-pubsub queue driver is read-only');
        }

        return null;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new EventDispatcherJob(
                $this->container,
                $this->sqs,
                $response['Messages'][0],
                $this->connectionName,
                $queue,
                $this->eventResolver,
            );
        }

        return null;
    }
}
