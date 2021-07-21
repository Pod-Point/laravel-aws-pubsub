<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Mockery as m;
use PodPoint\AwsPubSub\Sub\Queue\SqsSnsQueue;
use PodPoint\AwsPubSub\Sub\Queue\Jobs\SqsSnsJob;
use PodPoint\AwsPubSub\Tests\TestCase;

class SqsSnsQueueTest extends TestCase
{
    /**
     * @var SqsClient|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockedSqsClient;

    /**
     * @var array
     */
    protected $mockedMessageDataWithTopicOnly;

    /**
     * @var array
     */
    protected $mockedMessageDataWithTopicAndSubject;

    protected function setUp():void
    {
        $this->mockedSqsClient = $this->getMockBuilder(SqsClient::class)
            ->addMethods(['receiveMessage', 'deleteMessage'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockedMessageDataWithTopicOnly = [
            'Body' => json_encode([
                'Type' => 'Notification',
                'TopicArn' => 'TopicArn:123456',
                'Message' => 'foo',
                'MessageId' => 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81',
            ]),
        ];
        $this->mockedMessageDataWithTopicAndSubject = [
            'Body' => json_encode([
                'Type' => 'Notification',
                'TopicArn' => 'TopicArn:123456',
                'Subject' => 'Subject#action',
                'Message' => 'foo',
                'MessageId' => 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81',
            ]),
        ];
    }

    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function it_can_instantiate_the_queue()
    {
        $queue = new SqsSnsQueue($this->mockedSqsClient, 'default');

        $this->assertInstanceOf(SqsSnsQueue::class, $queue);
    }

    /** @test */
    public function it_will_set_the_event_listeners_mapping()
    {
        $queue = new SqsSnsQueue($this->mockedSqsClient, 'default', '', '', [
            "Subject#action" => '\\SubjectListener',
        ]);

        $queueReflection = new \ReflectionClass($queue);
        $eventsProperty = $queueReflection->getProperty('events');
        $eventsProperty->setAccessible(true);

        $this->assertEquals(["Subject#action" => '\\SubjectListener'], $eventsProperty->getValue($queue));
    }

    /** @test */
    public function it_will_resolve_the_listener_name_based_on_the_topic_arn()
    {
        $this->mockedSqsClient
            ->method('receiveMessage')
            ->willReturn(new \Aws\Result([
                'Messages' => [$this->mockedMessageDataWithTopicOnly],
            ]));

        $queue = new SqsSnsQueue($this->mockedSqsClient, 'default', '', '', [
            "TopicArn:123456" => '\\TopicListener',
        ]);
        $queue->setContainer($this->createMock(Container::class));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsSnsJob::class, $job);
        $this->assertEquals('\\TopicListener', $job->getSqsJob()['ListenerName']);
    }

    /** @test */
    public function it_will_resolve_the_listener_name_based_on_the_subject_first_if_present()
    {
        $this->mockedSqsClient
            ->method('receiveMessage')
            ->willReturn(new \Aws\Result([
                'Messages' => [$this->mockedMessageDataWithTopicAndSubject],
            ]));

        $queue = new SqsSnsQueue($this->mockedSqsClient, 'default', '', '', [
            "Subject#action" => '\\SubjectListener',
            "TopicArn:123456" => '\\TopicListener',
        ]);
        $queue->setContainer($this->createMock(Container::class));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsSnsJob::class, $job);
        $this->assertEquals('\\SubjectListener', $job->getSqsJob()['ListenerName']);
    }

    /** @test */
    public function it_can_receive_a_message_and_pop_it_off_the_queue()
    {
        $this->mockedSqsClient
            ->method('receiveMessage')
            ->willReturn(new \Aws\Result([
                'Messages' => [$this->mockedMessageDataWithTopicOnly],
            ]));

        $queue = new SqsSnsQueue($this->mockedSqsClient, 'default', '', '', [
            "TopicArn:123456" => '\\TopicListener',
        ]);
        $queue->setContainer($this->createMock(Container::class));

        $job = $queue->pop();

        $this->assertInstanceOf(SqsSnsJob::class, $job);
    }
}
