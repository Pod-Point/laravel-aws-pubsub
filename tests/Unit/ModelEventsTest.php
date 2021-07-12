<?php

namespace PodPoint\SnsBroadcaster\Tests\Unit;

use Aws\Sns\SnsClient;
use Mockery;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\UserWithBroadcastingEvents;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\UserWithBroadcastingEventsForSpecificEvents;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\UserWithBroadcastingEventsWithCustomPayload;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\UserWithBroadcastingEventsWithCustomPayloadForSpecificEvents;
use PodPoint\SnsBroadcaster\Tests\TestCase;

class ModelEventsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function test_broadcasts_model_event()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $userData = [
            'name' => 'Foo Bar',
            'email' => 'model-event-1@email.com',
            'password' => 'password',
        ];

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($userData) {
            $message = json_decode($argument['Message'], true);

            return $message['model']['email'] == $userData['email'];
        }));

        UserWithBroadcastingEvents::create($userData);
    }

    /** @test */
    public function test_broadcasts_model_event_with_custom_payload()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $userData = [
            'name' => 'Foo Bar',
            'email' => 'model-event-2@email.com',
            'password' => 'password',
        ];

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($userData) {
            $message = json_decode($argument['Message'], true);

            return $message['data']['user']['email'] == $userData['email']
                && $message['action'] == 'created'
                && $message['data']['foo'] == 'bar';
        }));

        UserWithBroadcastingEventsWithCustomPayload::create($userData);
    }

    /** @test */
    public function test_broadcasts_model_event_with_specified_event()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $user = UserWithBroadcastingEventsForSpecificEvents::create([
            'name' => 'Foo Bar',
            'email' => 'model-event-3@email.com',
            'password' => 'password',
        ]);

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($user) {
            $message = json_decode($argument['Message'], true);

            return $message['model']['id'] == $user->id && $message['model']['email'] == 'model-event-3-updated@email.com';
        }));

        $user->update([
            'email' => 'model-event-3-updated@email.com',
        ]);
    }

    /** @test */
    public function test_does_not_broadcast_model_event_without_specified_event()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $mocked->shouldNotHaveReceived('publish');

        $user = UserWithBroadcastingEventsForSpecificEvents::create([
            'name' => 'Foo Bar',
            'email' => 'model-event-4@email.com',
            'password' => 'password',
        ]);

        $user->delete();
    }

    /** @test */
    public function test_broadcasts_model_event_with_specified_event_and_custom_payload()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $user = UserWithBroadcastingEventsWithCustomPayloadForSpecificEvents::create([
            'name' => 'Foo Bar',
            'email' => 'model-event-5@email.com',
            'password' => 'password',
        ]);

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($user) {
            $message = json_decode($argument['Message'], true);

            return $message['data']['user']['email'] == 'model-event-5-updated@email.com'
                && $message['action'] == 'updated'
                && $message['data']['foo'] == 'baz';
        }));

        $user->update([
            'email' => 'model-event-5-updated@email.com',
        ]);
    }
}
