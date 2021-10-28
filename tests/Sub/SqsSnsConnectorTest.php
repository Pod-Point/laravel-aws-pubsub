<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use PodPoint\AwsPubSub\Sub\Queue\Connectors\SqsSnsConnector;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;
use PodPoint\AwsPubSub\Tests\TestCase;

class SqsSnsConnectorTest extends TestCase
{
    /** @test */
    public function it_can_instantiate_the_connector_and_connect_to_the_queue()
    {
        $queue = (new SqsSnsConnector)->connect(config('queue.connections.pub-sub'));

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }

    /** @test */
    public function it_can_use_a_queue_prefix()
    {
        $queue = (new SqsSnsConnector)->connect(config('queue.connections.pub-sub'));

        $this->assertEquals('https://sqs.eu-west-1.amazonaws.com/13245/default', $queue->getQueue(null));
    }

    /** @test */
    public function it_can_use_a_queue_suffix()
    {
        config(['queue.connections.pub-sub.suffix' => '-testing']);
        dd(config('queue.connections.pub-sub'));
        $queue = (new SqsSnsConnector)->connect(config('queue.connections.pub-sub'));

        $this->assertEquals('https://sqs.eu-west-1.amazonaws.com/13245/default-testing', $queue->getQueue(null));
    }
}
