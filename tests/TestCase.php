<?php

namespace PodPoint\AwsPubSub\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PodPoint\AwsPubSub\AwsPubSubServiceProvider;
use PodPoint\AwsPubSub\EventServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
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
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
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

        /** SUB */
        $app['config']->set('queue.connections.pub-sub', [
            'driver' => 'sqs-sns',
            'key' => 'dummy-key',
            'secret' => 'dummy-secret',
            'prefix' => 'https://sqs.eu-west-1.amazonaws.com/13245',
            'queue' => 'default',
            'region' => 'eu-west-1',
        ]);

        /** DATABASE */
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
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
}
