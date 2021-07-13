<?php

namespace PodPoint\SnsBroadcaster;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred as EloquentBroadcastableModelEventOccurred;
use Illuminate\Support\ServiceProvider;
use PodPoint\SnsBroadcaster\Broadcasters\SnsBroadcaster;

class SnsBroadcasterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(EloquentBroadcastableModelEventOccurred::class, BroadcastableModelEventOccurred::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->app->singleton(SnsClient::class, function () {
            $config = [
                'region' => config('broadcasting.connections.sns.region'),
                'version' => 'latest',
            ];

            $key = config('broadcasting.connections.sns.key');
            $secret = config('broadcasting.connections.sns.secret');
            $token = config('broadcasting.connections.sns.token');

            if ($key && $secret) {
                $config['credentials'] = [
                    'key' => $key,
                    'secret' => $secret,
                    'token' => $token,
                ];
            }

            return new SnsClient($config);
        });

        $this->app->make(BroadcastManager::class)->extend('sns', function(Container $app, array $config) {
            return new SnsBroadcaster($config['arn-prefix']);
        });
    }
}
