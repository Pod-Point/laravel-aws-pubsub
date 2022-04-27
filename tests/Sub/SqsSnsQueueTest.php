<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery as m;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;
use PodPoint\AwsPubSub\Tests\TestCase;

class SqsSnsQueueTest extends TestCase
{
    /**
     * @var array
     */
    protected $mockedMessageDataWithTopicOnly;

    /**
     * @var array
     */
    protected $mockedMessageDataWithTopicAndSubject;

    /**
     * @var array
     */
    protected $mockedRawMessageData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockedMessageDataWithTopicOnly = [
            'Body' => json_encode([
                'Type' => 'Notification',
                'TopicArn' => 'TopicArn:123456',
                'Message' => '{ "foo": "bar" }',
                'MessageId' => 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81',
            ]),
        ];

        $this->mockedMessageDataWithTopicAndSubject = [
            'Body' => json_encode([
                'Type' => 'Notification',
                'TopicArn' => 'TopicArn:123456',
                'Subject' => 'Subject#action',
                'Message' => '{ "foo": "bar" }',
                'MessageId' => 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81',
            ]),
        ];

        $this->mockedRawMessageData = [
            'Body' => json_encode([
                'foo' => 'bar',
            ]),
        ];
    }

    /** @test */
    public function it_can_instantiate_the_queue()
    {
        $queue = new SqsSnsQueue(m::mock(SqsClient::class), 'default');

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }

    /** @test */
    public function it_can_receive_a_message_and_pop_it_off_the_queue_which_should_dispatch_an_internal_event()
    {
        Event::fake();
        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedMessageDataWithTopicOnly],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('TopicArn:123456');
    }

    /** @test */
    public function it_can_dispatch_an_internal_event_for_topic_based_events()
    {
        Event::fake();
        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedMessageDataWithTopicOnly],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('TopicArn:123456', function ($event, $payload) {
            return $payload === [
                'payload' => ['foo' => 'bar'],
                'subject' => '',
            ];
        });
    }

    /** @test */
    public function it_can_dispatch_an_internal_event_for_subject_based_events()
    {
        Event::fake();
        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedMessageDataWithTopicAndSubject],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('Subject#action', function ($event, $payload) {
            return $payload === [
                'payload' => ['foo' => 'bar'],
                'subject' => 'Subject#action',
            ];
        });
    }

    /** @test */
    public function it_dispatches_a_subject_based_events_over_topic_based()
    {
        Event::fake();
        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedMessageDataWithTopicAndSubject],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('Subject#action');
        Event::assertNotDispatched('TopicArn:123456');
    }

    /** @test */
    public function it_dispatches_a_topic_based_event_if_no_subject_can_be_found()
    {
        Event::fake();
        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedMessageDataWithTopicOnly],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('TopicArn:123456');
        Event::assertNotDispatched('Subject#action');
    }

    /** @test */
    public function it_will_only_handle_rich_notifications_not_the_ones_with_raw_payloads()
    {
        Log::shouldReceive('error')->once()->with(
            m::pattern('/^SqsSnsQueue: Invalid SNS payload/'),
            m::type('array')
        );

        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedRawMessageData],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();
    }

    /** @test */
    public function it_will_never_return_a_job_when_popping_messages_out_from_the_queue()
    {
        $sqs = m::mock(SqsClient::class);
        $sqs->shouldReceive('receiveMessage')->once()->andReturn(new \Aws\Result([
            'Messages' => [$this->mockedMessageDataWithTopicOnly],
        ]));

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);

        $this->assertNull($queue->pop());
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
    public function it_is_a_read_only_queue_driver_and_will_not_push_messages_onto_the_queue(string $method, ...$args)
    {
        Log::shouldReceive('error')->once()->with('Unsupported: sqs-sns queue driver is read-only');

        $sqs = m::mock(SqsClient::class);
        $sqs->shouldNotReceive('sendMessage');

        $queue = new SqsSnsQueue($sqs, 'default');
        $queue->setContainer($this->app);
        $queue->$method(...$args);
    }
}
