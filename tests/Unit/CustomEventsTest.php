<?php

namespace PodPoint\SnsBroadcaster\Tests\Unit;

use Aws\Sns\SnsClient;
use Mockery;
use PodPoint\SnsBroadcaster\Tests\Dummies\Events\UserRetrieved;
use PodPoint\SnsBroadcaster\Tests\Dummies\Events\UserRetrievedWithBroadcastWith;
use PodPoint\SnsBroadcaster\Tests\Dummies\Models\User;
use PodPoint\SnsBroadcaster\Tests\TestCase;

class CustomEventsTest extends TestCase
{
    const EVENT_ACTION = 'RETRIEVED';

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
                && $message['action'] == self::EVENT_ACTION
                && $message['foo'] = 'bar';
        }));

        event(new UserRetrieved($user));
    }

    /** @test */
    public function test_broadcasts_custom_event_with_custom_payload()
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

            return $message['data']['user']['id'] == $user->id
                && $message['action'] == self::EVENT_ACTION
                && $message['data']['foo'] == 'baz';
        }));

        event(new UserRetrievedWithBroadcastWith($user));
    }
}
