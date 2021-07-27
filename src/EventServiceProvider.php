<?php

namespace PodPoint\AwsPubSub;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred as EloquentBroadcastableModelEventOccurred;
use Illuminate\Support\ServiceProvider;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\SnsBroadcaster;
use PodPoint\AwsPubSub\Pub\Database\Eloquent\BroadcastableModelEventOccurred;
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
        $this->app->bind(
            EloquentBroadcastableModelEventOccurred::class,
            BroadcastableModelEventOccurred::class
        );
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
}
