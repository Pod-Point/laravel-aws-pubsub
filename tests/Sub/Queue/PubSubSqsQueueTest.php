<?php

namespace PodPoint\AwsPubSub\Tests\Sub\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery as m;
use PodPoint\AwsPubSub\Sub\EventDispatchers\EventDispatcher;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\EventDispatcherJob;
use PodPoint\AwsPubSub\Sub\Queue\PubSubSqsQueue;
use PodPoint\AwsPubSub\Tests\Sub\Concerns\MocksNotificationMessages;
use PodPoint\AwsPubSub\Tests\TestCase;

class PubSubSqsQueueTest extends TestCase
{
    use MocksNotificationMessages;

    /**
     * @var SqsClient|m\LegacyMockInterface|m\MockInterface
     */
    protected $sqs;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->sqs = m::mock(SqsClient::class);
    }

    /** @test */
    public function it_can_instantiate_the_queue()
    {
        $queue = $this->getQueue();

        $this->assertInstanceOf(PubSubSqsQueue::class, $queue);
    }

    /** @test */
    public function it_can_receive_a_rich_notification_message_and_pop_it_off_the_queue()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->with(['QueueUrl' => '/default', 'AttributeNames' => ['ApproximateReceiveCount']])
            ->andReturn($this->mockedRichNotificationMessage());

        $queue = $this->getQueue(['sqs' => $this->sqs, 'default' => 'default']);

        $result = $queue->pop();

        $this->assertInstanceOf(EventDispatcherJob::class, $result);
        $this->assertEquals('/default', $result->getQueue());
    }

    /** @test */
    public function it_should_use_the_queue_name_including_prefix_and_suffix()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->with(['QueueUrl' => 'prefix/default-suffix', 'AttributeNames' => ['ApproximateReceiveCount']])
            ->andReturn($this->mockedRichNotificationMessage());

        $queue = $this->getQueue([
            'sqs' => $this->sqs, 'default' => 'default', 'prefix' => 'prefix', 'suffix' => '-suffix',
        ]);

        $result = $queue->pop();

        $this->assertInstanceOf(EventDispatcherJob::class, $result);
        $this->assertEquals('prefix/default-suffix', $result->getQueue());
    }

    /** @test */
    public function it_properly_handles_empty_message_when_popping_it_off_the_queue()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedEmptyNotificationMessage());

        $queue = $this->getQueue(['sqs' => $this->sqs, 'default' => 'default']);

        $this->assertNull($queue->pop());
    }

    /** @test */
    public function it_provides_the_job_with_its_event_dispatcher()
    {
        $eventDispatcher = m::mock(EventDispatcher::class);

        $queue = $this->getQueue(['eventDispatcher' => $eventDispatcher]);

        $this->assertEquals($eventDispatcher, $queue->pop()->getEventDispatcher());
    }

    public function readOnlyDataProvider(): array
    {
        return [
            'pushRaw' => ['pushRaw', ['foo' => 'bar']],
            'push' => ['push', 'job'],
            'later' => ['later', 123, 'job'],
        ];
    }

    /** @test @dataProvider readOnlyDataProvider */
    public function it_is_a_read_only_queue_driver_and_will_not_push_messages_onto_a_queue(string $method, ...$args)
    {
        Log::shouldReceive('error')->once()->with('Unsupported: sqs-sns queue driver is read-only');
        $this->sqs->shouldNotReceive('sendMessage');

        $queue = $this->getQueue(['sqs' => $this->sqs, 'default' => 'default']);

        $queue->$method(...$args);
    }

    public function getQueue($parameterOverrides = []): PubSubSqsQueue
    {
        $sqs = tap(m::mock(SqsClient::class), function ($sqs) {
            return $sqs->shouldReceive('receiveMessage')->andReturn($this->mockedRichNotificationMessage());
        });

        $parameters = array_merge([
            'sqs' => $sqs,
            'default' => '',
            'prefix' => '',
            'suffix' => '',
            'dispatchAfterCommit' => false,
            'eventDispatcher' => m::mock(EventDispatcher::class),
        ], $parameterOverrides);

        return tap(new PubSubSqsQueue(
            $parameters['sqs'],
            $parameters['default'],
            $parameters['prefix'],
            $parameters['suffix'],
            $parameters['dispatchAfterCommit'],
            $parameters['eventDispatcher'],
        ), function ($queue) {
            $queue->setContainer($this->app);
        });
    }
}
