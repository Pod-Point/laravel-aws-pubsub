<?php

namespace PodPoint\AwsPubSub\Tests\Sub;

use PodPoint\AwsPubSub\Tests\TestCase;

class InstallationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        copy(config_path('app.php'), config_path('app.original'));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unlink(app_path('Providers') . '/PubSubEventServiceProvider.php');
        copy(config_path('app.original'), config_path('app.php'));
    }

    /** @test */
    public function it_can_install_the_service_provider()
    {
        $this->assertFileDoesNotExist(app_path('Providers') . '/PubSubEventServiceProvider.php');
        $this->assertStringNotContainsString('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));

        $this->artisan('pubsub:install')->assertExitCode(0);

        $this->assertFileExists(app_path('Providers') . '/PubSubEventServiceProvider.php');
        $this->assertStringContainsString('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));
    }

    /** @test */
    public function it_does_not_install_the_service_provider_if_already_existing()
    {
        $this->artisan('pubsub:install')->assertExitCode(0);

        $this->assertFileExists(app_path('Providers') . '/PubSubEventServiceProvider.php');
        $this->assertStringContainsString('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));

        $this->artisan('pubsub:install')
            ->expectsOutput('PubSubEventServiceProvider already exists!')
            ->assertExitCode(1);

        $this->assertFileExists(app_path('Providers') . '/PubSubEventServiceProvider.php');
        $this->assertStringContainsString('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));
    }
}
