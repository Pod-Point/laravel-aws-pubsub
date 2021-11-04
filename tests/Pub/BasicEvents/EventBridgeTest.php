<?php

namespace PodPoint\AwsPubSub\Tests\Pub\BasicEvents;

use Aws\EventBridge\EventBridgeClient;
use Illuminate\Foundation\Application;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use PodPoint\AwsPubSub\Tests\Pub\Concerns\InteractsWithEventBridge;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrieved;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithCustomName;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithCustomPayload;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithMultipleChannels;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\User;
use PodPoint\AwsPubSub\Tests\TestCase;

class EventBridgeTest extends TestCase
{
    use InteractsWithEventBridge;

    /**
     * @param  Application  $app
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

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(Mockery::on(function ($arg) use ($janeRetrieved) {
                    return $arg['Entries'][0]['Detail'] === json_encode($janeRetrieved)
                        && $arg['Entries'][0]['DetailType'] === UserRetrieved::class
                        && $arg['Entries'][0]['EventBusName'] === 'users'
                        && $arg['Entries'][0]['Source'] === 'my-app';
                }));
        });

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrievedWithCustomName($jane);

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(Mockery::on(function ($arg) use ($janeRetrieved) {
                    return $arg['Entries'][0]['Detail'] === json_encode($janeRetrieved)
                        && $arg['Entries'][0]['DetailType'] === 'user.retrieved'
                        && $arg['Entries'][0]['EventBusName'] === 'users'
                        && $arg['Entries'][0]['Source'] === 'my-app';
                }));
        });

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action_and_custom_payload()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrievedWithCustomPayload($jane);

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(Mockery::on(function ($arg) use ($janeRetrieved) {
                    $customPayload = array_merge($janeRetrieved->broadcastWith(), ['socket' => null]);

                    return $arg['Entries'][0]['Detail'] === json_encode($customPayload)
                        && $arg['Entries'][0]['DetailType'] === UserRetrievedWithCustomPayload::class
                        && $arg['Entries'][0]['EventBusName'] === 'users'
                        && $arg['Entries'][0]['Source'] === 'my-app';
                }));
        });

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_to_multiple_channels_as_buses()
    {
        $jane = $this->createJane();

        $janeRetrieved = new UserRetrievedWithMultipleChannels($jane);

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(Mockery::on(function ($arg) use ($janeRetrieved) {
                    return collect($janeRetrieved->broadcastOn())
                        ->map(function ($channel, $key) use ($arg, $janeRetrieved) {
                            return $arg['Entries'][$key]['Detail'] === json_encode($janeRetrieved)
                                && $arg['Entries'][$key]['DetailType'] === UserRetrievedWithMultipleChannels::class
                                && $arg['Entries'][$key]['EventBusName'] === $channel
                                && $arg['Entries'][$key]['Source'] === 'my-app';
                        })
                        ->filter()
                        ->count() > 0;
                }));
        });

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
