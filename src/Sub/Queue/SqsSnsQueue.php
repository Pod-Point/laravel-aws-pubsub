<?php

namespace PodPoint\AwsPubSub\Sub\Queue;

use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SqsSnsQueue extends SqsQueue
{
    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if ($this->container->bound('log')) {
            Log::error('Unsupported: sqs-sns queue driver is read-only');
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($this->container->bound('log')) {
            Log::error('Unsupported: sqs-sns queue driver is read-only');
        }

        return null;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        $hasMessage = ! is_null($response['Messages']) && count($response['Messages']) > 0;

        if ($hasMessage && $event = $this->resolveEventToTrigger($response)) {
            if ($this->container->bound('events')) {
                $this->container['events']->dispatch($event, $this->resolveEventPayload($response));
            }
        }

        return null;
    }

    /**
     * Check whether there is a message pushed from SNS to SQS to process or not, validate
     * it and finally see if we are listening for it or not based on the mapping.
     *
     * @param  \Aws\Result  $response
     * @return string|null
     */
    private function resolveEventToTrigger(\Aws\Result $response): ?string
    {
        $messages = $response['Messages'];

        if (is_null($messages) || empty($messages)) {
            return null;
        }

        $body = json_decode($messages[0]['Body'], true);

        if (is_null($body) || is_null(Arr::get($body, 'Type'))) {
            Log::error('SqsSnsQueue: Invalid SNS payload. '.
                'Make sure your JSON is a valid JSON object and raw '.
                'message delivery is disabled for your SQS subscription.', $response->toArray());

            return null;
        }

        return Arr::get($body, 'Subject', Arr::get($body, 'TopicArn'));
    }

    private function resolveEventPayload(\Aws\Result $response): array
    {
        $body = json_decode($response['Messages'][0]['Body'], true);

        return [
            'payload' => json_decode($body['Message'], true),
            'subject' => Arr::get($body, 'Subject', ''),
        ];
    }
}
