<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use PodPoint\AwsPubSub\Sub\Queue\Connectors\SqsSnsConnector;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;
use PodPoint\AwsPubSub\Tests\TestCase;

class SqsSnsConnectorTest extends TestCase
{
    /** @test */
    public function it_can_instantiate_the_connector()
    {
        $connector = new SqsSnsConnector();

        $this->assertInstanceOf(SqsSnsConnector::class, $connector);
    }

    /** @test */
    public function it_can_connect_to_the_queue()
    {
        $connector = new SqsSnsConnector();

        $queue = $connector->connect([
            'key' => 'dummy_key',
            'secret' => 'dummy_secret',
            'region' => 'us-west-2',
            'queue' => '',
        ]);

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }
}
