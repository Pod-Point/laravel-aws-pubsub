<?php

namespace PodPoint\AwsPubSub\Tests\Sub\Queue\Connectors;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Jobs\SqsJob;
use PodPoint\AwsPubSub\Sub\EventDispatcherManager;
use PodPoint\AwsPubSub\Sub\EventDispatchers\EventDispatcher;
use PodPoint\AwsPubSub\Sub\EventDispatchers\SnsEventDispatcher;
use PodPoint\AwsPubSub\Sub\Queue\Connectors\PubSubSqsConnector;
use PodPoint\AwsPubSub\Sub\Queue\PubSubSqsQueue;
use PodPoint\AwsPubSub\Tests\TestCase;

class PubSubSqsConnectorTest extends TestCase
{
    /** @test */
    public function it_can_instantiate_the_connector_and_connect_to_the_queue()
    {
        $queue = $this->getConnector()->connect(config('queue.connections.pub_sub'));

        $this->assertInstanceOf(PubSubSqsQueue::class, $queue);
    }

    /** @test */
    public function it_can_use_a_queue_prefix()
    {
        $queue = $this->getConnector()->connect(config('queue.connections.pub_sub'));

        $this->assertEquals('https://sqs.eu-west-1.amazonaws.com/13245/default', $queue->getQueue(null));
    }

    /** @test */
    public function it_can_use_a_queue_suffix()
    {
        config(['queue.connections.pub_sub.suffix' => '-testing']);

        $queue = $this->getConnector()->connect(config('queue.connections.pub_sub'));

        $this->assertEquals('https://sqs.eu-west-1.amazonaws.com/13245/default-testing', $queue->getQueue(null));
    }

    /** @test */
    public function it_creates_a_queue_using_the_event_dispatcher_from_config()
    {
        $testDispatcher = $this->getEventDispatcher();

        $manager = tap($this->app->make(EventDispatcherManager::class), function ($manager) use ($testDispatcher) {
            $manager->extend('test_dispatcher', function () use ($testDispatcher) {
                return $testDispatcher;
            });
        });

        config(['queue.connections.pub_sub.dispatcher' => 'test_dispatcher']);

        $queue = $this->getConnector($manager)->connect(config('queue.connections.pub_sub'));

        $this->assertEquals($testDispatcher, $queue->getEventDispatcher());
    }

    /** @test */
    public function it_uses_the_sns_event_dispatcher_by_default()
    {
        $queue = $this->getConnector()->connect(config('queue.connections.pub_sub'));

        $this->assertInstanceOf(SnsEventDispatcher::class, $queue->getEventDispatcher());
    }

    private function getConnector(?EventDispatcherManager $manager = null): PubSubSqsConnector
    {
        return new PubSubSqsConnector($manager ?? $this->app->make(EventDispatcherManager::class));
    }

    private function getEventDispatcher()
    {
        return new class implements EventDispatcher
        {
            public function dispatch(SqsJob $job, Dispatcher $dispatcher): void
            {
            }
        };
    }
}
