<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Events\CallQueuedListener;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\SqsSnsJob;
use PodPoint\AwsPubSub\Tests\TestCase;

class SqsSnsJobTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SqsClient
     */
    private $sqsClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function makeJobWithTopicAndSubject(): SqsSnsJob
    {
        return new SqsSnsJob(new Container, $this->sqsClient, [
            'Body' => json_encode([
                'MessageId' => '4f4749d6-b004-478a-bc38-d934124914b2',
                'Type' => 'Notification',
                'TopicArn' => 'TopicArn:123456',
                'Subject' => 'Subject#action',
                'Message' => '{ "foo": "bar" }',
            ]),
            'ListenerName' => '\\SubjectListener',
        ], 'connection_name', 'default');
    }

    private function makeJobWithTopicOnly(): SqsSnsJob
    {
        return new SqsSnsJob(new Container, $this->sqsClient, [
            'Body' => json_encode([
                'MessageId' => '4f4749d6-b004-478a-bc38-d934124914b2',
                'Type' => 'Notification',
                'TopicArn' => 'TopicArn:123456',
                'Message' => '{ "foo": "bar" }',
            ]),
            'ListenerName' => '\\TopicListener',
        ], 'connection_name', 'default');
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_job()
    {
        $jobPayload = $this->makeJobWithTopicAndSubject()->payload();

        $this->assertEquals('Illuminate\\Queue\\CallQueuedHandler@call', $jobPayload['job']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command_name()
    {
        $jobPayload = $this->makeJobWithTopicAndSubject()->payload();

        $this->assertEquals('Illuminate\Events\CallQueuedListener', $jobPayload['data']['commandName']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command()
    {
        $jobPayload = $this->makeJobWithTopicAndSubject()->payload();

        $expectedCommand = serialize(new CallQueuedListener('\SubjectListener', 'handle', [
            'payload' => ['foo' => 'bar'],
            'subject' => 'Subject#action',
        ]));

        $this->assertEquals($expectedCommand, $jobPayload['data']['command']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_job_topic_binding()
    {
        $jobPayload = $this->makeJobWithTopicOnly()->payload();

        $this->assertEquals('Illuminate\\Queue\\CallQueuedHandler@call', $jobPayload['job']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command_name_topic_binding()
    {
        $jobPayload = $this->makeJobWithTopicOnly()->payload();

        $this->assertEquals(CallQueuedListener::class, $jobPayload['data']['commandName']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command_topic_binding()
    {
        $jobPayload = $this->makeJobWithTopicOnly()->payload();

        $expectedCommand = serialize(new CallQueuedListener('\TopicListener', 'handle', [
            'payload' => ['foo' => 'bar'],
            'subject' => '',
        ]));

        $this->assertEquals($expectedCommand, $jobPayload['data']['command']);
    }

    /** @test */
    public function it_will_leave_default_sqs_job_untouched()
    {
        $jobPayload = $this->makeJobWithTopicOnly()->payload();

        $expectedCommand = serialize(new CallQueuedListener('\TopicListener', 'handle', [
            'payload' => ['foo' => 'bar'],
            'subject' => '',
        ]));

        $this->assertEquals([
            'uuid' => '4f4749d6-b004-478a-bc38-d934124914b2',
            'displayName' => '\TopicListener',
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => CallQueuedListener::class,
                'command' => $expectedCommand,
            ],
        ], $jobPayload);
    }
}
