<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use PodPoint\AwsPubSub\Tests\TestCase;

class GenerateListenersTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        unlink(app_path('Listeners/PubSub/SomeListener.php'));
        rmdir(app_path('Listeners/PubSub'));
        rmdir(app_path('Listeners'));
    }

    /** @test */
    public function it_can_generate_pubsub_event_listeners()
    {
        $this->assertFileDoesNotExist(app_path('Listeners/PubSub/SomeListener.php'));

        $this->artisan('pubsub:make:listener SomeListener')->assertExitCode(0);

        $this->assertFileExists(app_path('Listeners/PubSub/SomeListener.php'));
    }

    /** @test */
    public function it_cannot_generate_pubsub_event_listeners_which_already_exist()
    {
        $this->artisan('pubsub:make:listener SomeListener')->assertExitCode(0);

        $this->artisan('pubsub:make:listener SomeListener')->expectsOutput('Listener already exists!');
    }
}
