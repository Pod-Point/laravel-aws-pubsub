<?php

namespace PodPoint\AwsPubSub\Sub\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Log;
use PodPoint\AwsPubSub\Sub\EventDispatchers\EventDispatcher;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\EventDispatcherJob;

class PubSubSqsQueue extends SqsQueue
{
    /** @var EventDispatcher */
    private $eventDispatcher;

    public function __construct(
        SqsClient $sqs,
        $default,
        $prefix,
        $suffix,
        $dispatchAfterCommit,
        EventDispatcher $eventDispatcher
    ) {
        parent::__construct($sqs, $default, $prefix, $suffix, $dispatchAfterCommit);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if ($this->container->bound('log')) {
            Log::error('Unsupported: pub_sub queue driver is read-only');
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($this->container->bound('log')) {
            Log::error('Unsupported: pub_sub queue driver is read-only');
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
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new EventDispatcherJob(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue, $this->getEventDispatcher()
            );
        }
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }
}
