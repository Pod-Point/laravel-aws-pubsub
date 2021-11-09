<?php

namespace PodPoint\AwsPubSub\Tests\Console;

use PodPoint\AwsPubSub\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        copy(config_path('app.php'), config_path('app.original'));

        if (! file_exists(app_path('Providers'))) {
            mkdir(app_path('Providers'));
        }
    }

    public function tearDown(): void
    {
        unlink(app_path('Providers').'/PubSubEventServiceProvider.php');
        copy(config_path('app.original'), config_path('app.php'));

        parent::tearDown();
    }

    /** @test */
    public function it_can_install_the_service_provider()
    {
        $this->assertFileMissing(app_path('Providers').'/PubSubEventServiceProvider.php');
        $this->assertStringNotContains('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));

        $this->artisan('pubsub:install');

        $this->assertFileExists(app_path('Providers').'/PubSubEventServiceProvider.php');
        $this->assertStringContains('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));
    }

    /** @test */
    public function it_does_not_install_the_service_provider_if_already_existing()
    {
        $this->artisan('pubsub:install');

        $this->assertFileExists(app_path('Providers').'/PubSubEventServiceProvider.php');
        $this->assertStringContains('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));

        $this->artisan('pubsub:install');

        $this->assertFileExists(app_path('Providers').'/PubSubEventServiceProvider.php');
        $this->assertStringContains('PubSubEventServiceProvider', file_get_contents(config_path('app.php')));
    }
}
