<?php

namespace PodPoint\AwsPubSub\Tests\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery as m;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\SnsEventDispatcherJob;
use PodPoint\AwsPubSub\Tests\Sub\Concerns\MocksNotificationMessages;
use PodPoint\AwsPubSub\Tests\TestCase;

class SnsEventDispatcherJobTest extends TestCase
{
    use MocksNotificationMessages;

    /**
     * @var array
     */
    protected $mockedJobData = [];

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    /** @test */
    public function it_will_not_handle_raw_notification_messages()
    {
        Log::shouldReceive('error')->once()->with(
            m::pattern('/^SqsSnsQueue: Invalid SNS payload/'),
            m::type('array')
        );

        $this->mockedJobData = $this->mockedRawNotificationMessage()['Messages'][0];

        $this->getJob()->fire();

        Event::assertNothingDispatched();
    }

    /** @test */
    public function it_will_not_handle_messages_where_the_event_name_to_trigger_cannot_be_resolved()
    {
        $this->mockedJobData = $this->mockedRichNotificationMessage([
            'TopicArn' => '',
            'Subject' => '',
        ])['Messages'][0];

        $this->getJob()->fire();

        Event::assertNothingDispatched();
    }

    /** @test */
    public function it_can_dispatch_an_event_using_the_topic_and_forward_the_message_payload()
    {
        $this->mockedJobData = $this->mockedRichNotificationMessage([
            'TopicArn' => 'TopicArn:123456',
            'Message' => json_encode(['foo' => 'bar']),
        ])['Messages'][0];

        $this->getJob()->fire();

        Event::assertDispatched('TopicArn:123456', function ($event, $args) {
            return $args === [
                'payload' => ['foo' => 'bar'],
                'subject' => '',
            ];
        });
    }

    /** @test */
    public function it_can_dispatch_an_event_using_the_subject_if_found_in_the_notification_payload()
    {
        $this->mockedJobData = $this->mockedRichNotificationMessage([
            'TopicArn' => 'TopicArn:123456',
            'Subject' => 'Subject#action',
            'Message' => json_encode(['foo' => 'bar']),
        ])['Messages'][0];

        $this->getJob()->fire();

        Event::assertDispatched('Subject#action', function ($event, $payload) {
            return $payload === [
                'payload' => ['foo' => 'bar'],
                'subject' => 'Subject#action',
            ];
        });
        Event::assertNotDispatched('TopicArn:123456');
    }

    /** @test */
    public function it_dispatches_an_event_using_the_topic_if_no_subject_can_be_found()
    {
        $this->mockedJobData = $this->mockedRichNotificationMessage([
            'TopicArn' => 'TopicArn:123456',
        ])['Messages'][0];

        $this->getJob()->fire();

        Event::assertDispatched('TopicArn:123456');
    }

    protected function getJob()
    {
        return new SnsEventDispatcherJob(
            $this->app,
            m::mock(SqsClient::class),
            $this->mockedJobData,
            'connection-name',
            'https://sqs.someregion.amazonaws.com/1234567891011/pubsub-events'
        );
    }
}
