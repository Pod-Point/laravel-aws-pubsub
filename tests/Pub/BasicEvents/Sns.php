<?php

namespace PodPoint\AwsPubSub\Tests\Pub\BasicEvents;

use Aws\Sns\SnsClient;
use Mockery;
use Mockery\MockInterface;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrieved;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithCustomName;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithCustomPayload;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithMultipleChannels;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Events\UserRetrievedWithPublicProperties;
use PodPoint\AwsPubSub\Tests\Pub\TestClasses\Models\User;
use PodPoint\AwsPubSub\Tests\TestCase;

class Sns extends TestCase
{
    /** @test */
    public function it_broadcasts_basic_event()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['user']['email'] === 'john@doe.com'
                        && $message['foo'] = 'bar';
                }));
        });

        event(new UserRetrieved(User::create([
            'name' => $this->faker->name(),
            'email' => 'john@doe.com',
            'password' => $this->faker->password(),
        ])));
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['user']['email'] === 'john@doe.com'
                        && $message['action'] === 'retrieved'
                        && $message['foo'] = 'bar';
                }));
        });

        event(new UserRetrievedWithPublicProperties(User::create([
            'name' => $this->faker->name(),
            'email' => 'john@doe.com',
            'password' => $this->faker->password(),
        ])));
    }

    /** @test */
    public function it_broadcasts_basic_event_with_action_and_custom_payload()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['data']['user']['email'] === 'john@doe.com'
                        && $message['action'] === 'retrieved'
                        && $message['data']['foo'] === 'baz';
                }));
        });

        event(new UserRetrievedWithCustomPayload(User::create([
            'name' => $this->faker->name(),
            'email' => 'john@doe.com',
            'password' => $this->faker->password(),
        ])));
    }

    /** @test */
    public function it_broadcasts_basic_event_to_multiple_channels()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->twice()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['user']['email'] === 'john@doe.com'
                        && $message['foo'] = 'bat';
                }));
        });

        event(new UserRetrievedWithMultipleChannels(User::create([
            'name' => $this->faker->name(),
            'email' => 'john@doe.com',
            'password' => $this->faker->password(),
        ])));
    }

    /** @test */
    public function it_broadcasts_basic_event_name_as_subject()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['user']['email'] === 'john@doe.com'
                        && $argument['Subject'] === UserRetrieved::class;
                }));
        });

        event(new UserRetrieved(User::create([
            'name' => $this->faker->name(),
            'email' => 'john@doe.com',
            'password' => $this->faker->password(),
        ])));
    }

    /** @test */
    public function it_broadcasts_basic_event_name_as_subject_if_specified()
    {
        $this->mock(SnsClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')
                ->once()
                ->with(Mockery::on(function ($argument) {
                    $message = json_decode($argument['Message'], true);

                    return $message['user']['email'] === 'john@doe.com'
                        && $argument['Subject'] === 'user.retrieved';
                }));
        });

        event(new UserRetrievedWithCustomName(User::create([
            'name' => $this->faker->name(),
            'email' => 'john@doe.com',
            'password' => $this->faker->password(),
        ])));
    }
}
