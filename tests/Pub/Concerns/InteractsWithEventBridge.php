<?php

namespace PodPoint\AwsPubSub\Tests\Pub\Concerns;

use Aws\EventBridge\EventBridgeClient;
use Closure;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Mockery;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\EventBridgeBroadcaster;

trait InteractsWithEventBridge
{
    /**
     * @param  Closure|null $mock
     * @return void
     */
    private function mockEventBridge(Closure $mock = null)
    {
        $eventBridge = Mockery::mock(EventBridgeClient::class, $mock);

        $broadcaster = Mockery::mock(
            EventBridgeBroadcaster::class,
            [$eventBridge, config('broadcasting.connections.eventbridge.source')]
        )->makePartial();

        $this->swap(Broadcaster::class, $broadcaster);

        $manager = Mockery::mock(BroadcastManager::class, [$this->app], function ($mock) use ($broadcaster) {
            $mock->shouldReceive('connection')->andReturn($broadcaster);
        })->makePartial();

        $this->swap(BroadcastManager::class, $manager);
    }
}
