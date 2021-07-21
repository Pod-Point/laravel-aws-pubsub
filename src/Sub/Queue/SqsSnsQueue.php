<?php

namespace PodPoint\AwsPubSub\Sub\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\SqsSnsJob;

class SqsSnsQueue extends SqsQueue
{
    /**
     * @var array
     */
    protected $events;

    /**
     * Create a new Amazon SQS SNS subscription queue instance
     *
     * @param \Aws\Sqs\SqsClient $sqs
     * @param string $default
     * @param string $prefix
     * @param string $suffix
     * @param array $events
     */
    public function __construct(SqsClient $sqs, $default, $prefix = '', $suffix = '', $events = [])
    {
        parent::__construct($sqs, $default, $prefix, $suffix);

        $this->events = $events;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     * @return \PodPoint\AwsPubSub\Sub\Queue\Jobs\SqsSnsJob|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if ($listenerName = $this->resolveListener($response)) {
            $response['Messages'][0]['ListenerName'] = $listenerName;

            return new SqsSnsJob(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue
            );
        }
    }

    /**
     * Check wether there is a message pushed from SNS to SQS
     * to process or not, validate it and see if we are
     * listening for it or not based on the mapping.
     *
     * @param \Aws\Result $response
     * @return string|null
     */
    private function resolveListener(\Aws\Result $response)
    {
        $messages = $response['Messages'];

        if (is_null($messages) || empty($messages)) {
            return false;
        }

        $body = json_decode($messages[0]['Body'], true);

        if (is_null($body) || is_null(Arr::get($body, 'Type'))) {
            Log::error('SqsSnsQueue: Invalid SNS payload. ' .
                'Make sure your JSON is a valid JSON object and raw ' .
                'message delivery is disabled for your SQS subscription.', $response->toArray());

            return false;
        }

        $event = Arr::get($body, 'Subject', Arr::get($body, 'TopicArn'));

        return Arr::get($this->events, $event);
    }
}
