<?php

namespace PodPoint\AwsPubSub\Tests\Pub\BasicEvents;

use Aws\EventBridge\EventBridgeClient;
use Mockery\MockInterface;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrieved;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithCustomName;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithCustomPayload;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithMultipleChannels;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\User;
use PodPoint\AwsPubSub\Tests\TestCase;

class EventBridgeTest extends TestCase
{
    /**
     * @var MockInterface
     */
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->mock(EventBridgeClient::class);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->setEventBridgeBroadcaster($app);

        $this->setTestDatabase($app);
    }

    /** @test */
    public function it_broadcasts_basic_event_with_the_event_name_as_the_detail_type_and_serialised_event_as_the_detail()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrieved($jane);

        $expectedPayload = [
            'Entries' => [
                [
                    'Detail' => json_encode($janeRetrieved),
                    'DetailType' => UserRetrieved::class,
                    'EventBusName' => 'users',
                    'Source' => 'my-app',
                ],
            ],
        ];

        $this->mockClient
            ->shouldReceive('putEvents')
            ->once()
            ->with($expectedPayload);

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrievedWithCustomName($jane);

        $expectedPayload = [
            'Entries' => [
                [
                    'Detail' => json_encode($janeRetrieved),
                    'DetailType' => 'user.retrieved',
                    'EventBusName' => 'users',
                    'Source' => 'my-app',
                ],
            ],
        ];

        $this->mockClient
            ->shouldReceive('putEvents')
            ->once()
            ->with($expectedPayload);

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action_and_custom_payload()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrievedWithCustomPayload($jane);

        $expectedPayload = [
            'Entries' => [
                [
                    'Detail' => json_encode([
                        'action' => 'retrieved',
                        'data' => [
                            'user' => $jane->toArray(),
                            'foo' => 'baz',
                        ],
                        'socket' => null,
                    ]),
                    'DetailType' => UserRetrievedWithCustomPayload::class,
                    'EventBusName' => 'users',
                    'Source' => 'my-app',
                ],
            ],
        ];

        $this->mockClient
            ->shouldReceive('putEvents')
            ->once()
            ->with($expectedPayload);

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_to_multiple_channels_as_buses()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrievedWithMultipleChannels($jane);

        $expectedPayload = [
            'Entries' => [
                [
                    'Detail' => json_encode($janeRetrieved),
                    'DetailType' => UserRetrievedWithMultipleChannels::class,
                    'EventBusName' => 'users',
                    'Source' => 'my-app',
                ],
                [
                    'Detail' => json_encode($janeRetrieved),
                    'DetailType' => UserRetrievedWithMultipleChannels::class,
                    'EventBusName' => 'customers',
                    'Source' => 'my-app',
                ],
            ],
        ];

        $this->mockClient
            ->shouldReceive('putEvents')
            ->with($expectedPayload);

        event($janeRetrieved);
    }

    /**
     * @return User
     */
    protected function createJane()
    {
        return User::create([
            'name' => 'Jane',
            'email' => 'jane@doe.com',
            'password' => 'shh',
        ])->fresh();
    }
}
