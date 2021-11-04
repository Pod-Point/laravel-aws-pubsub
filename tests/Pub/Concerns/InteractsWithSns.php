<?php

namespace PodPoint\AwsPubSub\Tests\Pub\Concerns;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Mockery as m;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\SnsBroadcaster;

trait InteractsWithSns
{
    /**
     * Mocks the SnsClient through the SnsBroadcaster and the BroadcastManager.
     *
     * @param  \Closure|null  $mock
     * @return void
     */
    private function mockSns(\Closure $mock = null)
    {
        $sns = m::mock(SnsClient::class, $mock);

        $broadcaster = m::mock(SnsBroadcaster::class, [$sns])->makePartial();

        $this->swap(BroadcasterContract::class, $broadcaster);

        $manager = m::mock(BroadcastManager::class, [$this->app], function ($mock) use ($broadcaster) {
            $mock->shouldReceive('connection')->andReturn($broadcaster);
        })->makePartial();

        $this->swap(BroadcastManager::class, $manager);
    }
}
