<?php

namespace PodPoint\AwsPubSub\Tests;

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Mockery as m;
use PodPoint\AwsPubSub\Sub\Queue\Connectors\PubSubSqsConnector;

class EventServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_a_queue_connector()
    {
        $queue = tap(m::mock(Queue::class), function ($queue) {
            $queue->shouldReceive('setContainer')->andReturnSelf();
            $queue->shouldReceive('setConnectionName')->andReturnSelf();
        });

        $this->app->instance(PubSubSqsConnector::class, m::mock(ConnectorInterface::class, [
            'connect' => $queue,
        ]));

        $this->assertEquals($queue, $this->app->make('queue')->connection('sqs_pub_sub'));
    }
}
