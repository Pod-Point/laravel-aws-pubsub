<?php

namespace PodPoint\AwsPubSub\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as Orchestra;
use PodPoint\AwsPubSub\AwsPubSubServiceProvider;
use PodPoint\AwsPubSub\EventServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase,
        WithFaker;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AwsPubSubServiceProvider::class,
            EventServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function setTestDatabase($app)
    {
        /** DATABASE */
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->setSnsBroadcaster($app);

        $this->setSubQueue($app);

        $this->setTestDatabase($app);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function setSnsBroadcaster($app): void
    {
        /** PUB */
        $app['config']->set('broadcasting.default', 'sns');
        $app['config']->set('broadcasting.connections.sns', [
            'driver' => 'sns',
            'key' => 'dummy-key',
            'secret' => 'dummy-secret',
            'arn-prefix' => 'aws:arn:12345:',
            'arn-suffix' => '',
            'region' => 'eu-west-1',
        ]);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function setEventBridgeBroadcaster($app)
    {
        /** PUB */
        $app['config']->set('broadcasting.default', 'eventbridge');
        $app['config']->set('broadcasting.connections.eventbridge', [
            'driver' => 'eventbridge',
            'key' => 'dummy-key',
            'secret' => 'dummy-secret',
            'region' => 'eu-west-1',
            'event_bus' => 'default',
            'source' => 'my-app',
        ]);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function setSubQueue($app): void
    {
        /** SUB */
        $app['config']->set('queue.connections.pub-sub', [
            'driver' => 'sqs-sns',
            'key' => 'dummy-key',
            'secret' => 'dummy-secret',
            'prefix' => 'https://sqs.eu-west-1.amazonaws.com/13245',
            'queue' => 'default',
            'suffix' => '',
            'region' => 'eu-west-1',
        ]);
    }
}
