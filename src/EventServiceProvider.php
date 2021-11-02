<?php

namespace PodPoint\AwsPubSub;

use Aws\EventBridge\EventBridgeClient;
use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
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
    public function register(): void
    {
        // ...
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerPub();

        $this->registerSub();
    }

    /**
     * Register anything related to the Publication of PubSub events on AWS.
     *
     * @throws BindingResolutionException
     */
    protected function registerPub()
    {
        $this->registerSnsBroadcaster();

        $this->registerEventBridgeBroadcaster();
    }

    /**
     * Register anything related to the Subscription of PubSub events on AWS.
     *
     * @return void
     */
    protected function registerSub()
    {
        $this->app['queue']->extend('sqs-sns', function () {
            return new SqsSnsConnector($this->listen);
        });
    }

    /**
     * Register everything relevant to the Event Bridge broadcaster.
     *
     * @return void
     */
    protected function registerSnsBroadcaster(): void
    {
        $this->app->singleton(SnsClient::class, function () {
            $config = [
                'region' => config('broadcasting.connections.sns.region'),
                'version' => 'latest',
            ];

            $key = config('broadcasting.connections.sns.key');
            $secret = config('broadcasting.connections.sns.secret');

            if ($key && $secret) {
                $config['credentials'] = [
                    'key' => $key,
                    'secret' => $secret,
                    'token' => config('broadcasting.connections.sns.token'),
                ];
            }

            return new SnsClient($config);
        });

        $this->app->make(BroadcastManager::class)->extend('sns', function (Container $app, array $config) {
            return new SnsBroadcaster(
                $config['arn-prefix'] ?? '',
                $config['arn-suffix'] ?? ''
            );
        });
    }

    /**
     * Register everything relevant to the Event Bridge broadcaster.
     *
     * @return void
     */
    protected function registerEventBridgeBroadcaster(): void
    {
        $this->app->singleton(EventBridgeClient::class, function () {
            $config = [
                'region' => config('broadcasting.connections.eventbridge.region'),
                'version' => 'latest',
            ];

            $key = config('broadcasting.connections.eventbridge.key');
            $secret = config('broadcasting.connections.eventbridge.secret');

            if ($key && $secret) {
                $config['credentials'] = [
                    'key' => $key,
                    'secret' => $secret,
                    'token' => config('broadcasting.connections.eventbridge.token'),
                ];
            }

            return new EventBridgeClient($config);
        });

        $this->app->make(BroadcastManager::class)->extend('eventbridge', function (Container $app, array $config) {
            return new EventBridgeBroadcaster(
                $config['source'] ?? '',
                $config['event'] ?? ''
            );
        });
    }
}
