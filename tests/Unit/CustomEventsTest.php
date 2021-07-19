<?php

namespace PodPoint\SnsBroadcaster\Tests\Unit;

use Aws\Sns\SnsClient;
use Illuminate\Support\Arr;
use Mockery;
use PodPoint\SnsBroadcaster\Tests\Dummies\Events\UserRetrieved;
use PodPoint\SnsBroadcaster\Tests\Dummies\Events\UserRetrievedWithAction;
use PodPoint\SnsBroadcaster\Tests\Dummies\Events\UserRetrievedWithActionWithCustomPayload;
use PodPoint\SnsBroadcaster\Tests\Dummies\Events\UserRetrievedWithMultipleChannels;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\User;
use PodPoint\SnsBroadcaster\Tests\TestCase;

class CustomEventsTest extends TestCase
{
    const EVENT_ACTION = 'retrieved';

    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function test_broadcasts_custom_event()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $user = User::create([
            'name' => 'Foo',
            'email' => 'custom-event-1@email.com',
            'password' => 'password',
        ]);

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($user) {
            $message = json_decode($argument['Message'], true);

            return $message['user']['id'] == $user->id
                && $message['foo'] = 'bar'
                && $argument['Subject'] == 'users.user_retrieved';
        }));

        event(new UserRetrieved($user));
    }

    /** @test */
    public function test_broadcasts_custom_event_with_action()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $user = User::create([
            'name' => 'Foo',
            'email' => 'custom-event-2@email.com',
            'password' => 'password',
        ]);

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($user) {
            $message = json_decode($argument['Message'], true);

            return $message['user']['id'] == $user->id
                && $message['action'] == self::EVENT_ACTION
                && $message['foo'] = 'bar'
                && $argument['Subject'] == 'users.' . self::EVENT_ACTION;
        }));

        event(new UserRetrievedWithAction($user));
    }

    /** @test */
    public function test_broadcasts_custom_event_with_action_and_custom_payload()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $user = User::create([
            'name' => 'Foo',
            'email' => 'custom-event-3@email.com',
            'password' => 'password',
        ]);

        $mocked->shouldReceive('publish')->once()->with(Mockery::on(function ($argument) use ($user) {
            $message = json_decode($argument['Message'], true);

            return $message['data']['user']['id'] == $user->id
                && $message['action'] == self::EVENT_ACTION
                && $message['data']['foo'] == 'baz'
                && $argument['Subject'] == 'users.' . self::EVENT_ACTION;
        }));

        event(new UserRetrievedWithActionWithCustomPayload($user));
    }

    /** @test */
    public function test_broadcasts_custom_event_to_multiple_channels()
    {
        $mocked = Mockery::mock(SnsClient::class);

        $this->app->instance(SnsClient::class, $mocked);

        $user = User::create([
            'name' => 'Foo',
            'email' => 'custom-event-4@email.com',
            'password' => 'password',
        ]);

        $mocked->shouldReceive('publish')->twice()->with(Mockery::on(function ($argument) use ($user) {
            $message = json_decode($argument['Message'], true);

            return $message['user']['id'] == $user->id
                && $message['foo'] = 'bat'
                && in_array($argument['Subject'], [
                    'users.user_retrieved_with_multiple_channels',
                    'customers.user_retrieved_with_multiple_channels',
                ]);
        }));

        event(new UserRetrievedWithMultipleChannels($user));
    }
}
