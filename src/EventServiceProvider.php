<?php

namespace PodPoint\AwsPubSub;

use Aws\EventBridge\EventBridgeClient;
use Aws\Sns\SnsClient;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
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

        $this->registerEventBridgeBroadcaster();

        $this->registerListeners();
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
        $config = self::prepareConfigurationCredentials($config);

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
                return new SqsSnsConnector;
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
                return $this->createEventBridgeDriver(array_merge($config, [
                    'version' => '2015-10-07',
                ]));
            });
        });
    }

    /**
     * Create an instance of the EventBridge driver for broadcasting.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    protected function createEventBridgeDriver(array $config)
    {
        $config = self::prepareConfigurationCredentials($config);

        return new EventBridgeBroadcaster(
            new EventBridgeClient($config),
            $config['source'] ?? ''
        );
    }

    /**
     * Parse and prepare the AWS credentials needed by the AWS SDK library from the config.
     *
     * @param  array  $config
     * @return array
     */
    public static function prepareConfigurationCredentials(array $config)
    {
        if (Arr::has($config, ['key', 'secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return $config;
    }

    /**
     * Register the event handlers subscribed to PubSub events against Laravel event bus.
     *
     * @return void
     */
    private function registerListeners()
    {
        foreach ($this->listen as $event => $listeners) {
            foreach (array_unique($listeners) as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
