<?php

namespace PodPoint\AwsPubSub\Tests\Pub;

use Aws\Sns\SnsClient;
use Mockery;
use Mockery\MockInterface;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\User;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\UserWithBroadcastingEvents;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\UserWithBroadcastingEventsWhenUpdatedOnly;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\UserWithBroadcastingEventsWithCustomName;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\UserWithBroadcastingEventsWithCustomPayload;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\UserWithBroadcastingEventsWithCustomPayloadWhenUpdatedOnly;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\UserWithBroadcastingEventsWithMultipleChannels;
use PodPoint\AwsPubSub\Tests\TestCase;

class ModelEventsTest extends TestCase
{
    /** @test */
    public function it_broadcasts_model_event()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['model']['email'] === 'john@doe.com';
                }));
        });

        UserWithBroadcastingEvents::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ]);
    }

    /** @test */
    public function it_does_not_broadcast_model_events_without_trait()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldNotHaveReceived('publish');
        });

        User::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ]);
    }

    /** @test */
    public function it_broadcasts_model_event_with_custom_payload()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['data']['user']['email'] === 'john@doe.com'
                        && $message['action'] === 'created'
                        && $message['data']['foo'] === 'bar';
                }));
        });

        UserWithBroadcastingEventsWithCustomPayload::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ]);
    }

    /** @test */
    public function it_broadcasts_model_event_with_specified_event()
    {
        $user = UserWithBroadcastingEventsWhenUpdatedOnly::create([
            'name' => 'John Doe',
            'email' => 'john.doe@test.com',
            'password' => 'pas$word',
        ]);

        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['model']['email'] === 'john@doe.com';
                }));
        });

        $user->update([
            'email' => 'john@doe.com',
        ]);
    }

    /** @test */
    public function it_does_not_broadcast_model_event_without_specified_event()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldNotHaveReceived('publish');
        });

        UserWithBroadcastingEventsWhenUpdatedOnly::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ])->delete();
    }

    /** @test */
    public function it_broadcasts_model_event_with_specified_event_and_custom_payload()
    {
        $user = UserWithBroadcastingEventsWithCustomPayloadWhenUpdatedOnly::create([
            'name' => 'John Doe',
            'email' => 'john.doe@test.com',
            'password' => 'pas$word',
        ]);

        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['data']['user']['email'] === 'john@doe.com'
                        && $message['action'] === 'updated'
                        && $message['data']['foo'] === 'baz';
                }));
        });

        $user->update([
            'email' => 'john@doe.com',
        ]);
    }

    /** @test */
    public function it_broadcasts_model_events_to_multiple_channels()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->twice()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['model']['email'] === 'john@doe.com';
                }));
        });

        UserWithBroadcastingEventsWithMultipleChannels::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ]);
    }

    /** @test */
    public function it_broadcasts_model_event_name_as_subject()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    return $argument['Subject'] === 'UserWithBroadcastingEventsCreated';
                }));
        });

        UserWithBroadcastingEvents::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ]);
    }

    /** @test */
    public function it_broadcasts_model_event_name_as_subject_if_specified()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    return $argument['Subject'] === 'user.created';
                }));
        });

        UserWithBroadcastingEventsWithCustomName::create([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'pas$word',
        ]);
    }
}
