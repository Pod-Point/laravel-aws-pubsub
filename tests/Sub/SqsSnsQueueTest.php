<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\SqsSnsJob;
use PodPoint\AwsPubSub\Tests\TestCase;

class SqsSnsQueueTest extends TestCase
{
    /**
     * @var SqsClient|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sqsClient;

    protected function setUp():void
    {
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['receiveMessage'])
            ->getMock();
    }

    /** @test */
    public function it_can_instantiate_the_queue()
    {
        $queue = new SqsSnsQueue($this->sqsClient, 'default_queue');

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }

    /** @test */
    public function it_will_set_the_events()
    {
        $queue = new SqsSnsQueue($this->sqsClient, 'default_queue', '', '', [
            "Subject#action" => '\\Listener',
        ]);

        $queueReflection = new \ReflectionClass($queue);
        $routeReflectionProperty = $queueReflection->getProperty('events');
        $routeReflectionProperty->setAccessible(true);

        $this->assertEquals(["Subject#action" => '\\Listener'], $routeReflectionProperty->getValue($queue));
    }

    /** @test */
    public function it_will_resolve_the_listener_name_based_on_the_subject()
    {
        $message = ['Body' => '{"Subject": "Subject#action", "Type": "Notification", "MessageId": "123456789", "Message": "Foo bar"}'];

        $this->sqsClient
            ->method('receiveMessage')
            ->willReturn(new \Aws\Result(['Messages' => [$message]]));

        $queue = new SqsSnsQueue($this->sqsClient, 'default_queue', '', '', [
            "Subject#action" => '\\Listener',
        ]);
        $queue->setContainer($this->createMock(Container::class));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsSnsJob::class, $job);
        $this->assertEquals('\\Listener', $job->getSqsJob()['ListenerName']);
    }

    /** @test */
    public function it_will_resolve_the_listener_name_based_on_the_topic_arn()
    {
        $message = ['Body' => '{"TopicArn": "TopicArn:123456", "Type": "Notification", "MessageId": "123456789", "Message": "Foo bar"}'];

        $this->sqsClient
            ->method('receiveMessage')
            ->willReturn(new \Aws\Result(['Messages' => [$message]]));

        $queue = new SqsSnsQueue($this->sqsClient, 'default_queue', '', '', [
            "TopicArn:123456" => '\\Listener',
        ]);
        $queue->setContainer($this->createMock(Container::class));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsSnsJob::class, $job);
        $this->assertEquals('\\Listener', $job->getSqsJob()['ListenerName']);
    }

    /** @test */
    public function it_can_receive_a_message_and_pop_it_off_the_queue()
    {
        $message = ['Body' => '{"TopicArn": "TopicArn:123456", "Type": "Notification", "MessageId": "123456789", "Message": "Foo bar"}'];

        $this->sqsClient
            ->expects($this->once())
            ->method('receiveMessage')
            ->willReturn(new \Aws\Result(['Messages' => [$message]]));

        $queue = new SqsSnsQueue($this->sqsClient, 'default_queue', '', '', [
            "TopicArn:123456" => '\\Listener',
        ]);
        $queue->setContainer($this->createMock(Container::class));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsSnsJob::class, $job);
    }
}
