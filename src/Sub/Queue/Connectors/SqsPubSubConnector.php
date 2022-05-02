<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use PodPoint\AwsPubSub\EventServiceProvider;
use PodPoint\AwsPubSub\Sub\Queue\EventDispatcherManager;
use PodPoint\AwsPubSub\Sub\Queue\SqsPubSubQueue;

class SqsPubSubConnector extends SqsConnector
{
    /** @var EventDispatcherManager */
    private $eventDispatcherManager;

    public function __construct(EventDispatcherManager $eventDispatcherManager)
    {
        $this->eventDispatcherManager = $eventDispatcherManager;
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

        return new SqsPubSubQueue(
            new SqsClient(EventServiceProvider::prepareConfigurationCredentials($config)),
            $config['queue'],
            Arr::get($config, 'prefix', ''),
            Arr::get($config, 'suffix', ''),
            Arr::get($config, 'dispatchAfterCommit', false),
            $this->eventDispatcherManager->driver(Arr::get($config, 'dispatcher'))
        );
    }
}
