<?php

namespace PodPoint\AwsPubSub\Tests\Sub\Queue\Connectors;

use PodPoint\AwsPubSub\Sub\Queue\Connectors\PubSubSqsConnector;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;
use PodPoint\AwsPubSub\Tests\TestCase;

class PubSubSqsConnectorTest extends TestCase
{
    /** @test */
    public function it_can_instantiate_the_connector_and_connect_to_the_queue()
    {
        $queue = (new PubSubSqsConnector)->connect(config('queue.connections.pub-sub'));

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }

    /** @test */
    public function it_can_use_a_queue_prefix()
    {
        $queue = (new PubSubSqsConnector)->connect(config('queue.connections.pub-sub'));

        $this->assertEquals('https://sqs.eu-west-1.amazonaws.com/13245/default', $queue->getQueue(null));
    }

    /** @test */
    public function it_can_use_a_queue_suffix()
    {
        config(['queue.connections.pub-sub.suffix' => '-testing']);

        $queue = (new PubSubSqsConnector)->connect(config('queue.connections.pub-sub'));

        $this->assertEquals('https://sqs.eu-west-1.amazonaws.com/13245/default-testing', $queue->getQueue(null));
    }
}
