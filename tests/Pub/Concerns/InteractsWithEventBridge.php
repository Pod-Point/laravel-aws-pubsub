<?php

namespace PodPoint\AwsPubSub\Tests\Pub\Concerns;

use Aws\EventBridge\EventBridgeClient;
use Closure;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Application;
use Mockery as m;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\EventBridgeBroadcaster;

trait InteractsWithEventBridge
{
    /**
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app)
    {
        $app->config->set('broadcasting.default', 'eventbridge');
        $app->config->set('broadcasting.connections.eventbridge', [
            'driver' => 'eventbridge',
            'key' => 'dummy-key',
            'secret' => 'dummy-secret',
            'region' => 'eu-west-1',
            'event_bus' => 'default',
            'source' => 'my-app',
        ]);
    }

    /**
     * @param  Closure|null  $mock
     * @return void
     */
    private function mockEventBridge(Closure $mock = null)
    {
        $eventBridge = m::mock(EventBridgeClient::class, $mock);

        $connection = config('broadcasting.default');
        $broadcaster = m::mock(EventBridgeBroadcaster::class, [
            $eventBridge,
            config("broadcasting.connections.{$connection}.source", ''),
        ])->makePartial();

        $this->swap(Broadcaster::class, $broadcaster);

        $manager = m::mock(BroadcastManager::class, [$this->app], function ($mock) use ($broadcaster) {
            $mock->shouldReceive('connection')->andReturn($broadcaster);
        })->makePartial();

        $this->swap(BroadcastManager::class, $manager);
    }
}
