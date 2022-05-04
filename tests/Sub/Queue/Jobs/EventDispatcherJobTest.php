<?php

namespace PodPoint\AwsPubSub\Tests\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery as m;
use PodPoint\AwsPubSub\Sub\EventDispatchers\EventDispatcher;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\EventDispatcherJob;
use PodPoint\AwsPubSub\Tests\TestCase;

class EventDispatcherJobTest extends TestCase
{
    /** @test */
    public function it_calls_the_provided_event_dispatcher()
    {
        $eventDispatcher = m::spy(EventDispatcher::class);

        $job = $this->getJob($eventDispatcher);

        $job->fire();

        $eventDispatcher->shouldHaveReceived('dispatch')
            ->once()
            ->with($job, $this->app->make(Dispatcher::class));
    }

    protected function getJob(EventDispatcher $eventDispatcher)
    {
        return new EventDispatcherJob(
            $this->app,
            m::mock(SqsClient::class),
            [],
            'connection-name',
            'https://sqs.someregion.amazonaws.com/1234567891011/pubsub-events',
            $eventDispatcher,
        );
    }
}
