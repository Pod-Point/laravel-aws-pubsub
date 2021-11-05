<?php

namespace PodPoint\AwsPubSub\Tests;

use Closure;
use Mockery;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\ExpectationFailedException;
use PodPoint\AwsPubSub\AwsPubSubServiceProvider;
use PodPoint\AwsPubSub\EventServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
    }

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
            'region' => 'eu-west-1',
        ]);

        $this->setTestDatabase($app);
    }

    /**
     * Added for backwards compatability with Laravel 5.4 as it otherwise doesn't exist.
     *
     * Check that the given filename doesn't exist in the filesystem.
     *
     * @param  string  $filename
     * @param  string  $message
     */
    public function assertFileDoesNotExist(string $filename, string $message = '')
    {
        static::assertThat($filename, new LogicalNot(new FileExists), $message);
    }

    /**
     * Added for backwards compatability with PHPUnit 6.
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $constraint = new LogicalNot(new StringContains($needle));

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * Added for backwards compatability with PHPUnit 6.
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        static::assertThat($haystack, new StringContains($needle), $message);
    }

    /**
     * Added for backwards compatability with Laravel 5.4 as it otherwise doesn't exist.
     *
     * Mock an instance of an object in the container.
     *
     * @param  string  $abstract
     * @param  Closure|null  $mock
     * @return MockInterface
     */
    protected function mock(string $abstract, Closure $mock = null): MockInterface
    {
        $mock = Mockery::mock(...array_filter(func_get_args()));

        $this->app->instance($abstract, $mock);

        return $mock;
    }
}
