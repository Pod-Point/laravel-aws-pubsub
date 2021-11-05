<?php

namespace PodPoint\AwsPubSub\Tests\Console;

use PodPoint\AwsPubSub\Tests\TestCase;

class ListenerMakeCommandTest extends TestCase
{
    protected function tearDown()
    {
        // Must be before parent::tearDown() as it flushes the container,
        // which is required to be populated for the `app_path` helper.
        @unlink(app_path('Listeners/PubSub/SomeListener.php'));
        @rmdir(app_path('Listeners/PubSub'));
        @rmdir(app_path('Listeners'));

        parent::tearDown();
    }

    /** @test */
    public function it_can_generate_pubsub_event_listeners()
    {
        $this->assertFileMissing(app_path('Listeners/PubSub/SomeListener.php'));

        $this->artisan('pubsub:make:listener', ['name' => 'SomeListener']);

        $this->assertFileExists(app_path('Listeners/PubSub/SomeListener.php'));
    }
}
