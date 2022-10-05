<?php

namespace PodPoint\AwsPubSub\Tests\Pub\BasicEvents;

use Mockery as m;
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

    /** @test */
    public function it_broadcasts_basic_event_with_the_event_name_as_the_detail_type_and_serialised_event_as_the_detail()
    {
        $janeRetrieved = new UserRetrieved($this->createJane());

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(m::on(function ($arg) use ($janeRetrieved) {
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
        $janeRetrieved = new UserRetrievedWithCustomName($this->createJane());

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(m::on(function ($arg) use ($janeRetrieved) {
                    return $arg['Entries'][0]['Detail'] === json_encode($janeRetrieved)
                        && $arg['Entries'][0]['DetailType'] === 'user.retrieved';
                }));
        });

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action_and_custom_payload()
    {
        $janeRetrieved = new UserRetrievedWithCustomPayload($this->createJane());

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(m::on(function ($arg) use ($janeRetrieved) {
                    $customPayload = array_merge($janeRetrieved->broadcastWith(), ['socket' => null]);

                    return $arg['Entries'][0]['Detail'] === json_encode($customPayload)
                        && $arg['Entries'][0]['DetailType'] === UserRetrievedWithCustomPayload::class
                        && $arg['Entries'][0]['EventBusName'] === 'users';
                }));
        });

        event($janeRetrieved);
    }

    /** @test */
    public function it_broadcasts_basic_event_to_multiple_channels_as_buses()
    {
        $janeRetrieved = new UserRetrievedWithMultipleChannels($this->createJane());

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(m::on(function ($arg) use ($janeRetrieved) {
                    return collect($janeRetrieved->broadcastOn())
                        ->map(function ($channel, $key) use ($arg, $janeRetrieved) {
                            return $arg['Entries'][$key]['Detail'] === json_encode($janeRetrieved)
                                && $arg['Entries'][$key]['DetailType'] === UserRetrievedWithMultipleChannels::class
                                && $arg['Entries'][$key]['EventBusName'] === $channel;
                        })
                        ->filter()
                        ->count() === 2;
                }));
        });

        event($janeRetrieved);
    }

    /** @test */
    public function it_can_use_a_source()
    {
        config(['broadcasting.connections.eventbridge.source' => 'some-other-source']);

        $janeRetrieved = new UserRetrievedWithMultipleChannels($this->createJane());

        $this->mockEventBridge(function (MockInterface $eventBridge) use ($janeRetrieved) {
            $eventBridge
                ->shouldReceive('putEvents')
                ->once()
                ->with(m::on(function ($arg) use ($janeRetrieved) {
                    return collect($janeRetrieved->broadcastOn())
                            ->map(function ($channel, $key) use ($arg) {
                                return $arg['Entries'][$key]['Source'] === 'some-other-source';
                            })
                            ->filter()
                            ->count() > 0;
                }));
        });

        event($janeRetrieved);
    }

    protected function createJane(): User
    {
        return User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@doe.com',
            'password' => 'shh',
        ])->fresh();
    }
}
