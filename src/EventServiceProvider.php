<?php

namespace PodPoint\AwsPubSub;

use Aws\EventBridge\EventBridgeClient;
use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\EventBridgeBroadcaster;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\SnsBroadcaster;
use PodPoint\AwsPubSub\Sub\Queue\Connectors\SqsSnsConnector;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for subscribing to PubSub events.
     *
     * @var array
     */
    protected $listen = [];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSnsBroadcaster();
        $this->registerSqsSnsQueueConnector();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the SNS broadcaster for the Broadcast components.
     *
     * @return void
     */
    protected function registerSnsBroadcaster()
    {
        $this->app->resolving(BroadcastManager::class, function (BroadcastManager $manager) {
            $manager->extend('sns', function (Container $app, array $config) {
                return $this->createSnsDriver($config);
            });
        });
    }

    /**
     * Create an instance of the SNS driver for broadcasting.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createSnsDriver(array $config)
    {
        $config = $this->prepareConfig($config);

        return new SnsBroadcaster(
            new SnsClient(array_merge($config, ['version' => 'latest'])),
            $config['arn-prefix'] ?? '',
            $config['arn-suffix'] ?? ''
        );
    }

    /**
     * Register the SQS SNS connector for the Queue components.
     *
     * @return void
     */
    protected function registerSqsSnsQueueConnector()
    {
        $this->app->resolving('queue', function (QueueManager $manager) {
            $manager->extend('sqs-sns', function () {
                return new SqsSnsConnector($this->listen);
            });
        });
    }

    /**
     * Register the EventBridge broadcaster for the Broadcast components.
     *
     * @return void
     */
    protected function registerEventBridgeBroadcaster()
    {
        $this->app->resolving(BroadcastManager::class, function (BroadcastManager $manager) {
            $manager->extend('eventbridge', function (Container $app, array $config) {
                return $this->createEventBridgeDriver($config);
            });
        });
    }

    /**
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createEventBridgeDriver(array $config)
    {
        $config = $this->prepareConfig($config);

        return new EventBridgeBroadcaster(
            new EventBridgeClient($config),
            $config['source'] ?? ''
        );
    }

    /**
     * @param  array  $config
     * @return array
     */
    protected function prepareConfig(array $config): array
    {
        if ($config['key'] && $config['secret']) {
            $config['credentials'] = [
                'key' => $config['key'],
                'secret' => $config['secret'],
                'token' => $config['token'] ?? null,
            ];
        }
        return $config;
    }
}
