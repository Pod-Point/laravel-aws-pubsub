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

    /**
     * @var Container
     */
    private $container;

    protected function setUp(): void
    {
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = new Container;
    }

    private function getSqsSnsJobSubjectBinding(): SqsSnsJob
    {
        return new SqsSnsJob(
            $this->container,
            $this->sqsClient,
            [
                'Body' => json_encode([
                    'MessageId' => '4f4749d6-b004-478a-bc38-d934124914b2',
                    'Type' => 'Notification',
                    'TopicArn' => 'TopicArn:123456',
                    'Subject' => 'Subject#action',
                    'Message' => 'The Message',
                ]),
                'ListenerName' => '\\Listener',
            ],
            'connection_name',
            'default_queue'
        );
    }

    private function getSqsSnsJobTopicBinding(): SqsSnsJob
    {
        return new SqsSnsJob(
            $this->container,
            $this->sqsClient,
            [
                'Body' => json_encode([
                    'MessageId' => '4f4749d6-b004-478a-bc38-d934124914b2',
                    'Type' => 'Notification',
                    'TopicArn' => 'TopicArn:123456',
                    'Message' => 'The Message',
                ]),
                'ListenerName' => '\\Listener',
            ],
            'connection_name',
            'default_queue'
        );
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_job()
    {
        $jobPayload = $this->getSqsSnsJobSubjectBinding()->payload();

        $this->assertEquals('Illuminate\\Queue\\CallQueuedHandler@call', $jobPayload['job']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command_name()
    {
        $jobPayload = $this->getSqsSnsJobSubjectBinding()->payload();

        $this->assertEquals('Illuminate\Events\CallQueuedListener', $jobPayload['data']['commandName']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command()
    {
        $jobPayload = $this->getSqsSnsJobSubjectBinding()->payload();

        $expectedCommand = serialize(new CallQueuedListener('\Listener', 'handle', [
            'payload' => null,
            'subject' => 'Subject#action',
        ]));

        $this->assertEquals($expectedCommand, $jobPayload['data']['command']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_job_topic_binding()
    {
        $jobPayload = $this->getSqsSnsJobTopicBinding()->payload();

        $this->assertEquals('Illuminate\\Queue\\CallQueuedHandler@call', $jobPayload['job']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command_name_topic_binding()
    {
        $jobPayload = $this->getSqsSnsJobTopicBinding()->payload();

        $this->assertEquals('Illuminate\Events\CallQueuedListener', $jobPayload['data']['commandName']);
    }

    /** @test */
    public function it_will_resolve_sqs_subscription_command_topic_binding()
    {
        $jobPayload = $this->getSqsSnsJobTopicBinding()->payload();

        $expectedCommand = serialize(new CallQueuedListener('\Listener', 'handle', [
            'payload' => null,
            'subject' => '',
        ]));

        $this->assertEquals($expectedCommand, $jobPayload['data']['command']);
    }

    /** @test */
    public function it_will_leave_default_sqs_job_untouched()
    {
        $defaultSqsJob = new SqsSnsJob(
            $this->container,
            $this->sqsClient,
            [
                'Body' => json_encode([
                    'MessageId' => '123456789',
                    'Message' => 'The Message',
                ]),
                'ListenerName' => '\\Listener',
            ],
            'connection_name',
            'default_queue'
        );

        $jobPayload = $defaultSqsJob->payload();

        $expectedCommand = serialize(new CallQueuedListener('\Listener', 'handle', [
            'payload' => null,
            'subject' => '',
        ]));

        $this->assertEquals([
            'uuid' => '123456789',
            'displayName' => '\Listener',
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => 'Illuminate\Events\CallQueuedListener',
                'command' => $expectedCommand,
            ],
        ], $jobPayload);
    }
}
