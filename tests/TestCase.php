<?php

namespace PodPoint\SnsBroadcaster\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as Orchestra;
use PodPoint\SnsBroadcaster\SnsBroadcasterServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase,
        WithFaker;

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
            SnsBroadcasterServiceProvider::class,
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
        $app['config']->set('broadcasting.default', 'sns');
        $app['config']->set('broadcasting.connections.sns', [
            'driver' => 'sns',
            'region' => 'eu-west-1',
            'key' => 'foo-bar',
            'secret' => 'foo-baz',
            'arn-prefix' => 'aws:arn:12345:',
            'arn-suffix' => '-local',
        ]);

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
