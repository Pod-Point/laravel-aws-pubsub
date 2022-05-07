<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use PodPoint\AwsPubSub\EventServiceProvider;
use PodPoint\AwsPubSub\Sub\EventDispatchers\SnsEventDispatcher;
use PodPoint\AwsPubSub\Sub\Queue\PubSubSqsQueue;

class PubSubSqsConnector extends SqsConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        return new PubSubSqsQueue(
            new SqsClient(EventServiceProvider::prepareConfigurationCredentials($config)),
            $config['queue'],
            Arr::get($config, 'prefix', ''),
            Arr::get($config, 'suffix', ''),
            false,
            new SnsEventDispatcher()
        );
    }
}
