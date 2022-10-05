<?php

namespace PodPoint\AwsPubSub\Tests\Pub\Concerns;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Foundation\Application;
use Mockery as m;
use PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters\SnsBroadcaster;

trait InteractsWithSns
{
    /**
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app)
    {
        $app->config->set('broadcasting.default', 'sns');
        $app->config->set('broadcasting.connections.sns', [
            'driver' => 'sns',
            'key' => 'dummy-key',
            'secret' => 'dummy-secret',
            'arn-prefix' => 'aws:arn:12345:',
            'region' => 'eu-west-1',
        ]);
    }

    /**
     * Mocks the SnsClient through the SnsBroadcaster and the BroadcastManager.
     *
     * @param  \Closure|null  $mock
     * @return void
     */
    private function mockSns(\Closure $mock = null)
    {
        $sns = m::mock(SnsClient::class, $mock);

        $connection = config('broadcasting.default');
        $broadcaster = m::mock(SnsBroadcaster::class, [
            $sns,
            config("broadcasting.connections.{$connection}.arn-prefix", ''),
            config("broadcasting.connections.{$connection}.arn-suffix", ''),
        ])->makePartial();

        $this->swap(BroadcasterContract::class, $broadcaster);

        $manager = m::mock(BroadcastManager::class, [$this->app], function ($mock) use ($broadcaster) {
            $mock->shouldReceive('connection')->andReturn($broadcaster);
        })->makePartial();

        $this->swap(BroadcastManager::class, $manager);
    }
}
