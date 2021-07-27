<?php

namespace PodPoint\AwsPubSub\Tests\Console;

use PodPoint\AwsPubSub\Tests\TestCase;

class ListenerMakeCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->cleanup();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->cleanup();
    }

    /** @test */
    public function it_can_generate_pubsub_event_listeners()
    {
        $this->assertFileDoesNotExist(app_path('Listeners/PubSub/SomeListener.php'));

        $this->artisan('pubsub:make:listener SomeListener')
            ->expectsOutput('Listener created successfully.');

        $this->assertFileExists(app_path('Listeners/PubSub/SomeListener.php'));
    }

    /** @test */
    public function it_cannot_generate_pubsub_event_listeners_which_already_exist()
    {
        $this->artisan('pubsub:make:listener SomeListener')
            ->expectsOutput('Listener created successfully.');

        $this->artisan('pubsub:make:listener SomeListener')
            ->expectsOutput('Listener already exists!');
    }

    private function cleanup()
    {
        @unlink(app_path('Listeners/PubSub/SomeListener.php'));
        @rmdir(app_path('Listeners/PubSub'));
        @rmdir(app_path('Listeners'));
    }
}
