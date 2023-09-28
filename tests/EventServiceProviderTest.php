<?php

namespace PodPoint\AwsPubSub\Tests;

use PodPoint\AwsPubSub\EventServiceProvider;

class EventServiceProviderTest extends TestCase
{
    /** @test */
    public function it_can_prepare_configuration_credentials()
    {
        $config = EventServiceProvider::prepareConfigurationCredentials([
            'foo' => 'bar',
            'key' => 'some_key',
            'secret' => 'some_secret',
        ]);

        $this->assertEquals([
            'foo' => 'bar',
            'key' => 'some_key',
            'secret' => 'some_secret',
            'credentials' => [
                'key' => 'some_key',
                'secret' => 'some_secret',
            ],
        ], $config);
    }

    /** @test */
    public function it_can_prepare_configuration_credentials_with_a_token()
    {
        $config = EventServiceProvider::prepareConfigurationCredentials([
            'foo' => 'bar',
            'key' => 'some_key',
            'secret' => 'some_secret',
            'token' => 'some_token',
        ]);

        $this->assertEquals([
            'foo' => 'bar',
            'key' => 'some_key',
            'secret' => 'some_secret',
            'token' => 'some_token',
            'credentials' => [
                'key' => 'some_key',
                'secret' => 'some_secret',
                'token' => 'some_token',
            ],
        ], $config);
    }

    /** @test */
    public function it_can_make_sure_some_aws_credentials_are_provided_before_preparing_the_credentials()
    {
        $config = EventServiceProvider::prepareConfigurationCredentials([
            'foo' => 'bar',
            'token' => 'some_token',
        ]);

        $this->assertArrayNotHasKey('credentials', $config);
    }

    public function invalidCredentialsDataProvider()
    {
        return [
            'key_is_empty' => [
                'creds' => [
                    'key' => '',
                    'secret' => 'some_secret',
                ],
            ],
            'secret_is_empty' => [
                'creds' => [
                    'key' => 'some_key',
                    'secret' => '',
                ],
            ],
            'key_and_secret_are_empty' => [
                'creds' => [
                    'key' => '',
                    'secret' => '',
                ],
            ],
            'key_is_null' => [
                'creds' => [
                    'key' => null,
                    'secret' => 'some_secret',
                ],
            ],
            'secret_is_null' => [
                'creds' => [
                    'key' => 'some_key',
                    'secret' => null,
                ],
            ],
            'key_and_secret_are_null' => [
                'creds' => [
                    'key' => null,
                    'secret' => null,
                ],
            ],
            'key_is_empty_and_secret_is_null' => [
                'creds' => [
                    'key' => '',
                    'secret' => null,
                ],
            ],
            'key_is_null_and_secret_is_empty' => [
                'creds' => [
                    'key' => null,
                    'secret' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider invalidCredentialsDataProvider
     */
    public function it_can_make_sure_some_aws_credentials_are_provided_and_valid(array $invalidCredentials)
    {
        $config = EventServiceProvider::prepareConfigurationCredentials(array_merge([
            'foo' => 'bar',
        ], $invalidCredentials));

        $this->assertArrayHasKey('foo', $config);
        $this->assertArrayNotHasKey('credentials', $config);
    }
}
