<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;

class SqsSnsConnector extends SqsConnector
{
    /**
     * @var array
     */
    public $events;

    public function __construct(array $events = [])
    {
        $this->events = $events;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return new SqsSnsQueue(
            new SqsClient($config),
            $config['queue'],
            Arr::get($config, 'prefix', ''),
            Arr::get($config, 'suffix', ''),
            $this->events,
        );
    }
}
