<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;
use PodPoint\AwsPubSub\EventServiceProvider;
use PodPoint\AwsPubSub\Sub\Queue\EventResolvers\SnsEventResolver;
use PodPoint\AwsPubSub\Sub\Queue\SqsPubSubQueue;

class SqsPubSubConnector extends SqsConnector
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

        return new SqsPubSubQueue(
            new SqsClient(EventServiceProvider::prepareConfigurationCredentials($config)),
            $config['queue'],
            Arr::get($config, 'prefix', ''),
            Arr::get($config, 'suffix', ''),
            Arr::get($config, 'dispatchAfterCommit', false),
            Arr::get($config, 'event_resolver', SnsEventResolver::class)
        );
    }
}
