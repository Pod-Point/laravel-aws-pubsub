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
        $queue = new SqsSnsQueue($this->sqs, 'default');

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }

    /** @test */
    public function it_can_receive_a_rich_notification_message_and_pop_it_off_the_queue()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedRichNotificationMessage());

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();
    }

    /** @test */
    public function it_will_return_null_when_popping_messages_off_the_queue()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedRichNotificationMessage());

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);

        $this->assertNull($queue->pop());
    }

    /** @test */
    public function it_will_not_handle_raw_notification_messages()
    {
        Log::shouldReceive('error')->once()->with(
            m::pattern('/^SqsSnsQueue: Invalid SNS payload/'),
            m::type('array')
        );
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedRawNotificationMessage());

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();
    }

    /** @test */
    public function it_can_dispatch_an_event_using_the_topic_and_forward_the_message_payload()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedRichNotificationMessage([
                'TopicArn' => 'TopicArn:123456',
                'Message' => json_encode(['foo' => 'bar']),
            ]));

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('TopicArn:123456', function ($event, $args) {
            return $args === [
                'subject' => '',
                'payload' => ['foo' => 'bar'],
            ];
        });
    }

    /** @test */
    public function it_can_dispatch_an_event_using_the_subject_if_found_in_the_notification_payload()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedRichNotificationMessage([
                'Subject' => 'Subject#action',
                'Message' => json_encode(['foo' => 'bar']),
            ]));

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('Subject#action', function ($event, $payload) {
            return $payload === [
                'subject' => 'Subject#action',
                'payload' => ['foo' => 'bar'],
            ];
        });
        Event::assertNotDispatched('TopicArn:123456');
    }

    /** @test */
    public function it_dispatches_an_event_using_the_topic_if_no_subject_can_be_found()
    {
        $this->sqs->shouldReceive('receiveMessage')
            ->once()
            ->andReturn($this->mockedRichNotificationMessage([
                'TopicArn' => 'TopicArn:123456',
            ]));

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);
        $queue->pop();

        Event::assertDispatched('TopicArn:123456');
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

        $queue = new SqsSnsQueue($this->sqs, 'default');
        $queue->setContainer($this->app);
        $queue->$method(...$args);
    }

    private function mockedRichNotificationMessage(array $attributes = []): \Aws\Result
    {
        $attributes = array_merge([
            'Type' => 'Notification',
            'TopicArn' => $this->faker->word,
            'Message' => json_encode(['foo' => 'bar']),
            'MessageId' => $this->faker->uuid,
        ], $attributes);

        return new \Aws\Result([
            'Messages' => [
                ['Body' => json_encode(array_filter($attributes))],
            ],
        ]);
    }

    private function mockedRawNotificationMessage(): \Aws\Result
    {
        return new \Aws\Result([
            'Messages' => [
                ['Body' => json_encode(['foo' => 'bar'])],
            ],
        ]);
    }
}
